<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'name', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lists(): HasMany
    {
        return $this->hasMany(BoardList::class)->orderBy('sort_order');
    }

    public function labels(): HasMany
    {
        return $this->hasMany(BoardLabel::class)->orderBy('sort_order');
    }
}
