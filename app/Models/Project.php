<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Project extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'business', 'name', 'customer', 'address', 'status', 'phase',
        'start_date', 'end_date', 'budget', 'budget_spent',
        'van_id', 'created_by', 'notes',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'budget'       => 'decimal:2',
        'budget_spent' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->dontLogEmptyChanges();
    }

    // ── Relationships ────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function van(): BelongsTo
    {
        return $this->belongsTo(Van::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('role');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ProjectChecklistItem::class)->orderBy('sort_order')->orderBy('id');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ── Accessors ────────────────────────────────────────────────────

    public function getBudgetProgressAttribute(): int
    {
        if (!$this->budget || $this->budget == 0) return 0;
        return (int) min(100, round(($this->budget_spent / $this->budget) * 100));
    }

    public function getIsOverBudgetAttribute(): bool
    {
        return $this->budget && $this->budget_spent > $this->budget;
    }

    public function getChecklistProgressAttribute(): int
    {
        $total = $this->checklistItems()->count();
        if ($total === 0) return 0;
        $done = $this->checklistItems()->where('is_completed', true)->count();
        return (int) round(($done / $total) * 100);
    }
}
