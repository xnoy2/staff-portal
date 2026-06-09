<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class OvertimeController extends Controller
{
    public function index(Request $request): Response
    {
        $user        = $request->user();
        $canReview   = $user->hasAnyRole(['admin', 'manager', 'hr']);

        $weekStart = Carbon::parse($request->get('week', now()->startOfWeek(Carbon::MONDAY)->toDateString()))
            ->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // The requesting user's own OT for the displayed week
        $myRequests = OvertimeRequest::where('user_id', $user->id)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->with('reviewer:id,name')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($r) => $this->payload($r));

        // Privileged users also get all pending OT across staff for review
        $pendingReview = $canReview
            ? OvertimeRequest::where('status', 'pending')
                ->with('user:id,name,avatar')
                ->orderBy('date')
                ->orderBy('start_time')
                ->get()
                ->map(fn ($r) => $this->payload($r, true))
            : collect();

        $staffList = $canReview
            ? User::select('id', 'name')->orderBy('name')->get()
            : collect();

        return Inertia::render('Overtime/Index', [
            'myRequests'   => $myRequests,
            'pendingReview' => $pendingReview,
            'weekStart'    => $weekStart->toDateString(),
            'weekEnd'      => $weekEnd->toDateString(),
            'canReview'    => $canReview,
            'staffList'    => $staffList,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date'          => ['required', 'date'],
            'start_time'    => ['required', 'date_format:H:i'],
            'end_time'      => ['required', 'date_format:H:i', 'after:start_time'],
            'type'          => ['required', 'in:ot,rdot'],
            'reason'        => ['nullable', 'string', 'max:500'],
            'time_entry_id' => ['nullable', 'string', 'exists:time_entries,id'],
        ]);

        OvertimeRequest::create(array_merge($data, ['user_id' => $request->user()->id]));

        return back()->with('success', 'OT request submitted.');
    }

    public function update(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        abort_unless($overtimeRequest->user_id === $request->user()->id, 403);
        abort_unless($overtimeRequest->status === 'pending', 422);

        $data = $request->validate([
            'date'       => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'type'       => ['required', 'in:ot,rdot'],
            'reason'     => ['nullable', 'string', 'max:500'],
        ]);

        $overtimeRequest->update($data);

        return back()->with('success', 'OT request updated.');
    }

    public function destroy(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $isOwner     = $overtimeRequest->user_id === $request->user()->id;
        $isPrivileged = $request->user()->hasAnyRole(['admin', 'manager', 'hr']);
        abort_unless($isOwner || $isPrivileged, 403);
        abort_unless($overtimeRequest->status === 'pending', 422);

        $overtimeRequest->delete();

        return back()->with('success', 'OT request cancelled.');
    }

    public function approve(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $data = $request->validate(['notes' => ['nullable', 'string', 'max:500']]);

        $overtimeRequest->update([
            'status'         => 'approved',
            'reviewed_by'    => $request->user()->id,
            'reviewed_at'    => now(),
            'reviewer_notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'OT request approved.');
    }

    public function reject(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $data = $request->validate(['notes' => ['required', 'string', 'max:500']]);

        $overtimeRequest->update([
            'status'         => 'rejected',
            'reviewed_by'    => $request->user()->id,
            'reviewed_at'    => now(),
            'reviewer_notes' => $data['notes'],
        ]);

        return back()->with('success', 'OT request rejected.');
    }

    private function payload(OvertimeRequest $r, bool $withUser = false): array
    {
        $data = [
            'id'             => $r->id,
            'date'           => $r->date->toDateString(),
            'day'            => $r->date->format('D'),
            'start_time'     => substr($r->start_time, 0, 5),
            'end_time'       => substr($r->end_time,   0, 5),
            'duration_hours' => $r->duration_hours,
            'type'           => $r->type,
            'reason'         => $r->reason,
            'status'         => $r->status,
            'reviewer_notes' => $r->reviewer_notes,
            'reviewed_at'    => $r->reviewed_at?->toDateString(),
            'reviewer'       => $r->reviewer ? ['name' => $r->reviewer->name] : null,
        ];

        if ($withUser && $r->relationLoaded('user') && $r->user) {
            $data['user'] = [
                'id'         => $r->user->id,
                'name'       => $r->user->name,
                'avatar_url' => $r->user->avatar_url,
            ];
        }

        return $data;
    }
}
