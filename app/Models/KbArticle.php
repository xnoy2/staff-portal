<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KbArticle extends Model
{
    use HasUuids;

    protected $table = 'kb_articles';

    protected $fillable = [
        'category_id', 'title', 'slug', 'content', 'excerpt',
        'is_published', 'visible_to', 'sort_order', 'author_id', 'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order'   => 'integer',
        'visible_to'   => 'array',
    ];

    public function isVisibleTo(\App\Models\User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        $roles = $this->visible_to;
        if (empty($roles)) {
            return true;
        }
        return $user->hasAnyRole($roles);
    }

    protected static function booted(): void
    {
        static::creating(function (self $article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
            if (empty($article->excerpt) && $article->content) {
                $article->excerpt = Str::limit(strip_tags($article->content), 200);
            }
        });

        static::updating(function (self $article) {
            if ($article->isDirty('content') && empty($article->excerpt)) {
                $article->excerpt = Str::limit(strip_tags($article->content), 200);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
