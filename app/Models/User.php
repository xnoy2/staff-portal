<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->employee_id)) {
                $user->employee_id = static::generateEmployeeId();
            }
        });
    }

    public static function generateEmployeeId(): string
    {
        $latest = static::where('employee_id', 'like', 'STAFF%')
            ->orderByRaw('CAST(SUBSTR(employee_id, 6) AS UNSIGNED) DESC')
            ->value('employee_id');

        $next = $latest ? ((int) substr($latest, 5)) + 1 : 1;
        return 'STAFF' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    protected $fillable = [
        'name',
        'employee_id',
        'email',
        'password',
        'is_active',
        'must_change_password',
        'hire_date',
        'last_login_at',
        'annual_leave_days',
        'emergency_contact_name',
        'emergency_contact_phone',
        'certifications',
        'notes',
        'avatar',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['avatar_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'must_change_password' => 'boolean',
            'hire_date'         => 'date',
        'last_login_at'     => 'datetime',
            'certifications' => 'array',
            'preferences'    => 'array',
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
            ->orderBy('projects.name');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function vans(): BelongsToMany
    {
        return $this->belongsToMany(Van::class, 'van_user')->withPivot('assigned_at');
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            try {
                $publicUrl = config('filesystems.disks.r2.url');
                if ($publicUrl) {
                    return rtrim($publicUrl, '/') . '/' . $this->avatar;
                }
                return Storage::disk('r2')->temporaryUrl($this->avatar, now()->addWeek());
            } catch (\Throwable) {
                // R2 not configured or unavailable — fall through to default
            }
        }
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&background=3B6D11&color=fff&size=128";
    }
}
