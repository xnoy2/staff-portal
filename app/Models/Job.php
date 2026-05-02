<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'work_orders';

    protected $fillable = [
        'project_id', 'van_id', 'created_by',
        'title', 'description', 'date',
        'start_time', 'end_time', 'status', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function van(): BelongsTo
    {
        return $this->belongsTo(Van::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'work_order_user', 'work_order_id', 'user_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
