<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardLabel extends Model
{
    use HasUuids;

    protected $fillable = ['board_id', 'color', 'name', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
