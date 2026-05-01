<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class TimeEntry extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'van_id',
        'clock_in',
        'clock_out',
        'total_hours',
        'is_overtime',
        'status',
        'source',
        'entered_by',
        'approved_by',
        'approved_at',
        'notes',
        'rejection_reason',
        'clock_state',
    ];

    protected function casts(): array
    {
        return [
            'clock_in'    => 'datetime',
            'clock_out'   => 'datetime',
            'total_hours' => 'decimal:2',
            'is_overtime' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (TimeEntry $entry) {
            if ($entry->isDirty('clock_out') && $entry->clock_out) {
                $entry->calculateHours();
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(TimeEntryBreak::class);
    }

    public function activeBreak(): ?TimeEntryBreak
    {
        return $this->breaks()->whereNull('ended_at')->latest()->first();
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('clock_out');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('clock_in', today());
    }

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('clock_in', '>=', $from);
        }
        if ($to) {
            $query->whereDate('clock_in', '<=', $to);
        }
        return $query;
    }

    // ─── Methods ──────────────────────────────────────────────────────────────

    public function calculateHours(): void
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return;
        }

        $totalMinutes = $this->clock_in->diffInMinutes($this->clock_out);
        $breakMinutes = $this->breaks()->whereNotNull('ended_at')->sum('duration_minutes') ?? 0;
        $workedMinutes = max(0, $totalMinutes - $breakMinutes);

        $this->total_hours = round($workedMinutes / 60, 2);
        $this->is_overtime = $this->total_hours > 8;
    }

    public function approve(User $approver): void
    {
        $this->update([
            'status'      => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(User $approver, ?string $reason = null): void
    {
        $this->update([
            'status'           => 'rejected',
            'approved_by'      => $approver->id,
            'approved_at'      => now(),
            'rejection_reason' => $reason,
        ]);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getDurationLabelAttribute(): string
    {
        if (! $this->clock_in) {
            return '—';
        }

        $end          = $this->clock_out ?? now();
        $totalMinutes = $this->clock_in->diffInMinutes($end);
        $breakMinutes = $this->breaks()->whereNotNull('ended_at')->sum('duration_minutes') ?? 0;
        $minutes      = max(0, $totalMinutes - $breakMinutes);
        $h            = intdiv($minutes, 60);
        $m            = $minutes % 60;

        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }
}
