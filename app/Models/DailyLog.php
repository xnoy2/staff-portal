<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'log_date', 'status', 'summary', 'blockers', 'plan_tomorrow',
        'photos', 'jobs', 'team', 'submitted_at', 'acknowledged_by', 'acknowledged_at', 'manager_comment',
    ];

    protected function casts(): array
    {
        return [
            'log_date'        => 'date',
            'photos'          => 'array',
            'jobs'            => 'array',
            'team'            => 'array',
            'submitted_at'    => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
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
