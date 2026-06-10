<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasUuids;

    public const TYPES = ['general', 'blog', 'event', 'recognition'];

    public const REACTION_TYPES = ['like', 'love', 'celebrate', 'clap', 'laugh'];

    protected $fillable = [
        'user_id', 'type', 'title', 'body', 'images',
        'event_date', 'event_location', 'recognized_user_id', 'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'images'     => 'array',
            'event_date' => 'date',
            'is_pinned'  => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recognizedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recognized_user_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class)->orderBy('created_at');
    }
}
