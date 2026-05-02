<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'site_head']);
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) return true;
        if ($user->hasRole('site_head')) {
            return $user->projects()->where('projects.id', $project->id)->exists();
        }
        // Staff can view projects they are assigned to
        return $user->projects()->where('projects.id', $project->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $user->hasRole('admin');
    }
}
