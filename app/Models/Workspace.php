<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    use HasUuids;

    protected $fillable = ['owner_id', 'name', 'color', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class)->orderBy('sort_order')->orderBy('created_at');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function hasMember(string $userId): bool
    {
        return $this->members()->where('users.id', $userId)->exists();
    }

    public function isOwner(string $userId): bool
    {
        return $this->owner_id === $userId
            || $this->members()->where('users.id', $userId)->wherePivot('role', 'owner')->exists();
    }
}
