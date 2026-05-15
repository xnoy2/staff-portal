<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SubcontractorPhoto extends Model
{
    protected $fillable = [
        'subcontractor_id', 'type', 'path', 'original_name', 'caption', 'uploaded_by',
    ];

    protected $appends = ['url'];

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
