<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingModule extends Model
{
    use HasUuids;

    protected $fillable = [
        'title', 'description', 'thumbnail',
        'sort_order', 'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order'   => 'integer',
    ];

    public function lessons(): HasMany
    {
        return $this->hasMany(TrainingLesson::class, 'module_id')->orderBy('sort_order');
    }

    public function publishedLessons(): HasMany
    {
        return $this->hasMany(TrainingLesson::class, 'module_id')
            ->where('is_published', true)
            ->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
