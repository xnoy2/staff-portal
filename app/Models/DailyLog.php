<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'log_date', 'status', 'summary', 'blockers', 'plan_tomorrow',
        'submitted_at', 'acknowledged_by', 'acknowledged_at', 'manager_comment',
    ];

    protected function casts(): array
    {
        return [
            'log_date'        => 'date',
            'submitted_at'    => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    protected $appends = ['total_minutes'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityEntry::class)->orderBy('sort_order')->orderBy('created_at');
    }

    /** Total logged minutes across this day's activities. */
    public function getTotalMinutesAttribute(): int
    {
        // Prefer an eager withSum() result when present (avoids a query, incl. for empty logs).
        if (array_key_exists('activities_sum_duration_minutes', $this->attributes)) {
            return (int) $this->attributes['activities_sum_duration_minutes'];
        }
        // Otherwise sum the relation (loads it if needed) so the value is always correct.
        return (int) $this->activities->sum('duration_minutes');
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function scopeSubmitted($q)
    {
        return $q->where('status', 'submitted');
    }
}
