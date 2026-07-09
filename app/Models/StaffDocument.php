<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDocument extends Model
{
    protected $fillable = [
        'user_id', 'category', 'title', 'original_name', 'mime_type', 'size', 'path', 'uploaded_by',
    ];

    public const CATEGORIES = [
        'contract'    => 'Employment Contract',
        'non_compete' => 'Non-Compete / T&C (signed)',
        'handbook'    => 'Handbook / Policy',
        'id'          => 'ID / Right to Work',
        'other'       => 'Other',
    ];

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Other';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
