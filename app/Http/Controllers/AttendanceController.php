<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\TimeEntryBreak;
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
        $user         = $request->user();
        $isPrivileged = $user->hasAnyRole(['admin', 'manager', 'site_head']);

        $query = TimeEntry::with(['user:id,name,avatar', 'enteredBy:id,name', 'approvedBy:id,name'])
            ->withSum('breaks', 'duration_minutes')
            ->orderBy('clock_in', 'desc');

        if (! $isPrivileged) {
            $query->forUser($user->id);
        }

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

        $activeEntry = TimeEntry::active()
            ->forUser($user->id)
            ->with('breaks')
            ->first();

        return Inertia::render('Attendance/Index', [
            'entries'      => $entries,
            'pendingCount' => $pendingCount,
            'staffList'    => $staffList,
            'isPrivileged' => $isPrivileged,
            'isManager'    => $user->hasAnyRole(['admin', 'manager']),
            'filters'      => $request->only(['user_id', 'status', 'from', 'to']),
            'activeEntry'  => $activeEntry ? $this->entryPayload($activeEntry) : null,
        ]);
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (TimeEntry::active()->forUser($user->id)->exists()) {
            return back()->with('error', 'You already have an active clock-in entry.');
        }

        $autoApprove = $user->hasAnyRole(['admin', 'manager']);

        $entry = TimeEntry::create([
            'user_id'     => $user->id,
            'clock_in'    => now(),
            'clock_state' => 'working',
            'source'      => 'self_clockin',
            'status'      => $autoApprove ? 'approved' : 'pending',
            'entered_by'  => $user->id,
            'approved_by' => $autoApprove ? $user->id : null,
            'approved_at' => $autoApprove ? now() : null,
        ]);

        return back()->with('success', 'Clocked in at ' . $entry->clock_in->format('H:i'));
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $user  = $request->user();
        $entry = TimeEntry::active()->forUser($user->id)->first();

        if (! $entry) {
            return back()->with('error', 'No active clock-in entry found.');
        }

        // Close any open break first
        $entry->breaks()->whereNull('ended_at')->each(function (TimeEntryBreak $b) {
            $b->end();
        });

        $entry->clock_out  = now();
        $entry->clock_state = 'working'; // reset; no longer active
        $entry->calculateHours();
        $entry->save();

        return back()->with('success', 'Clocked out. Duration: ' . $entry->duration_label);
    }

    public function startBreak(Request $request): RedirectResponse
    {
        $request->validate(['type' => 'required|in:break,lunch']);

        $user  = $request->user();
        $entry = TimeEntry::active()->forUser($user->id)->first();

        if (! $entry) {
            return back()->with('error', 'No active clock-in entry found.');
        }
        if ($entry->clock_state !== 'working') {
            return back()->with('error', 'Already on a break.');
        }

        $type = $request->input('type');

        $entry->update(['clock_state' => $type === 'lunch' ? 'on_lunch' : 'on_break']);

        TimeEntryBreak::create([
            'time_entry_id' => $entry->id,
            'type'          => $type,
            'started_at'    => now(),
        ]);

        $label = $type === 'lunch' ? 'Lunch break started' : 'Break started';
        return back()->with('success', $label . ' at ' . now()->format('H:i'));
    }

    public function endBreak(Request $request): RedirectResponse
    {
        $user  = $request->user();
        $entry = TimeEntry::active()->forUser($user->id)->first();

        if (! $entry) {
            return back()->with('error', 'No active clock-in entry found.');
        }

        $break = $entry->breaks()->whereNull('ended_at')->latest()->first();

        if (! $break) {
            return back()->with('error', 'No active break found.');
        }

        $break->end();
        $entry->update(['clock_state' => 'working']);

        $label = $break->type === 'lunch' ? 'Back from lunch' : 'Break ended';
        return back()->with('success', $label . ' · ' . $break->duration_minutes . ' min');
    }

    public function activeEntry(Request $request): JsonResponse
    {
        $entry = TimeEntry::active()
            ->forUser($request->user()->id)
            ->with('breaks')
            ->first();

        return response()->json($entry ? $this->entryPayload($entry) : null);
    }

    public function approve(Request $request, TimeEntry $timeEntry): RedirectResponse
    {
        $this->authorizeManagerAction($request);

        if ($timeEntry->status !== 'pending') {
            return back()->with('error', 'Entry is not pending.');
        }

        $timeEntry->approve($request->user());

        activity()->performedOn($timeEntry)->causedBy($request->user())->log('time_entry_approved');

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

        activity()->performedOn($timeEntry)->causedBy($request->user())->log('time_entry_rejected');

        return back()->with('success', 'Entry rejected.');
    }

    public function manual(Request $request): RedirectResponse
    {
        $this->authorizeManagerAction($request);

        $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'string|exists:users,id',
            'date'       => 'required|date|before_or_equal:today',
            'clock_in'   => 'required|date_format:H:i',
            'clock_out'  => 'nullable|date_format:H:i|after:clock_in',
            'notes'      => 'nullable|string|max:500',
        ]);

        $manager  = $request->user();
        $source   = count($request->input('user_ids')) > 1 ? 'bulk' : 'manual';
        $date     = $request->input('date');
        $clockIn  = \Carbon\Carbon::parse("{$date} {$request->input('clock_in')}");
        $clockOut = $request->filled('clock_out')
            ? \Carbon\Carbon::parse("{$date} {$request->input('clock_out')}")
            : null;
        $notes    = $request->input('notes');

        $count = 0;
        foreach ($request->input('user_ids') as $userId) {
            if (TimeEntry::where('user_id', $userId)
                ->whereDate('clock_in', $date)
                ->exists()) {
                continue;
            }

            $entry = TimeEntry::create([
                'user_id'     => $userId,
                'clock_in'    => $clockIn,
                'clock_out'   => $clockOut,
                'clock_state' => 'working',
                'source'      => $source,
                'notes'       => $notes,
                'status'      => 'approved',
                'entered_by'  => $manager->id,
                'approved_by' => $manager->id,
                'approved_at' => now(),
            ]);

            if ($clockOut) {
                $entry->calculateHours();
                $entry->save();
            }

            $count++;
        }

        $skipped = count($request->input('user_ids')) - $count;
        $msg     = "{$count} entr" . ($count !== 1 ? 'ies' : 'y') . ' added.';
        if ($skipped > 0) {
            $msg .= " {$skipped} skipped (entry already exists for that date).";
        }

        return back()->with('success', $msg);
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

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function entryPayload(TimeEntry $entry): array
    {
        $activeBreak      = $entry->breaks->whereNull('ended_at')->first();
        $totalBreakMinutes = (int) $entry->breaks->whereNotNull('ended_at')->sum('duration_minutes');

        return [
            'id'                  => $entry->id,
            'clock_in'            => $entry->clock_in->toIso8601String(),
            'clock_state'         => $entry->clock_state ?? 'working',
            'active_break'        => $activeBreak ? [
                'id'         => $activeBreak->id,
                'type'       => $activeBreak->type,
                'started_at' => $activeBreak->started_at->toIso8601String(),
            ] : null,
            'total_break_minutes' => $totalBreakMinutes,
        ];
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
