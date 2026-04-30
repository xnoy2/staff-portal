<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VanAllocation extends Model
{
    use HasUuids;

    protected $fillable = [
        'van_id', 'project_id', 'allocated_from', 'allocated_to',
        'purpose', 'notes', 'created_by',
    ];

    protected $casts = [
        'allocated_from' => 'date',
        'allocated_to'   => 'date',
    ];

    public function van(): BelongsTo
    {
        return $this->belongsTo(Van::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusAttribute(): string
    {
        $today = today();
        if ($this->allocated_to->lt($today))  return 'past';
        if ($this->allocated_from->gt($today)) return 'upcoming';
        return 'current';
    }

    public function scopeActive($query)
    {
        return $query->where('allocated_to', '>=', today());
    }

    public function scopeConflicting($query, string $vanId, string $from, string $to, ?string $excludeId = null)
    {
        return $query->where('van_id', $vanId)
            ->where('allocated_from', '<=', $to)
            ->where('allocated_to', '>=', $from)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));
    }
}
