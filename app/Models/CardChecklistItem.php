<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardChecklistItem extends Model
{
    use HasUuids;

    protected $table = 'card_checklist_items';

    protected $fillable = ['card_id', 'title', 'is_done', 'sort_order'];

    protected $casts = [
        'is_done'    => 'boolean',
        'sort_order' => 'integer',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(BoardCard::class, 'card_id');
    }
}
