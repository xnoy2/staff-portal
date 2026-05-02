<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // all authenticated users — query scoping handles role filtering
    }

    public function view(User $user, Job $job): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) return true;
        if ($user->hasRole('site_head')) {
            return $job->project_id && $user->projects()->where('projects.id', $job->project_id)->exists();
        }
        return $job->staff()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'site_head']);
    }

    public function update(User $user, Job $job): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) return true;
        if ($user->hasRole('site_head')) {
            return $job->project_id && $user->projects()->where('projects.id', $job->project_id)->exists();
        }
        return false;
    }

    public function delete(User $user, Job $job): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function restore(User $user, Job $job): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function forceDelete(User $user, Job $job): bool
    {
        return $user->hasRole('admin');
    }
}
