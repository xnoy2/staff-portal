<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Van;

class VanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'site_head']);
    }

    public function view(User $user, Van $van): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'site_head']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Van $van): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function delete(User $user, Van $van): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function restore(User $user, Van $van): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function forceDelete(User $user, Van $van): bool
    {
        return $user->hasRole('admin');
    }
}
use App\Models\Van;
use Illuminate\Auth\Access\Response;

class VanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Van $van): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Van $van): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Van $van): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Van $van): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Van $van): bool
    {
        return false;
    }
}
