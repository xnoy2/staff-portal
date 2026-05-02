<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager']), 403);

        $year  = (int) $request->input('year', now()->year);
        $month = $request->input('month', ''); // '' = all year
        $userId = $request->input('user_id', '');

        // ── Attendance summary ────────────────────────────────────────────
        $attendanceQuery = TimeEntry::select(
                'user_id',
                DB::raw('COUNT(*) as total_entries'),
                DB::raw('SUM(total_hours) as total_hours'),
                DB::raw('SUM(CASE WHEN is_overtime = 1 THEN total_hours ELSE 0 END) as overtime_hours'),
                DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count')
            )
            ->with('user:id,name,employee_id,avatar')
            ->whereYear('clock_in', $year)
            ->whereNotNull('clock_out')
            ->groupBy('user_id')
            ->orderBy('total_hours', 'desc');

        if ($month !== '') {
            $attendanceQuery->whereMonth('clock_in', (int) $month);
        }

        if ($userId !== '') {
            $attendanceQuery->where('user_id', $userId);
        }

        $attendanceSummary = $attendanceQuery->get()->map(fn ($row) => [
            'user'          => $row->user ? [
                'id'          => $row->user->id,
                'name'        => $row->user->name,
                'employee_id' => $row->user->employee_id,
                'avatar_url'  => $row->user->avatar_url,
            ] : null,
            'total_entries'  => (int) $row->total_entries,
            'total_hours'    => round((float) $row->total_hours, 1),
            'overtime_hours' => round((float) $row->overtime_hours, 1),
            'pending_count'  => (int) $row->pending_count,
        ]);

        // ── Leave summary ─────────────────────────────────────────────────
        $leaveQuery = LeaveRequest::select(
                'user_id',
                DB::raw('SUM(CASE WHEN type = "annual" AND status = "approved" THEN days ELSE 0 END) as annual_used'),
                DB::raw('SUM(CASE WHEN type = "sick"   AND status = "approved" THEN days ELSE 0 END) as sick_used'),
                DB::raw('SUM(CASE WHEN type = "annual" AND status = "pending"  THEN days ELSE 0 END) as annual_pending'),
                DB::raw('SUM(CASE WHEN type = "unpaid" AND status = "approved" THEN days ELSE 0 END) as unpaid_used')
            )
            ->with('user:id,name,employee_id,avatar,annual_leave_days')
            ->whereYear('start_date', $year)
            ->groupBy('user_id')
            ->orderBy('annual_used', 'desc');

        if ($userId !== '') {
            $leaveQuery->where('user_id', $userId);
        }

        $leaveSummary = $leaveQuery->get()->map(fn ($row) => [
            'user'            => $row->user ? [
                'id'               => $row->user->id,
                'name'             => $row->user->name,
                'employee_id'      => $row->user->employee_id,
                'avatar_url'       => $row->user->avatar_url,
                'entitlement'      => $row->user->annual_leave_days ?? 28,
            ] : null,
            'annual_used'     => round((float) $row->annual_used, 1),
            'annual_pending'  => round((float) $row->annual_pending, 1),
            'sick_used'       => round((float) $row->sick_used, 1),
            'unpaid_used'     => round((float) $row->unpaid_used, 1),
        ])->map(fn ($row) => array_merge($row, [
            'annual_remaining' => max(0, ($row['user']['entitlement'] ?? 28) - $row['annual_used'] - $row['annual_pending']),
        ]));

        // ── Totals ────────────────────────────────────────────────────────
        $totals = [
            'total_hours'    => round($attendanceSummary->sum('total_hours'), 1),
            'overtime_hours' => round($attendanceSummary->sum('overtime_hours'), 1),
            'total_entries'  => $attendanceSummary->sum('total_entries'),
            'staff_count'    => $attendanceSummary->count(),
        ];

        return Inertia::render('Reports/Index', [
            'attendanceSummary' => $attendanceSummary,
            'leaveSummary'      => $leaveSummary,
            'totals'            => $totals,
            'staffList'         => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'year'              => $year,
            'month'             => $month,
            'filters'           => $request->only(['year', 'month', 'user_id']),
        ]);
    }
}
