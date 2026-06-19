<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardComment extends Model
{
    use HasUuids;

    protected $fillable = ['card_id', 'user_id', 'body'];

    public function card(): BelongsTo
    {
        return $this->belongsTo(BoardCard::class, 'card_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
