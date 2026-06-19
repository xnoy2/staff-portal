<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Models\Workspace;

trait BuildsWorkspaceNav
{
    /**
     * The user's first workspace, or null if they belong to none.
     *
     * Workspaces are never auto-created: a user only sees a workspace after an
     * admin/manager has explicitly added them as a member.
     */
    protected function firstWorkspace(User $user): ?Workspace
    {
        return $user->workspaces()
            ->orderBy('workspaces.sort_order')
            ->orderBy('workspaces.created_at')
            ->first();
    }

    /** Left-nav data: every workspace the user belongs to, with its boards. */
    protected function workspaceNav(User $user): array
    {
        return $user->workspaces()
            ->with('boards:id,workspace_id,name')
            ->orderBy('workspaces.sort_order')
            ->orderBy('workspaces.created_at')
            ->get()
            ->map(fn ($w) => [
                'id'       => $w->id,
                'name'     => $w->name,
                'color'    => $w->color,
                'role'     => $w->pivot->role,
                'is_owner' => $w->pivot->role === 'owner',
                'boards'   => $w->boards->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
            ])->values()->toArray();
    }
}
