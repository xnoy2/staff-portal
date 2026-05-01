<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntryBreak extends Model
{
    use HasUuids;

    protected $fillable = [
        'time_entry_id',
        'type',
        'started_at',
        'ended_at',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at'   => 'datetime',
        ];
    }

    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class);
    }

    public function end(): void
    {
        $this->ended_at         = now();
        $this->duration_minutes = (int) $this->started_at->diffInMinutes($this->ended_at);
        $this->save();
    }
}
