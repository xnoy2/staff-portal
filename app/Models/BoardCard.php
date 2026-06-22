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
        'list_id', 'title', 'description', 'start_date', 'due_date', 'due_done',
        'due_reminder', 'due_reminder_sent_at', 'recurring', 'recurred_at',
        'created_by', 'sort_order',
    ];

    protected $casts = [
        'sort_order'           => 'integer',
        'start_date'           => 'date',
        'due_date'             => 'datetime',
        'due_done'             => 'boolean',
        'due_reminder_sent_at' => 'datetime',
        'recurred_at'          => 'datetime',
    ];

    /** Minutes-before-due for each reminder option. */
    public const REMINDER_OFFSETS = [
        'at_time' => 0,
        '5_min'   => 5,
        '10_min'  => 10,
        '15_min'  => 15,
        '1_hour'  => 60,
        '2_hour'  => 120,
        '1_day'   => 1440,
        '2_day'   => 2880,
    ];

    public const RECURRING_OPTIONS = ['never', 'daily', 'weekly', 'monthly'];

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
