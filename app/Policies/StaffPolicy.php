<?php

namespace App\Policies;

use App\Models\User;

class StaffPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'site_head']);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->hasAnyRole(['admin', 'manager', 'site_head'])) return true;
        return $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, User $model): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) return true;
        return $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) return false;
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function toggleActive(User $user, User $model): bool
    {
        if ($user->id === $model->id) return false;
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
use Illuminate\Auth\Access\Response;

class StaffPolicy
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
    public function view(User $user, User $model): bool
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
    public function update(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
