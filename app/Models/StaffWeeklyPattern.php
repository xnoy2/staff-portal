<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffWeeklyPattern extends Model
{
    protected $fillable = [
        'user_id', 'day_of_week', 'shift_start', 'shift_end', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
