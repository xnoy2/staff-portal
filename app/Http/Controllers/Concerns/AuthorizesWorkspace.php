<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait AuthorizesWorkspace
{
    /** Any member of the workspace (or an admin) may view/edit its boards, lists, cards. */
    protected function requireMember(Request $request, ?string $workspaceId): void
    {
        abort_unless($request->user()->canAccessWorkspace($workspaceId), 403);
    }

    /**
     * Managing a workspace (settings, deletion, membership) requires an
     * admin/manager who is a member — or any admin, who can manage every workspace.
     */
    protected function requireWorkspaceManager(Request $request, ?string $workspaceId): void
    {
        $user = $request->user();

        if ($user->canAccessAllWorkspaces()) {
            return;
        }

        abort_unless($user->canManageWorkspaces() && $user->isMemberOfWorkspace($workspaceId), 403);
    }
}
