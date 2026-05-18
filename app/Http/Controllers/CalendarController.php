<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\LeaveRequest;
use App\Models\Project;
use App\Services\BgrApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $user    = $request->user();
        $monthOf = $request->input('month', today()->format('Y-m'));
        $start   = Carbon::parse($monthOf . '-01')->startOfMonth();
        $end     = $start->copy()->endOfMonth();

        // Jobs for the month
        $jobs = Job::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with(['project:id,name', 'staff:id,name'])
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($j) => $j->date->toDateString())
            ->map(fn ($g) => $g->map(fn ($j) => [
                'id'          => $j->id,
                'kind'        => 'job',
                'title'       => $j->title,
                'status'      => $j->status,
                'start_time'  => $j->start_time,
                'project'     => $j->project?->name,
                'staff_count' => $j->staff->count(),
            ])->values());

        // Leave requests overlapping the month (approved + pending)
        $leaves = LeaveRequest::whereIn('status', ['approved', 'pending'])
            ->where(fn ($q) => $q
                ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(fn ($q2) => $q2
                    ->where('start_date', '<=', $start->toDateString())
                    ->where('end_date', '>=', $end->toDateString())
                )
            )
            ->with('user:id,name')
            ->get();

        // Expand multi-day leaves into one entry per calendar day
        $leaveEvents = $leaves->flatMap(function ($leave) use ($start, $end) {
            $cur   = ($leave->start_date->lt($start) ? $start : $leave->start_date)->copy();
            $until = ($leave->end_date->gt($end)     ? $end   : $leave->end_date)->copy();
            $days  = [];
            while ($cur->lte($until)) {
                $days[] = [
                    'date'   => $cur->toDateString(),
                    'kind'   => 'leave',
                    'title'  => $leave->user->name,
                    'type'   => $leave->type,
                    'status' => $leave->status,
                ];
                $cur->addDay();
            }
            return $days;
        })->groupBy('date')->map->values();

        // Local projects overlapping the month that the user is assigned to
        $localProjectQuery = Project::whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->whereNull('deleted_at');

        if (! $user->hasRole(['admin', 'manager', 'hr'])) {
            $localProjectQuery->whereHas('staff', fn ($q) => $q->where('users.id', $user->id));
        }

        $localProjects = $localProjectQuery->get(['id', 'name', 'status', 'start_date', 'end_date'])
            ->map(fn ($p) => [
                'id'         => $p->id,
                'source'     => 'local',
                'title'      => $p->name,
                'status'     => $p->status,
                'start_date' => $p->start_date->toDateString(),
                'end_date'   => $p->end_date->toDateString(),
            ]);

        // BGR projects (external API) — only when the user has a connected BGR account
        $bgrProjects = $this->fetchBgrProjects($user, $start, $end);

        // Combine local + BGR project list and expand into per-day events
        $allProjectRecords = $localProjects->concat($bgrProjects);

        $projectEvents = $allProjectRecords->flatMap(function ($project) use ($start, $end) {
            try {
                $projStart = Carbon::parse($project['start_date']);
                $projEnd   = Carbon::parse($project['end_date']);
            } catch (\Throwable) {
                return [];
            }

            $cur   = ($projStart->lt($start) ? $start : $projStart)->copy();
            $until = ($projEnd->gt($end)     ? $end   : $projEnd)->copy();
            $days  = [];

            while ($cur->lte($until)) {
                $days[] = [
                    'date'       => $cur->toDateString(),
                    'kind'       => 'project',
                    'source'     => $project['source'],
                    'id'         => $project['id'],
                    'title'      => $project['title'],
                    'status'     => $project['status'],
                    'is_start'   => $cur->toDateString() === $projStart->toDateString(),
                    'is_end'     => $cur->toDateString() === $projEnd->toDateString(),
                    'start_date' => $projStart->toDateString(),
                    'end_date'   => $projEnd->toDateString(),
                ];
                $cur->addDay();
            }

            return $days;
        })->groupBy('date')->map->values();

        // Merge all event sources by date
        $allDates = $jobs->keys()
            ->merge($projectEvents->keys())
            ->merge($leaveEvents->keys())
            ->unique()->sort()->values();

        $events = $allDates->mapWithKeys(fn ($date) => [
            $date => array_merge(
                $jobs->get($date, collect())->toArray(),
                $projectEvents->get($date, collect())->toArray(),
                $leaveEvents->get($date, collect())->toArray(),
            ),
        ]);

        return Inertia::render('Calendar/Index', [
            'month'     => $monthOf,
            'monthName' => $start->format('F Y'),
            'prevMonth' => $start->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $start->copy()->addMonthNoOverflow()->format('Y-m'),
            'todayDate' => today()->toDateString(),
            'events'    => $events,
        ]);
    }

    /**
     * Fetch BGR API projects for the user and return only those overlapping the given month.
     * Returns an empty collection if the user has no BGR token or the API call fails.
     */
    private function fetchBgrProjects($user, Carbon $start, Carbon $end): Collection
    {
        if (! $user->bgr_token) {
            return collect();
        }

        try {
            $service  = new BgrApiService($user->bgr_token);
            $response = $service->getProjects(['per_page' => 200]);
            $raw      = $response['data'] ?? [];

            if (empty($raw) || ! is_array($raw)) {
                return collect();
            }

            return collect($raw)
                ->filter(fn ($p) => ! empty($p['start_date']) && ! empty($p['estimated_completion']))
                ->filter(function ($p) use ($start, $end) {
                    try {
                        $ps = Carbon::parse($p['start_date']);
                        $pe = Carbon::parse($p['estimated_completion']);
                        return $ps->lte($end) && $pe->gte($start);
                    } catch (\Throwable) {
                        return false;
                    }
                })
                ->map(fn ($p) => [
                    'id'         => $p['id'],
                    'source'     => 'bgr',
                    'title'      => $p['name'] ?? $p['title'] ?? 'BGR Project',
                    'status'     => $p['status'] ?? null,
                    'start_date' => $p['start_date'],
                    'end_date'   => $p['estimated_completion'],
                ])
                ->values();
        } catch (\Throwable) {
            return collect();
        }
    }
}
