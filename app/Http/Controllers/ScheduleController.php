<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\LeaveRequest;
use App\Models\StaffSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $weekOf    = $request->input('week', today()->toDateString());
        $weekStart = Carbon::parse($weekOf)->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // All jobs for the week (with staff loaded)
        $jobs = Job::whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->with([
                'project:id,name,customer,business',
                'van:id,registration,make,model',
                'staff:id,name,avatar',
            ])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // Job calendar — grouped by day
        $weekDays = collect(range(0, 6))->map(function ($i) use ($weekStart, $jobs) {
            $date    = $weekStart->copy()->addDays($i);
            $dateStr = $date->toDateString();

            $dayJobs = $jobs
                ->filter(fn ($j) => $j->date->toDateString() === $dateStr)
                ->map(fn ($j) => [
                    'id'          => $j->id,
                    'title'       => $j->title,
                    'start_time'  => $j->start_time,
                    'end_time'    => $j->end_time,
                    'status'      => $j->status,
                    'project'     => $j->project
                        ? ['name' => $j->project->name, 'business' => $j->project->business]
                        : null,
                    'van'         => $j->van ? $j->van->registration : null,
                    'staff_count' => $j->staff->count(),
                    'staff'       => $j->staff->map(fn ($u) => [
                        'id'         => $u->id,
                        'name'       => $u->name,
                        'avatar_url' => $u->avatar_url,
                    ])->values(),
                ])
                ->values();

            return [
                'date'    => $dateStr,
                'label'   => $date->format('D'),
                'dayNum'  => (int) $date->format('j'),
                'month'   => $date->format('M'),
                'isToday' => $date->isToday(),
                'jobs'    => $dayJobs,
            ];
        });

        // Staff schedule matrix — all active staff
        $activeStaff = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'avatar']);

        // Roster entries for the week, indexed by [user_id][date]
        $rosterEntries = StaffSchedule::whereIn('user_id', $activeStaff->pluck('id'))
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()
            ->groupBy('user_id')
            ->map(fn ($group) => $group->keyBy(fn ($e) => $e->date->toDateString()));

        $staffSchedule = $activeStaff->map(function ($staffMember) use ($jobs, $weekStart, $rosterEntries) {
            $userRoster = $rosterEntries->get($staffMember->id, collect());

            $days = collect(range(0, 6))->map(function ($i) use ($staffMember, $jobs, $weekStart, $userRoster) {
                $date  = $weekStart->copy()->addDays($i)->toDateString();
                $entry = $userRoster->get($date);

                $dayJobs = $jobs
                    ->filter(fn ($j) =>
                        $j->date->toDateString() === $date &&
                        $j->staff->contains('id', $staffMember->id)
                    )
                    ->map(fn ($j) => [
                        'id'           => $j->id,
                        'title'        => $j->title,
                        'status'       => $j->status,
                        'start_time'   => $j->start_time,
                        'project_name' => $j->project?->name,
                    ])
                    ->values();

                return [
                    'date'        => $date,
                    'scheduled'   => $entry !== null,
                    'schedule_id' => $entry?->id,
                    'shift_start' => $entry?->shift_start,
                    'shift_end'   => $entry?->shift_end,
                    'notes'       => $entry?->notes,
                    'jobs'        => $dayJobs,
                ];
            });

            return [
                'id'             => $staffMember->id,
                'name'           => $staffMember->name,
                'avatar_url'     => $staffMember->avatar_url,
                'days_scheduled' => $days->filter(fn ($d) => $d['scheduled'])->count(),
                'total_jobs'     => $days->sum(fn ($d) => count($d['jobs'])),
                'days'           => $days,
            ];
        })->values();

        return Inertia::render('Schedule/Index', [
            'weekDays'      => $weekDays,
            'weekStart'     => $weekStart->toDateString(),
            'weekEnd'       => $weekEnd->toDateString(),
            'prevWeek'      => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek'      => $weekStart->copy()->addWeek()->toDateString(),
            'todayDate'     => today()->toDateString(),
            'isPrivileged'  => $request->user()->hasAnyRole(['admin', 'manager', 'site_head']),
            'canEdit'       => $request->user()->hasAnyRole(['admin', 'manager']),
            'staffSchedule' => $staffSchedule,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager']), 403);

        $data = $request->validate([
            'user_id'     => ['required', 'exists:users,id'],
            'date'        => ['required', 'date'],
            'shift_start' => ['nullable', 'date_format:H:i'],
            'shift_end'   => ['nullable', 'date_format:H:i'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        // Conflict detection
        $conflicts = [];

        $onLeave = LeaveRequest::forUser($data['user_id'])
            ->approved()
            ->where('start_date', '<=', $data['date'])
            ->where('end_date', '>=', $data['date'])
            ->first();

        if ($onLeave) {
            $conflicts[] = 'Staff member has approved ' . $onLeave->type . ' leave on this date.';
        }

        StaffSchedule::updateOrCreate(
            ['user_id' => $data['user_id'], 'date' => $data['date']],
            [
                'shift_start' => $data['shift_start'] ?? null,
                'shift_end'   => $data['shift_end']   ?? null,
                'notes'       => $data['notes']        ?? null,
                'created_by'  => $request->user()->id,
            ]
        );

        $message = 'Schedule saved.';
        if (! empty($conflicts)) {
            $message .= ' Warning: ' . implode(' ', $conflicts);
            return back()->with('warning', $message);
        }

        return back()->with('success', $message);
    }

    public function destroyEntry(StaffSchedule $staffSchedule): RedirectResponse
    {
        abort_unless(request()->user()->hasAnyRole(['admin', 'manager']), 403);

        $staffSchedule->delete();

        return back()->with('success', 'Schedule removed.');
    }
}
