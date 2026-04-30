<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $user      = $request->user();
        $isPrivileged = $user->hasAnyRole(['admin', 'manager', 'site_head']);

        $query = TimeEntry::with(['user:id,name,avatar', 'enteredBy:id,name', 'approvedBy:id,name'])
            ->orderBy('clock_in', 'desc');

        // Staff sees only their own entries
        if (! $isPrivileged) {
            $query->forUser($user->id);
        }

        // Filters
        if ($request->filled('user_id') && $isPrivileged) {
            $query->forUser($request->input('user_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        $query->dateRange($request->input('from'), $request->input('to'));

        $entries = $query->paginate(25)->withQueryString();

        $pendingCount = TimeEntry::pending()->count();

        $staffList = $isPrivileged
            ? User::select('id', 'name')->orderBy('name')->get()
            : collect();

        return Inertia::render('Attendance/Index', [
            'entries'      => $entries,
            'pendingCount' => $pendingCount,
            'staffList'    => $staffList,
            'isPrivileged' => $isPrivileged,
            'isManager'    => $user->hasAnyRole(['admin', 'manager']),
            'filters'      => $request->only(['user_id', 'status', 'from', 'to']),
        ]);
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $user = $request->user();

        $existing = TimeEntry::active()->forUser($user->id)->first();
        if ($existing) {
            return back()->with('error', 'You already have an active clock-in entry.');
        }

        $autoApprove = $user->hasAnyRole(['admin', 'manager']);

        $entry = TimeEntry::create([
            'user_id'    => $user->id,
            'clock_in'   => now(),
            'source'     => 'self_clockin',
            'status'     => $autoApprove ? 'approved' : 'pending',
            'entered_by' => $user->id,
            'approved_by' => $autoApprove ? $user->id : null,
            'approved_at' => $autoApprove ? now() : null,
        ]);

        return back()->with('success', 'Clocked in at ' . $entry->clock_in->toIso8601String());
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $user  = $request->user();
        $entry = TimeEntry::active()->forUser($user->id)->first();

        if (! $entry) {
            return back()->with('error', 'No active clock-in entry found.');
        }

        $entry->clock_out = now();
        $entry->calculateHours();
        $entry->save();

        return back()->with('success', 'Clocked out. Duration: ' . $entry->duration_label);
    }

    public function activeEntry(Request $request): JsonResponse
    {
        $entry = TimeEntry::active()
            ->forUser($request->user()->id)
            ->first();

        return response()->json($entry);
    }

    public function approve(Request $request, TimeEntry $timeEntry): RedirectResponse
    {
        $this->authorizeManagerAction($request);

        if ($timeEntry->status !== 'pending') {
            return back()->with('error', 'Entry is not pending.');
        }

        $timeEntry->approve($request->user());

        activity()
            ->performedOn($timeEntry)
            ->causedBy($request->user())
            ->log('time_entry_approved');

        return back()->with('success', 'Entry approved.');
    }

    public function reject(Request $request, TimeEntry $timeEntry): RedirectResponse
    {
        $this->authorizeManagerAction($request);

        $request->validate(['reason' => 'nullable|string|max:500']);

        if ($timeEntry->status !== 'pending') {
            return back()->with('error', 'Entry is not pending.');
        }

        $timeEntry->reject($request->user(), $request->input('reason'));

        activity()
            ->performedOn($timeEntry)
            ->causedBy($request->user())
            ->log('time_entry_rejected');

        return back()->with('success', 'Entry rejected.');
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $this->authorizeManagerAction($request);

        $request->validate(['ids' => 'required|array', 'ids.*' => 'string|exists:time_entries,id']);

        $count = TimeEntry::whereIn('id', $request->input('ids'))
            ->where('status', 'pending')
            ->count();

        TimeEntry::whereIn('id', $request->input('ids'))
            ->where('status', 'pending')
            ->update([
                'status'      => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

        return back()->with('success', "{$count} entries approved.");
    }

    private function authorizeManagerAction(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyRole(['admin', 'manager']),
            403,
            'Only managers can approve or reject time entries.'
        );
    }
}
