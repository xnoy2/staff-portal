<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesWorkspace;
use App\Http\Controllers\Concerns\BuildsWorkspaceNav;
use App\Models\Board;
use App\Models\BoardLabel;
use App\Models\BoardList;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    use AuthorizesWorkspace;
    use BuildsWorkspaceNav;

    /**
     * Entry point. Land on the first workspace the user belongs to, or show an
     * empty state if they have not been added to any workspace yet.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $ws = $this->firstWorkspace($request->user());

        if ($ws) {
            return redirect()->route('workspaces.show', $ws->id);
        }

        return Inertia::render('Boards/Empty', [
            'nav'        => $this->workspaceNav($request->user()),
            'can_create' => $request->user()->canManageWorkspaces(),
        ]);
    }

    public function show(Request $request, Board $board): Response
    {
        $this->authorizeBoard($request, $board);

        $board->load([
            'labels',
            'lists.cards.checklistItems',
            'lists.cards.labels',
            'lists.cards.attachments',
            'lists.cards.comments.user:id,name,avatar',
            'lists.cards.creator:id,name,avatar',
        ]);

        $this->ensureLabels($board);

        $workspace = $board->workspace->load('members:id,name,avatar');

        // Members available to @mention, and an id→name lookup for highlighting
        $members    = $workspace->members->map(fn ($u) => [
            'id' => $u->id, 'name' => $u->name, 'avatar_url' => $u->avatar_url,
        ])->values();
        $memberNames = $workspace->members->pluck('name', 'id');

        return Inertia::render('Boards/Show', [
            'nav'         => $this->workspaceNav($request->user()),
            'workspace'   => [
                'id'       => $workspace->id,
                'name'     => $workspace->name,
                'color'    => $workspace->color,
                'is_owner' => $workspace->isOwner($request->user()->id),
            ],
            'members'     => $members,
            'board'  => [
                'id'     => $board->id,
                'name'   => $board->name,
                'labels' => $board->labels->map(fn ($l) => [
                    'id' => $l->id, 'color' => $l->color, 'name' => $l->name,
                ]),
                'lists' => $board->lists->map(fn ($list) => [
                    'id'         => $list->id,
                    'name'       => $list->name,
                    'sort_order' => $list->sort_order,
                    'cards'      => $list->cards->map(fn ($card) => $this->cardPayload($card, $memberNames)),
                ]),
            ],
        ]);
    }

    public function store(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->requireMember($request, $workspace->id);

        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $maxOrder = $workspace->boards()->max('sort_order') ?? 0;

        $board = Board::create([
            'user_id'      => $request->user()->id,
            'workspace_id' => $workspace->id,
            'name'         => $data['name'],
            'sort_order'   => $maxOrder + 1,
        ]);

        return redirect()->route('boards.show', $board->id)->with('success', 'Board created.');
    }

    public function update(Request $request, Board $board): RedirectResponse
    {
        $this->authorizeBoard($request, $board);

        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $board->update($data);

        return back();
    }

    public function destroy(Request $request, Board $board): RedirectResponse
    {
        $this->authorizeBoard($request, $board);

        $workspaceId = $board->workspace_id;
        $board->delete();

        return redirect()->route('workspaces.show', $workspaceId)->with('success', 'Board deleted.');
    }

    // ── Lists ─────────────────────────────────────────────────────────────────

    public function storeList(Request $request, Board $board): RedirectResponse
    {
        $this->authorizeBoard($request, $board);

        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $maxOrder = $board->lists()->max('sort_order') ?? 0;

        BoardList::create([
            'board_id'   => $board->id,
            'name'       => $data['name'],
            'sort_order' => $maxOrder + 1,
        ]);

        return back();
    }

    public function updateList(Request $request, BoardList $list): RedirectResponse
    {
        $this->authorizeList($request, $list);

        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $list->update($data);

        return back();
    }

    public function destroyList(Request $request, BoardList $list): RedirectResponse
    {
        $this->authorizeList($request, $list);

        $list->delete();

        return back();
    }

    public function reorderLists(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['string', 'exists:board_lists,id'],
        ]);

        $userId = $request->user()->id;

        foreach ($data['ids'] as $i => $id) {
            BoardList::where('id', $id)
                ->whereHas('board', fn ($q) => $q->where('user_id', $userId))
                ->update(['sort_order' => $i + 1]);
        }

        return back();
    }

    public function updateLabel(Request $request, BoardLabel $label): RedirectResponse
    {
        $this->requireMember($request, $label->board->workspace_id);

        $data = $request->validate([
            'name'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'color' => ['sometimes', 'required', 'in:green,yellow,orange,red,purple,blue,pink,slate'],
        ]);

        $label->update($data);

        return back();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function cardPayload($card, $memberNames = null): array
    {
        $items = $card->checklistItems;

        return [
            'id'              => $card->id,
            'list_id'         => $card->list_id,
            'title'           => $card->title,
            'description'     => $card->description,
            'due_date'        => $card->due_date?->toIso8601String(),
            'due_done'        => $card->due_done,
            'sort_order'      => $card->sort_order,
            'created_at'      => $card->created_at?->toIso8601String(),
            'creator'         => $card->creator ? [
                'name'       => $card->creator->name,
                'avatar_url' => $card->creator->avatar_url,
            ] : null,
            'checklist_total' => $items->count(),
            'checklist_done'  => $items->where('is_done', true)->count(),
            'checklist'       => $items->map(fn ($i) => [
                'id'      => $i->id,
                'title'   => $i->title,
                'is_done' => $i->is_done,
            ])->values(),
            'labels'          => $card->labels->map(fn ($l) => [
                'id' => $l->id, 'color' => $l->color, 'name' => $l->name,
            ])->values(),
            'attachments'     => $card->attachments->map(fn ($a) => [
                'id'       => $a->id,
                'name'     => $a->name,
                'url'      => route('kb.media', $a->path),
                'mime'     => $a->mime,
                'size'     => $a->size,
                'is_image' => $a->isImage(),
            ])->values(),
            'comments'        => $card->comments->map(fn ($c) => [
                'id'             => $c->id,
                'body'           => $c->body,
                'created_at'     => $c->created_at->toIso8601String(),
                'can_delete'     => $c->user_id === auth()->id(),
                'mention_names'  => collect($c->mentions ?? [])
                    ->map(fn ($id) => $memberNames?->get($id))
                    ->filter()->values(),
                'user'           => [
                    'name'       => $c->user->name,
                    'avatar_url' => $c->user->avatar_url,
                ],
            ])->values(),
        ];
    }

    /** Seed the default Trello-style label palette for a board if it has none. */
    private function ensureLabels(Board $board): void
    {
        if ($board->labels->isNotEmpty()) {
            return;
        }

        $palette = ['green', 'yellow', 'orange', 'red', 'purple', 'blue'];
        foreach ($palette as $i => $color) {
            BoardLabel::create(['board_id' => $board->id, 'color' => $color, 'name' => null, 'sort_order' => $i + 1]);
        }
        $board->load('labels');
    }

    private function authorizeBoard(Request $request, Board $board): void
    {
        $this->requireMember($request, $board->workspace_id);
    }

    private function authorizeList(Request $request, BoardList $list): void
    {
        $this->requireMember($request, $list->board->workspace_id);
    }
}
