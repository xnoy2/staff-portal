<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VanAssignment extends Model
{
    protected $fillable = [
        'van_id', 'user_id', 'assigned_by',
        'assigned_at', 'returned_at', 'returned_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function van(): BelongsTo
    {
        return $this->belongsTo(Van::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->returned_at === null;
    }

    public function getDurationAttribute(): string
    {
        $end = $this->returned_at ?? now();
        $days = (int) $this->assigned_at->diffInDays($end);
        if ($days === 0) return 'Today';
        if ($days === 1) return '1 day';
        if ($days < 30) return "{$days} days";
        $months = (int) round($days / 30);
        return "{$months} month" . ($months > 1 ? 's' : '');
    }
}
