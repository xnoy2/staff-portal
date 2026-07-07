<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user      = $request->user()->load('roles');
        $isManager = $user->hasAnyRole(['admin', 'manager']);

        $data = ['isManager' => $isManager];

        $data = array_merge(
            $data,
            $isManager ? $this->managerData($user) : $this->staffData($user)
        );

        return Inertia::render('Dashboard', $data);
    }

    private function managerData($user): array
    {
        $clockedInCount = TimeEntry::active()->count();
        $pendingLeave   = LeaveRequest::where('status', 'pending')->count();
        $pendingOt      = OvertimeRequest::where('status', 'pending')->count();

        // Today's jobs
        $todaysJobs = Job::forDate(today()->toDateString())
            ->with(['project:id,name,business', 'van:id,registration', 'staff:id,name,avatar'])
            ->orderBy('start_time')
            ->get()
            ->map(fn ($job) => [
                'id'          => $job->id,
                'title'       => $job->title,
                'status'      => $job->status,
                'start_time'  => $job->start_time,
                'project'     => $job->project ? ['name' => $job->project->name, 'business' => $job->project->business] : null,
                'van'         => $job->van?->registration,
                'staff_count' => $job->staff->count(),
            ]);

        // Projects by status (real DB data)
        $projectCounts    = Project::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $projectsByStatus = [
            'labels' => ['Planning', 'Active', 'On Hold', 'Completed'],
            'data'   => [
                $projectCounts->get('planning',  0),
                $projectCounts->get('active',    0),
                $projectCounts->get('on_hold',   0),
                $projectCounts->get('completed', 0),
            ],
        ];

        // Staff currently clocked in
        $activeEntries  = TimeEntry::active()->get(['user_id', 'clock_in', 'clock_state', 'ot_type'])->keyBy('user_id');
        $clockedInStaff = User::with('roles')
            ->whereIn('id', $activeEntries->keys())
            ->orderBy('name')
            ->get(['id', 'name', 'avatar'])
            ->map(function ($u) use ($activeEntries) {
                $entry = $activeEntries[$u->id];
                return [
                    'id'          => $u->id,
                    'name'        => $u->name,
                    'avatar_url'  => $u->avatar_url,
                    'since'       => Carbon::parse($entry->clock_in)->format('H:i'),
                    'clock_in'    => Carbon::parse($entry->clock_in)->toIso8601String(),
                    'clock_state' => $entry->clock_state ?? 'working',
                    'ot_type'     => $entry->ot_type,
                    'role'        => $u->getRoleNames()->first() ?? 'staff',
                ];
            });

        // Jobs this week grouped by day (0=Mon…6=Sun) and status
        $weekStart   = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd     = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        $weekJobsRaw = Job::whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get(['date', 'status']);

        $weekJobsByDay = ['scheduled' => array_fill(0, 7, 0), 'in_progress' => array_fill(0, 7, 0), 'completed' => array_fill(0, 7, 0)];
        foreach ($weekJobsRaw as $job) {
            $dayIndex = Carbon::parse($job->date)->dayOfWeekIso - 1;
            if (isset($weekJobsByDay[$job->status][$dayIndex])) {
                $weekJobsByDay[$job->status][$dayIndex]++;
            }
        }

        // Top 8 staff by hours this week
        $staffHoursWeek = TimeEntry::approved()
            ->whereBetween('clock_in', [$weekStart, $weekEnd->copy()->endOfDay()])
            ->whereNotNull('total_hours')
            ->with('user:id,name')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($entries) => [
                'name'  => $entries->first()->user?->name ?? 'Unknown',
                'hours' => round($entries->sum('total_hours'), 1),
            ])
            ->sortByDesc('hours')
            ->take(8)
            ->values();

        // Admin's own active entry (so they can clock in/out from the dashboard)
        $myEntry = TimeEntry::active()->forUser($user->id)->with('breaks')->first();
        $myEntryPayload = null;
        if ($myEntry) {
            $activeBreak       = $myEntry->breaks->whereNull('ended_at')->first();
            $totalBreakMinutes = (int) $myEntry->breaks->whereNotNull('ended_at')->sum('duration_minutes');
            $myEntryPayload = [
                'id'                  => $myEntry->id,
                'clock_in'            => $myEntry->clock_in->toIso8601String(),
                'clock_state'         => $myEntry->clock_state ?? 'working',
                'ot_type'             => $myEntry->ot_type,
                'active_break'        => $activeBreak ? [
                    'id'         => $activeBreak->id,
                    'type'       => $activeBreak->type,
                    'started_at' => $activeBreak->started_at->toIso8601String(),
                ] : null,
                'total_break_minutes' => $totalBreakMinutes,
            ];
        }

        $todayOt = OvertimeRequest::where('user_id', $user->id)
            ->where('date', today()->toDateString())
            ->where('status', 'approved')
            ->first();

        return [
            'stats' => [
                'todaysJobs'     => $todaysJobs->count(),
                'clockedInStaff' => $clockedInCount,
                'pendingLeave'   => $pendingLeave,
                'pendingOt'      => $pendingOt,
            ],
            'todaysJobs'        => $todaysJobs,
            'projectsByStatus'  => $projectsByStatus,
            'clockedInStaff'    => $clockedInStaff,
            'weekJobsByDay'     => $weekJobsByDay,
            'staffHoursWeek'    => $staffHoursWeek,
            'activeEntry'       => $myEntryPayload,
            'todayApprovedOt'   => $todayOt ? $todayOt->type : null,
        ];
    }

    private function staffData($user): array
    {
        $activeEntry = TimeEntry::active()
            ->forUser($user->id)
            ->with('breaks')
            ->first();

        // Weekly hours Mon–Sun of current week
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd   = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $weeklyRaw = TimeEntry::forUser($user->id)
            ->approved()
            ->whereBetween('clock_in', [$weekStart, $weekEnd])
            ->whereNotNull('total_hours')
            ->get()
            ->groupBy(fn ($e) => Carbon::parse($e->clock_in)->dayOfWeekIso - 1) // 0=Mon
            ->map(fn ($group) => round($group->sum('total_hours'), 2));

        $weeklyHours = array_map(
            fn ($i) => $weeklyRaw->get($i, 0),
            range(0, 6)
        );

        $recentEntries = TimeEntry::forUser($user->id)
            ->with(['enteredBy:id,name'])
            ->orderBy('clock_in', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id'        => $e->id,
                'clock_in'  => $e->clock_in->toIso8601String(),
                'clock_out' => $e->clock_out?->toIso8601String(),
                'hours'     => $e->total_hours,
                'status'    => $e->status,
            ]);

        $activeEntryPayload = null;
        if ($activeEntry) {
            $activeBreak       = $activeEntry->breaks->whereNull('ended_at')->first();
            $totalBreakMinutes = (int) $activeEntry->breaks->whereNotNull('ended_at')->sum('duration_minutes');
            $activeEntryPayload = [
                'id'                  => $activeEntry->id,
                'clock_in'            => $activeEntry->clock_in->toIso8601String(),
                'clock_state'         => $activeEntry->clock_state ?? 'working',
                'ot_type'             => $activeEntry->ot_type,
                'active_break'        => $activeBreak ? [
                    'id'         => $activeBreak->id,
                    'type'       => $activeBreak->type,
                    'started_at' => $activeBreak->started_at->toIso8601String(),
                ] : null,
                'total_break_minutes' => $totalBreakMinutes,
            ];
        }

        // Leave balance
        $year        = now()->year;
        $entitlement = (float) ($user->annual_leave_days ?? 28);
        $used        = (float) LeaveRequest::forUser($user->id)->forYear($year)->approved()->where('type', 'annual')->sum('days');
        $pending     = (float) LeaveRequest::forUser($user->id)->forYear($year)->pending()->where('type', 'annual')->sum('days');

        // Upcoming jobs assigned to this staff member (today onwards, next 5)
        $upcomingJobs = Job::whereHas('staff', fn ($q) => $q->where('users.id', $user->id))
            ->where('date', '>=', today()->toDateString())
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->with('project:id,name,business')
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(fn ($j) => [
                'id'         => $j->id,
                'title'      => $j->title,
                'date'       => $j->date->toDateString(),
                'start_time' => $j->start_time,
                'status'     => $j->status,
                'project'    => $j->project?->name,
                'business'   => $j->project?->business,
            ]);

        // Today's approved OT request (to pre-select the toggle)
        $todayOt = OvertimeRequest::where('user_id', $user->id)
            ->where('date', today()->toDateString())
            ->where('status', 'approved')
            ->first();

        // Today's log (dashboard nudge)
        $logDate  = \Illuminate\Support\Carbon::now($user->timezone)->toDateString();
        $todayLog = \App\Models\DailyLog::where('user_id', $user->id)
            ->where('log_date', $logDate)
            ->first();

        return [
            'activeEntry'   => $activeEntryPayload,
            'weeklyHours'   => $weeklyHours,
            'recentEntries' => $recentEntries,
            'todayApprovedOt' => $todayOt ? $todayOt->type : null,
            'todayLog'      => [
                'date'   => $logDate,
                'status' => $todayLog?->status ?? 'none',
                'photos' => count($todayLog?->photos ?? []),
            ],
            'leaveBalance'  => [
                'entitlement' => $entitlement,
                'used'        => $used,
                'pending'     => $pending,
                'remaining'   => max(0, $entitlement - $used),
            ],
            'upcomingJobs'  => $upcomingJobs,
        ];
    }
}
