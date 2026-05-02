<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequest;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function index(Request $request): Response
    {
        $user         = $request->user();
        $isPrivileged = $user->hasAnyRole(['admin', 'manager']);
        $year         = (int) ($request->input('year', now()->year));

        $query = LeaveRequest::with(['user:id,name,avatar', 'reviewedBy:id,name'])
            ->orderBy('start_date', 'desc');

        if (! $isPrivileged) {
            $query->forUser($user->id);
        } elseif ($request->filled('user_id')) {
            $query->forUser($request->input('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $query->whereYear('start_date', $year);

        $leaves = $query->paginate(20)->withQueryString()
            ->through(fn ($l) => $this->format($l));

        $pendingCount = $isPrivileged
            ? LeaveRequest::pending()->count()
            : LeaveRequest::pending()->forUser($user->id)->count();

        // Balance summary for the viewed user (self or filtered)
        $summaryUserId = $isPrivileged && $request->filled('user_id')
            ? $request->input('user_id')
            : $user->id;

        $summary = $this->buildSummary($summaryUserId, $year);

        return Inertia::render('Leave/Index', [
            'leaves'       => $leaves,
            'pendingCount' => $pendingCount,
            'summary'      => $summary,
            'staffList'    => $isPrivileged ? User::where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
            'isPrivileged' => $isPrivileged,
            'year'         => $year,
            'filters'      => $request->only(['user_id', 'status', 'type', 'year']),
        ]);
    }

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $user         = $request->user();
        $isPrivileged = $user->hasAnyRole(['admin', 'manager']);

        // Who is the leave for?
        $targetId = ($isPrivileged && $request->filled('user_id'))
            ? $request->input('user_id')
            : $user->id;

        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);
        $days  = LeaveRequest::workingDays($start, $end);

        // Balance check for annual leave
        if ($request->type === 'annual') {
            $targetUser  = User::findOrFail($targetId);
            $year        = $start->year;
            $used        = LeaveRequest::forUser($targetId)->forYear($year)->approved()->where('type', 'annual')->sum('days');
            $pending     = LeaveRequest::forUser($targetId)->forYear($year)->pending()->where('type', 'annual')->sum('days');
            $remaining   = $targetUser->annual_leave_days - (float) $used - (float) $pending;

            if ($days > $remaining) {
                $label = $targetId !== $user->id ? "This staff member has" : "You have";
                return back()->withErrors([
                    'days' => "{$label} {$remaining} annual leave day(s) remaining (including pending). Requested {$days} day(s).",
                ])->withInput();
            }
        }

        // Admin/manager leave (own or for others) → auto-approved; site head/staff → pending
        $autoApprove = $user->hasAnyRole(['admin', 'manager']);

        $leave = LeaveRequest::create([
            'user_id'     => $targetId,
            'type'        => $request->type,
            'start_date'  => $start,
            'end_date'    => $end,
            'days'        => $days,
            'reason'      => $request->reason,
            'status'      => $autoApprove ? 'approved' : 'pending',
            'created_by'  => $user->id,
            'reviewed_by' => $autoApprove ? $user->id : null,
            'reviewed_at' => $autoApprove ? now() : null,
        ]);

        $msg = $autoApprove
            ? "{$days} day(s) of leave approved."
            : "Leave request submitted — awaiting approval.";

        return back()->with('success', $msg);
    }

    public function approve(Request $request, LeaveRequest $leave): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager']), 403);

        $request->validate(['review_notes' => 'nullable|string|max:500']);

        $leave->update([
            'status'       => 'approved',
            'reviewed_by'  => $request->user()->id,
            'reviewed_at'  => now(),
            'review_notes' => $request->review_notes,
        ]);

        return back()->with('success', 'Leave approved.');
    }

    public function reject(Request $request, LeaveRequest $leave): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager']), 403);

        $request->validate(['review_notes' => 'nullable|string|max:500']);

        $leave->update([
            'status'       => 'rejected',
            'reviewed_by'  => $request->user()->id,
            'reviewed_at'  => now(),
            'review_notes' => $request->review_notes,
        ]);

        return back()->with('success', 'Leave rejected.');
    }

    public function destroy(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $user = $request->user();
        $canDelete = $user->hasAnyRole(['admin', 'manager'])
            || ($leave->user_id === $user->id && $leave->status === 'pending');

        abort_unless($canDelete, 403);

        $leave->delete();

        return back()->with('success', 'Leave request cancelled.');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function format(LeaveRequest $l): array
    {
        return [
            'id'           => $l->id,
            'user'         => $l->user ? ['id' => $l->user->id, 'name' => $l->user->name, 'avatar_url' => $l->user->avatar_url] : null,
            'type'         => $l->type,
            'start_date'   => $l->start_date->toDateString(),
            'end_date'     => $l->end_date->toDateString(),
            'days'         => (float) $l->days,
            'reason'       => $l->reason,
            'status'       => $l->status,
            'review_notes' => $l->review_notes,
            'reviewed_by'  => $l->reviewedBy?->name,
            'reviewed_at'  => $l->reviewed_at?->toDateString(),
            'created_at'   => $l->created_at->toDateString(),
        ];
    }

    private function buildSummary(string $userId, int $year): array
    {
        $targetUser = User::find($userId);
        $entitlement = $targetUser?->annual_leave_days ?? 28;

        $used = LeaveRequest::forUser($userId)
            ->forYear($year)
            ->approved()
            ->whereIn('type', ['annual'])
            ->sum('days');

        $sickDays = LeaveRequest::forUser($userId)
            ->forYear($year)
            ->approved()
            ->where('type', 'sick')
            ->sum('days');

        $pending = LeaveRequest::forUser($userId)
            ->forYear($year)
            ->pending()
            ->where('type', 'annual')
            ->sum('days');

        return [
            'entitlement' => $entitlement,
            'used'        => (float) $used,
            'pending'     => (float) $pending,
            'remaining'   => max(0, $entitlement - (float) $used),
            'sick_days'   => (float) $sickDays,
            'user_name'   => $targetUser?->name,
        ];
    }
}
