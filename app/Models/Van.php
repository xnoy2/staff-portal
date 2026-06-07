<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Van extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['registration', 'make', 'model', 'year', 'is_active', 'notes', 'photo'];

    protected $casts = ['is_active' => 'boolean'];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) return null;

        try {
            $publicUrl = config('filesystems.disks.r2.url');
            if ($publicUrl) {
                return rtrim($publicUrl, '/') . '/' . $this->photo;
            }
            return Storage::disk('r2')->temporaryUrl($this->photo, now()->addWeek());
        } catch (\Throwable) {}

        if (Storage::disk('public')->exists($this->photo)) {
            return Storage::disk('public')->url($this->photo);
        }

        return null;
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(VanAllocation::class);
    }

    // Legacy M2M — kept for backward compatibility with existing jobs/queries
    public function staff(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'van_user')->withPivot('assigned_at');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VanAssignment::class)->orderBy('assigned_at', 'desc');
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(VanAssignment::class)
            ->whereNull('returned_at')
            ->orderBy('assigned_at', 'desc');
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->registration} — {$this->make} {$this->model}";
    }
}
