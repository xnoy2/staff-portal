<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectChecklistItem extends Model
{
    use HasUuids;

    protected $fillable = ['project_id', 'job_id', 'title', 'is_completed', 'completed_by', 'completed_at', 'sort_order'];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
