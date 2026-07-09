<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingCertificate extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'module_id', 'module_title', 'reference', 'issued_at'];

    protected $casts = ['issued_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'module_id');
    }

    /**
     * Issue (or return the existing) certificate for a user + module, but only
     * once every published lesson in the module has been completed. Returns null
     * if the module isn't complete (or has no lessons).
     */
    public static function issueIfComplete(User $user, TrainingModule $module): ?self
    {
        $lessonIds = $module->publishedLessons()->pluck('id');
        if ($lessonIds->isEmpty()) {
            return null;
        }

        $completed = TrainingProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->get(['completed_at']);

        if ($completed->count() < $lessonIds->count()) {
            return null;
        }

        $completedAt = $completed->pluck('completed_at')->max() ?? now();

        return static::firstOrCreate(
            ['user_id' => $user->id, 'module_id' => $module->id],
            [
                'module_title' => $module->title,
                'reference'    => strtoupper(substr(hash('sha256', $user->id . '|' . $module->id), 0, 10)),
                'issued_at'    => $completedAt,
            ],
        );
    }
}
