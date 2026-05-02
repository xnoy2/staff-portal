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
