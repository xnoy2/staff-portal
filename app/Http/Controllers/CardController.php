<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesWorkspace;
use App\Models\BoardCard;
use App\Models\BoardLabel;
use App\Models\BoardList;
use App\Models\CardAttachment;
use App\Models\CardChecklistItem;
use App\Models\CardComment;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\CardMentioned;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CardController extends Controller
{
    use AuthorizesWorkspace;

    public function store(Request $request, BoardList $list): RedirectResponse
    {
        $this->authorizeList($request, $list);

        $data = $request->validate(['title' => ['required', 'string', 'max:500']]);

        $maxOrder = $list->cards()->max('sort_order') ?? 0;

        BoardCard::create([
            'list_id'    => $list->id,
            'title'      => $data['title'],
            'sort_order' => $maxOrder + 1,
            'created_by' => $request->user()->id,
        ]);

        return back();
    }

    public function update(Request $request, BoardCard $card): RedirectResponse
    {
        $this->authorizeCard($request, $card);

        $data = $request->validate([
            'title'       => ['sometimes', 'required', 'string', 'max:500'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'due_date'    => ['sometimes', 'nullable', 'date'],
            'due_done'    => ['sometimes', 'boolean'],
        ]);

        $card->update($data);

        return back();
    }

    public function destroy(Request $request, BoardCard $card): RedirectResponse
    {
        $this->authorizeCard($request, $card);

        $card->delete();

        return back();
    }

    /**
     * Move a card to a (possibly different) list and re-number that list.
     * `ids` = the ordered card ids of the destination list.
     */
    public function move(Request $request, BoardCard $card): RedirectResponse
    {
        $this->authorizeCard($request, $card);

        $data = $request->validate([
            'list_id' => ['required', 'string', 'exists:board_lists,id'],
            'ids'     => ['required', 'array'],
            'ids.*'   => ['string', 'exists:board_cards,id'],
        ]);

        // Destination list must belong to the same user
        $destList = BoardList::findOrFail($data['list_id']);
        $this->authorizeList($request, $destList);

        if ($card->list_id !== $destList->id) {
            $card->update(['list_id' => $destList->id]);
        }

        foreach ($data['ids'] as $i => $id) {
            BoardCard::where('id', $id)
                ->whereHas('list.board', fn ($q) => $q->where('user_id', $request->user()->id))
                ->update(['sort_order' => $i + 1]);
        }

        return back();
    }

    // ── Checklist items ───────────────────────────────────────────────────────

    public function storeChecklistItem(Request $request, BoardCard $card): RedirectResponse
    {
        $this->authorizeCard($request, $card);

        $data = $request->validate(['title' => ['required', 'string', 'max:500']]);

        $maxOrder = $card->checklistItems()->max('sort_order') ?? 0;

        CardChecklistItem::create([
            'card_id'    => $card->id,
            'title'      => $data['title'],
            'sort_order' => $maxOrder + 1,
        ]);

        return back();
    }

    public function updateChecklistItem(Request $request, CardChecklistItem $item): RedirectResponse
    {
        $this->authorizeItem($request, $item);

        $data = $request->validate([
            'title'   => ['sometimes', 'required', 'string', 'max:500'],
            'is_done' => ['sometimes', 'boolean'],
        ]);

        $item->update($data);

        return back();
    }

    public function destroyChecklistItem(Request $request, CardChecklistItem $item): RedirectResponse
    {
        $this->authorizeItem($request, $item);

        $item->delete();

        return back();
    }

    // ── Labels ────────────────────────────────────────────────────────────────

    public function toggleLabel(Request $request, BoardCard $card, BoardLabel $label): RedirectResponse
    {
        $this->authorizeCard($request, $card);
        abort_unless($label->board_id === $card->list->board_id, 403);

        $card->labels()->toggle($label->id);

        return back();
    }

    // ── Attachments ───────────────────────────────────────────────────────────

    public function storeAttachment(Request $request, BoardCard $card): RedirectResponse
    {
        $this->authorizeCard($request, $card);

        $request->validate([
            'file' => ['required', 'file', 'max:20480',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt,zip,ppt,pptx'],
        ]);

        $file = $request->file('file');
        $disk = config('filesystems.disks.r2.bucket') ? 'r2' : 'public';
        $path = $file->store('cards/attachments', $disk);

        CardAttachment::create([
            'card_id'     => $card->id,
            'name'        => $file->getClientOriginalName(),
            'path'        => $path,
            'mime'        => $file->getMimeType(),
            'size'        => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back();
    }

    public function destroyAttachment(Request $request, CardAttachment $attachment): RedirectResponse
    {
        $this->requireMember($request, $attachment->card->list->board->workspace_id);

        $attachment->delete();

        return back();
    }

    // ── Comments ──────────────────────────────────────────────────────────────

    public function storeComment(Request $request, BoardCard $card): RedirectResponse
    {
        $this->authorizeCard($request, $card);

        $data = $request->validate([
            'body'       => ['required', 'string', 'max:2000'],
            'mentions'   => ['nullable', 'array'],
            'mentions.*' => ['string', 'exists:users,id'],
        ]);

        $card->loadMissing('list.board');
        $workspaceId = $card->list->board->workspace_id;
        $boardId     = $card->list->board_id;
        $author      = $request->user();

        // Keep only mentions that are real members of the workspace (excluding the author)
        $memberIds  = Workspace::find($workspaceId)?->members()->pluck('users.id') ?? collect();
        $mentionIds = collect($data['mentions'] ?? [])
            ->unique()
            ->filter(fn ($id) => $id !== $author->id && $memberIds->contains($id))
            ->values();

        CardComment::create([
            'card_id'  => $card->id,
            'user_id'  => $author->id,
            'body'     => $data['body'],
            'mentions' => $mentionIds->isEmpty() ? null : $mentionIds->all(),
        ]);

        // Notify each mentioned member (in-app + broadcast + email via Resend)
        foreach ($mentionIds as $id) {
            try {
                User::find($id)?->notify(new CardMentioned(
                    $author->name,
                    $card->title,
                    $boardId,
                    $card->id,
                    $data['body'],
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back();
    }

    public function destroyComment(Request $request, CardComment $comment): RedirectResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->delete();

        return back();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authorizeList(Request $request, BoardList $list): void
    {
        $this->requireMember($request, $list->board->workspace_id);
    }

    private function authorizeCard(Request $request, BoardCard $card): void
    {
        $this->requireMember($request, $card->list->board->workspace_id);
    }

    private function authorizeItem(Request $request, CardChecklistItem $item): void
    {
        $this->requireMember($request, $item->card->list->board->workspace_id);
    }
}
