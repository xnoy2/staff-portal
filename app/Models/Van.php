<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Van extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['registration', 'make', 'model', 'year', 'is_active', 'notes'];

    protected $casts = ['is_active' => 'boolean'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(VanAllocation::class);
    }

    public function staff(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'van_user')->withPivot('assigned_at');
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->registration} — {$this->make} {$this->model}";
    }
}
