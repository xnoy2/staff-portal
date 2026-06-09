<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends Model
{
    protected $fillable = [
        'user_id', 'time_entry_id', 'date', 'start_time', 'end_time', 'type',
        'reason', 'status', 'reviewed_by', 'reviewed_at', 'reviewer_notes',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class);
    }

    public function getDurationHoursAttribute(): float
    {
        [$sh, $sm] = explode(':', substr($this->start_time, 0, 5));
        [$eh, $em] = explode(':', substr($this->end_time,   0, 5));
        return round(((int)$eh * 60 + (int)$em - ((int)$sh * 60 + (int)$sm)) / 60, 2);
    }
}
