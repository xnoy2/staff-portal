<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    /**
     * GET /api/attendance
     * Paginated time entries.
     *
     * Query params:
     *   from=YYYY-MM-DD   clock-in date >=
     *   to=YYYY-MM-DD     clock-in date <=
     *   status=approved   pending|approved|rejected
     *   user_id=<uuid>    single staff member
     *   per_page=50       page size (max 200)
     */
    public function index(Request $request): JsonResponse
    {
        $query = TimeEntry::query()
            ->with(['user:id,name,employee_id'])
            ->dateRange($request->input('from'), $request->input('to'))
            ->orderByDesc('clock_in');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->string('user_id'));
        }

        $perPage = min((int) $request->input('per_page', 50), 200);

        $entries = $query->paginate($perPage);

        $entries->getCollection()->transform(fn (TimeEntry $e) => [
            'id'          => $e->id,
            'staff'       => $e->user ? [
                'id'          => $e->user->id,
                'name'        => $e->user->name,
                'employee_id' => $e->user->employee_id,
            ] : null,
            'clock_in'    => $e->clock_in?->toIso8601String(),
            'clock_out'   => $e->clock_out?->toIso8601String(),
            'total_hours' => $e->total_hours !== null ? (float) $e->total_hours : null,
            'is_overtime' => $e->is_overtime,
            'ot_type'     => $e->ot_type,
            'status'      => $e->status,
            'source'      => $e->source,
        ]);

        return response()->json($entries);
    }

    /**
     * GET /api/attendance/summary
     * Live attendance snapshot + hours worked today / this week.
     */
    public function summary(): JsonResponse
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd   = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $clockedInNow = TimeEntry::active()->count();

        $hoursToday = (float) TimeEntry::approved()
            ->whereDate('clock_in', today())
            ->sum('total_hours');

        $hoursThisWeek = (float) TimeEntry::approved()
            ->whereBetween('clock_in', [$weekStart, $weekEnd->copy()->endOfDay()])
            ->sum('total_hours');

        $pendingApprovals = TimeEntry::pending()->count();

        return response()->json([
            'clocked_in_now'    => $clockedInNow,
            'hours_today'       => round($hoursToday, 2),
            'hours_this_week'   => round($hoursThisWeek, 2),
            'pending_approvals' => $pendingApprovals,
            'week_start'        => $weekStart->toDateString(),
            'week_end'          => $weekEnd->toDateString(),
        ]);
    }
}
