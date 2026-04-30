<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasUuids, HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'must_change_password',
        'hire_date',
        'annual_leave_days',
        'emergency_contact_name',
        'emergency_contact_phone',
        'certifications',
        'notes',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'must_change_password' => 'boolean',
            'hire_date'         => 'date',
            'certifications'    => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active', 'must_change_password'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withPivot('role')
            ->orderBy('name');
    }

    public function vans(): BelongsToMany
    {
        return $this->belongsToMany(Van::class, 'van_user')->withPivot('assigned_at');
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            $publicUrl = config('filesystems.disks.r2.url');
            if ($publicUrl) {
                return rtrim($publicUrl, '/') . '/' . $this->avatar;
            }
            return Storage::disk('r2')->temporaryUrl($this->avatar, now()->addWeek());
        }
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&background=3B6D11&color=fff&size=128";
    }
}
