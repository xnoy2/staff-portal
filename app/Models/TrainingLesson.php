<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TrainingLesson extends Model
{
    use HasUuids;

    protected $fillable = [
        'module_id', 'title', 'description', 'video_path',
        'duration_seconds', 'sort_order', 'is_published',
    ];

    protected $casts = [
        'is_published'     => 'boolean',
        'sort_order'       => 'integer',
        'duration_seconds' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'module_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(TrainingProgress::class, 'lesson_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function getDurationLabelAttribute(): string
    {
        if (! $this->duration_seconds) return '';
        $m = intdiv($this->duration_seconds, 60);
        $s = $this->duration_seconds % 60;
        return $m > 0 ? "{$m}m " . ($s ? "{$s}s" : '') : "{$s}s";
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (! $this->video_path) return null;

        if (! config('filesystems.disks.r2.bucket')) {
            return Storage::disk('public')->url($this->video_path);
        }

        // Prefer a stable public CDN URL; fall back to a 6-hour signed URL
        if (config('filesystems.disks.r2.url')) {
            return Storage::disk('r2')->url($this->video_path);
        }

        return Storage::disk('r2')->temporaryUrl($this->video_path, now()->addHours(6));
    }
}
