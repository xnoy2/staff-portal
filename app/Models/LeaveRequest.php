<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class LeaveRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'type', 'start_date', 'end_date', 'days',
        'reason', 'status', 'created_by', 'reviewed_by',
        'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'days'        => 'decimal:1',
        'reviewed_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopePending($query)  { return $query->where('status', 'pending'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeForUser($query, string $userId) { return $query->where('user_id', $userId); }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('start_date', $year);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public static function workingDays(Carbon $start, Carbon $end): float
    {
        $days = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            if ($current->isWeekday()) $days++;
            $current->addDay();
        }
        return (float) $days;
    }
}
