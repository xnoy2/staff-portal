<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityEntry extends Model
{
    use HasUuids;

    /** Selectable activity categories. */
    public const CATEGORIES = ['installation', 'travel', 'admin', 'meeting', 'site_visit', 'other'];

    protected $fillable = [
        'daily_log_id', 'user_id', 'description', 'category',
        'job_id', 'duration_minutes', 'photos', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'photos'           => 'array',
            'duration_minutes' => 'integer',
            'sort_order'       => 'integer',
        ];
    }

    public function dailyLog(): BelongsTo
    {
        return $this->belongsTo(DailyLog::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
