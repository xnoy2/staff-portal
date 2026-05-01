<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
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

        // Merge jobs + leave events by date
        $allDates = $jobs->keys()->merge($leaveEvents->keys())->unique()->sort()->values();
        $events   = $allDates->mapWithKeys(fn ($date) => [
            $date => array_merge(
                $jobs->get($date, collect())->toArray(),
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
}
