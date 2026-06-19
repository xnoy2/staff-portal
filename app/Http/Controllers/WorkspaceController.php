<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesWorkspace;
use App\Http\Controllers\Concerns\BuildsWorkspaceNav;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceMemberAdded;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    use AuthorizesWorkspace;
    use BuildsWorkspaceNav;

    public const COLORS = ['blue', 'green', 'orange', 'red', 'purple', 'pink', 'slate'];

    /** Boards grid for a workspace. */
    public function show(Request $request, Workspace $workspace): Response
    {
        $this->requireMember($request, $workspace->id);

        $workspace->load(['boards', 'members']);

        return Inertia::render('Boards/Home', [
            'nav'       => $this->workspaceNav($request->user()),
            'workspace' => $this->workspacePayload($request, $workspace),
            'boards'    => $workspace->boards->map(fn ($b) => [
                'id'   => $b->id,
                'name' => $b->name,
            ]),
        ]);
    }

    public function members(Request $request, Workspace $workspace): Response
    {
        $this->requireMember($request, $workspace->id);

        $workspace->load('members');
        $canManage = $request->user()->canManageWorkspaces();

        // Staff who could be added (active users not already members)
        $memberIds = $workspace->members->pluck('id');
        $candidates = $canManage
            ? User::where('is_active', true)
                ->whereNotIn('id', $memberIds)
                ->orderBy('name')
                ->get(['id', 'name', 'avatar'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'avatar_url' => $u->avatar_url])
            : collect();

        return Inertia::render('Boards/Members', [
            'nav'        => $this->workspaceNav($request->user()),
            'workspace'  => $this->workspacePayload($request, $workspace),
            'members'    => $workspace->members->map(fn ($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'avatar_url' => $u->avatar_url,
                'role'       => $u->pivot->role,
            ]),
            'candidates' => $candidates,
        ]);
    }

    public function settings(Request $request, Workspace $workspace): Response
    {
        $this->requireMember($request, $workspace->id);

        return Inertia::render('Boards/Settings', [
            'nav'       => $this->workspaceNav($request->user()),
            'workspace' => $this->workspacePayload($request, $workspace),
            'colors'    => self::COLORS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageWorkspaces(), 403);

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'in:' . implode(',', self::COLORS)],
        ]);

        $user     = $request->user();
        $maxOrder = $user->workspaces()->max('workspaces.sort_order') ?? 0;

        $workspace = Workspace::create([
            'owner_id'   => $user->id,
            'name'       => $data['name'],
            'color'      => $data['color'] ?? 'blue',
            'sort_order' => $maxOrder + 1,
        ]);
        $workspace->members()->attach($user->id, ['role' => 'owner']);

        return redirect()->route('workspaces.show', $workspace->id)->with('success', 'Workspace created.');
    }

    public function update(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->requireWorkspaceManager($request, $workspace->id);

        $data = $request->validate([
            'name'  => ['sometimes', 'required', 'string', 'max:100'],
            'color' => ['sometimes', 'required', 'in:' . implode(',', self::COLORS)],
        ]);

        $workspace->update($data);

        return back()->with('success', 'Workspace updated.');
    }

    public function destroy(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->requireWorkspaceManager($request, $workspace->id);

        $workspace->delete();

        // Send the user to whatever workspace remains — never auto-create one.
        $next = $this->firstWorkspace($request->user());

        return $next
            ? redirect()->route('workspaces.show', $next->id)->with('success', 'Workspace deleted.')
            : redirect()->route('boards.index')->with('success', 'Workspace deleted.');
    }

    // ── Members ───────────────────────────────────────────────────────────────

    public function storeMember(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->requireWorkspaceManager($request, $workspace->id);

        $data = $request->validate([
            'user_id' => ['required', 'string', 'exists:users,id'],
            'role'    => ['nullable', 'in:owner,member'],
        ]);

        if ($workspace->hasMember($data['user_id'])) {
            return back()->with('error', 'Already a member.');
        }

        $role = $data['role'] ?? 'member';
        $workspace->members()->attach($data['user_id'], ['role' => $role]);

        // Notify the new member (database + broadcast + email).
        // Guard so a mail failure never blocks the membership itself.
        $newMember = User::find($data['user_id']);
        try {
            $newMember?->notify(new WorkspaceMemberAdded(
                $workspace->name,
                $workspace->id,
                $request->user()->name,
                $role,
            ));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Member added.');
    }

    public function updateMember(Request $request, Workspace $workspace, User $user): RedirectResponse
    {
        $this->requireWorkspaceManager($request, $workspace->id);

        $data = $request->validate(['role' => ['required', 'in:owner,member']]);

        // Don't allow demoting the last owner
        if ($data['role'] === 'member' && $this->isLastOwner($workspace, $user->id)) {
            return back()->with('error', 'A workspace must keep at least one owner.');
        }

        $workspace->members()->updateExistingPivot($user->id, ['role' => $data['role']]);

        return back()->with('success', 'Role updated.');
    }

    public function destroyMember(Request $request, Workspace $workspace, User $user): RedirectResponse
    {
        $this->requireWorkspaceManager($request, $workspace->id);

        if ($this->isLastOwner($workspace, $user->id)) {
            return back()->with('error', 'A workspace must keep at least one owner.');
        }

        $workspace->members()->detach($user->id);

        // If a manager removed themselves, send them on to a remaining workspace.
        if ($request->user()->id === $user->id) {
            $next = $this->firstWorkspace($request->user());
            return $next
                ? redirect()->route('workspaces.show', $next->id)->with('success', 'You left the workspace.')
                : redirect()->route('boards.index')->with('success', 'You left the workspace.');
        }

        return back()->with('success', 'Member removed.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isLastOwner(Workspace $workspace, string $userId): bool
    {
        $owners = $workspace->members()->wherePivot('role', 'owner')->pluck('users.id');
        return $owners->count() === 1 && $owners->first() === $userId;
    }

    private function workspacePayload(Request $request, Workspace $workspace): array
    {
        return [
            'id'           => $workspace->id,
            'name'         => $workspace->name,
            'color'        => $workspace->color,
            'is_owner'     => $workspace->isOwner($request->user()->id),
            'can_manage'   => $request->user()->canManageWorkspaces(),
            'member_count' => $workspace->members()->count(),
        ];
    }
}
