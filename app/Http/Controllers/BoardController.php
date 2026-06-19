<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardLabel;
use App\Models\BoardList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Ensure the user always has at least one board
        if (! Board::where('user_id', $user->id)->exists()) {
            $this->createDefaultBoard($user->id);
        }

        $boards = Board::where('user_id', $user->id)
            ->orderBy('sort_order')->orderBy('created_at')
            ->get(['id', 'name']);

        // Active board: requested ?board= or the first one
        $activeId = $request->query('board');
        $active   = $boards->firstWhere('id', $activeId) ?? $boards->first();

        $board = Board::with([
            'labels',
            'lists.cards.checklistItems',
            'lists.cards.labels',
            'lists.cards.attachments',
            'lists.cards.comments.user:id,name,avatar',
            'lists.cards.creator:id,name,avatar',
        ])->findOrFail($active->id);

        // Guard: only the owner may view
        abort_unless($board->user_id === $user->id, 403);

        // Ensure every board has the default label palette
        $this->ensureLabels($board);

        return Inertia::render('Boards/Show', [
            'boards' => $boards,
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
                    'cards'      => $list->cards->map(fn ($card) => $this->cardPayload($card)),
                ]),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $user     = $request->user();
        $maxOrder = Board::where('user_id', $user->id)->max('sort_order') ?? 0;

        $board = Board::create([
            'user_id'    => $user->id,
            'name'       => $data['name'],
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('boards.index', ['board' => $board->id])
            ->with('success', 'Board created.');
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

        $board->delete();

        return redirect()->route('boards.index')->with('success', 'Board deleted.');
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
        abort_unless($label->board->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'color' => ['sometimes', 'required', 'string', 'max:20'],
        ]);

        $label->update($data);

        return back();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function cardPayload($card): array
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
                'id'         => $c->id,
                'body'       => $c->body,
                'created_at' => $c->created_at->toIso8601String(),
                'can_delete' => $c->user_id === auth()->id(),
                'user'       => [
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

    private function createDefaultBoard(string $userId): void
    {
        $board = Board::create(['user_id' => $userId, 'name' => 'My Board', 'sort_order' => 1]);

        foreach (['To Do', 'In Progress', 'Done'] as $i => $name) {
            BoardList::create(['board_id' => $board->id, 'name' => $name, 'sort_order' => $i + 1]);
        }
    }

    private function authorizeBoard(Request $request, Board $board): void
    {
        abort_unless($board->user_id === $request->user()->id, 403);
    }

    private function authorizeList(Request $request, BoardList $list): void
    {
        abort_unless($list->board->user_id === $request->user()->id, 403);
    }
}
