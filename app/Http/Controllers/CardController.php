<?php

namespace App\Http\Controllers;

use App\Models\BoardCard;
use App\Models\BoardLabel;
use App\Models\BoardList;
use App\Models\CardAttachment;
use App\Models\CardChecklistItem;
use App\Models\CardComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CardController extends Controller
{
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
        abort_unless($attachment->card->list->board->user_id === $request->user()->id, 403);

        $attachment->delete();

        return back();
    }

    // ── Comments ──────────────────────────────────────────────────────────────

    public function storeComment(Request $request, BoardCard $card): RedirectResponse
    {
        $this->authorizeCard($request, $card);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        CardComment::create([
            'card_id' => $card->id,
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

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
        abort_unless($list->board->user_id === $request->user()->id, 403);
    }

    private function authorizeCard(Request $request, BoardCard $card): void
    {
        abort_unless($card->list->board->user_id === $request->user()->id, 403);
    }

    private function authorizeItem(Request $request, CardChecklistItem $item): void
    {
        abort_unless($item->card->list->board->user_id === $request->user()->id, 403);
    }
}
