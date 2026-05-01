<?php

namespace App\Http\Controllers;

use App\Models\Job;
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

        $jobs = Job::whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->with([
                'project:id,name,customer,business',
                'van:id,registration,make,model',
                'staff:id,name,avatar',
            ])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

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

        // Build staff schedule matrix from the already-loaded jobs
        $allStaff = $jobs->flatMap->staff->unique('id')->sortBy('name');

        $staffSchedule = $allStaff->map(function ($staffMember) use ($jobs, $weekStart) {
            $days = collect(range(0, 6))->map(function ($i) use ($staffMember, $jobs, $weekStart) {
                $date    = $weekStart->copy()->addDays($i)->toDateString();
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

                return ['date' => $date, 'jobs' => $dayJobs];
            });

            return [
                'id'         => $staffMember->id,
                'name'       => $staffMember->name,
                'avatar_url' => $staffMember->avatar_url,
                'total_jobs' => $days->sum(fn ($d) => count($d['jobs'])),
                'days'       => $days,
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
            'staffSchedule' => $staffSchedule,
        ]);
    }
}
