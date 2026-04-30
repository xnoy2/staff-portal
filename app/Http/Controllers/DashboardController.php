<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\TimeEntry;
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
            $isManager ? $this->managerData() : $this->staffData($user)
        );

        return Inertia::render('Dashboard', $data);
    }

    private function managerData(): array
    {
        $clockedIn        = TimeEntry::active()->count();
        $pendingApprovals = TimeEntry::pending()->count();

        $todaysJobs = Job::forDate(today()->toDateString())
            ->with(['project:id,name,business', 'van:id,registration', 'staff:id,name,avatar'])
            ->orderBy('start_time')
            ->get()
            ->map(fn ($job) => [
                'id'         => $job->id,
                'title'      => $job->title,
                'status'     => $job->status,
                'start_time' => $job->start_time,
                'project'    => $job->project ? ['name' => $job->project->name, 'business' => $job->project->business] : null,
                'van'        => $job->van ? $job->van->registration : null,
                'staff_count'=> $job->staff->count(),
            ]);

        return [
            'stats' => [
                'todaysJobs'       => $todaysJobs->count(),
                'clockedInStaff'   => $clockedIn,
                'pendingApprovals' => $pendingApprovals,
                'lowStockItems'    => 0,
            ],
            'todaysJobs'       => $todaysJobs,
            'projectsByStatus' => [
                'labels' => ['Planning', 'In Progress', 'On Hold', 'Complete'],
                'data'   => [0, 0, 0, 0],
            ],
        ];
    }

    private function staffData($user): array
    {
        $activeEntry = TimeEntry::active()
            ->forUser($user->id)
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

        return [
            'activeEntry'   => $activeEntry ? [
                'id'       => $activeEntry->id,
                'clock_in' => $activeEntry->clock_in->toIso8601String(),
            ] : null,
            'weeklyHours'   => $weeklyHours,
            'recentEntries' => $recentEntries,
        ];
    }
}
