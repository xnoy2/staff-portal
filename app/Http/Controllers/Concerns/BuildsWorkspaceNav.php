<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Models\Workspace;

trait BuildsWorkspaceNav
{
    /** Ensure the user belongs to at least one workspace (personal default). */
    protected function ensureWorkspace(User $user): Workspace
    {
        $ws = $user->workspaces()->orderBy('workspaces.sort_order')->first();
        if ($ws) {
            return $ws;
        }

        $first = strtok($user->name ?? 'My', ' ');
        $ws = Workspace::create([
            'owner_id'   => $user->id,
            'name'       => $first . "'s Workspace",
            'color'      => 'blue',
            'sort_order' => 1,
        ]);
        $ws->members()->attach($user->id, ['role' => 'owner']);

        return $ws;
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
