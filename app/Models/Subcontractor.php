<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subcontractor extends Model
{
    protected $fillable = [
        'name', 'trade', 'company', 'email', 'phone',
        'qualification_verified', 'insurance_verified',
        'notes', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'qualification_verified' => 'boolean',
            'insurance_verified'     => 'boolean',
            'is_active'              => 'boolean',
        ];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(SubcontractorPhoto::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
