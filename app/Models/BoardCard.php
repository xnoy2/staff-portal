<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardCard extends Model
{
    use HasUuids;

    protected $table = 'board_cards';

    protected $fillable = [
        'list_id', 'title', 'description', 'due_date', 'due_done', 'created_by', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'due_date'   => 'datetime',
        'due_done'   => 'boolean',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(BoardList::class, 'list_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(CardChecklistItem::class, 'card_id')->orderBy('sort_order');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(BoardLabel::class, 'board_card_label', 'card_id', 'label_id')
            ->orderBy('sort_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CardAttachment::class, 'card_id')->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CardComment::class, 'card_id')->oldest();
    }
}
