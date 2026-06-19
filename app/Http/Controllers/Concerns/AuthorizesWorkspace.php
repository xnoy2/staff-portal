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

    /** Only the workspace owner may manage the workspace itself (settings, members). */
    protected function requireOwner(Request $request, ?string $workspaceId): void
    {
        abort_unless($request->user()->ownsWorkspace($workspaceId), 403);
    }
}
