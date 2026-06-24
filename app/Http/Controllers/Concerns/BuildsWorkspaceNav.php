<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Models\Workspace;

trait BuildsWorkspaceNav
{
    /**
     * The user's first workspace, or null if they have none.
     *
     * Members see workspaces they belong to; admins see every workspace.
     */
    protected function firstWorkspace(User $user): ?Workspace
    {
        return $this->workspaceBaseQuery($user)
            ->orderBy('workspaces.sort_order')
            ->orderBy('workspaces.created_at')
            ->first();
    }

    /** Left-nav data: each workspace the user can see, with its boards. */
    protected function workspaceNav(User $user): array
    {
        // Admins see every workspace; their role on a workspace they aren't a
        // member of is reported as 'admin' (full access).
        $isAdmin     = $user->canAccessAllWorkspaces();
        $memberRoles = $user->workspaces()->get()->pluck('pivot.role', 'id');

        return $this->workspaceBaseQuery($user)
            ->with('boards:id,workspace_id,name')
            ->orderBy('workspaces.sort_order')
            ->orderBy('workspaces.created_at')
            ->get()
            ->map(function ($w) use ($isAdmin, $memberRoles) {
                $role = $memberRoles[$w->id] ?? ($isAdmin ? 'admin' : null);
                return [
                    'id'       => $w->id,
                    'name'     => $w->name,
                    'color'    => $w->color,
                    'role'     => $role,
                    'is_owner' => in_array($role, ['owner', 'admin'], true),
                    'boards'   => $w->boards->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
                ];
            })->values()->toArray();
    }

    /** Workspaces this user may see: their memberships, or all workspaces for admins. */
    private function workspaceBaseQuery(User $user)
    {
        return $user->canAccessAllWorkspaces()
            ? Workspace::query()
            : $user->workspaces();
    }
}
