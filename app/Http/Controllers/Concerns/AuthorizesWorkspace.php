<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait AuthorizesWorkspace
{
    /** Any member of the workspace may view/edit its boards, lists, cards. */
    protected function requireMember(Request $request, ?string $workspaceId): void
    {
        abort_unless($request->user()->isMemberOfWorkspace($workspaceId), 403);
    }

    /**
     * Only an admin/manager who is a member may manage the workspace itself
     * (settings, deletion, and membership assignments).
     */
    protected function requireWorkspaceManager(Request $request, ?string $workspaceId): void
    {
        $user = $request->user();
        abort_unless($user->canManageWorkspaces() && $user->isMemberOfWorkspace($workspaceId), 403);
    }
}
