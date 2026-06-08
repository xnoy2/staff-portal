<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\PayrollRun;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard
     * One consolidated snapshot of headline metrics for the CEO dashboard.
     */
    public function snapshot(): JsonResponse
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd   = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $totalStaff  = User::count();
        $activeStaff = User::where('is_active', true)->count();

        $hoursThisWeek = (float) TimeEntry::approved()
            ->whereBetween('clock_in', [$weekStart, $weekEnd->copy()->endOfDay()])
            ->sum('total_hours');

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'staff' => [
                'total'    => $totalStaff,
                'active'   => $activeStaff,
                'inactive' => $totalStaff - $activeStaff,
            ],
            'attendance' => [
                'clocked_in_now'  => TimeEntry::active()->count(),
                'hours_today'     => round((float) TimeEntry::approved()->whereDate('clock_in', today())->sum('total_hours'), 2),
                'hours_this_week' => round($hoursThisWeek, 2),
                'pending_approvals' => TimeEntry::pending()->count(),
            ],
            'jobs' => [
                'today'       => Job::forDate(today()->toDateString())->count(),
                'in_progress' => Job::byStatus('in_progress')->count(),
                'scheduled'   => Job::byStatus('scheduled')->count(),
            ],
            'projects' => [
                'active'    => Project::byStatus('active')->count(),
                'planning'  => Project::byStatus('planning')->count(),
                'on_hold'   => Project::byStatus('on_hold')->count(),
                'completed' => Project::byStatus('completed')->count(),
            ],
            'approvals' => [
                'pending_leave'    => LeaveRequest::where('status', 'pending')->count(),
                'pending_overtime' => OvertimeRequest::where('status', 'pending')->count(),
            ],
            'payroll' => [
                'pending_runs'        => PayrollRun::where('status', 'draft')->count(),
                'unpaid_gross'        => round((float) PayrollRun::where('status', 'draft')->sum('gross_pay'), 2),
                'last_approved_gross' => round((float) PayrollRun::where('status', 'approved')->sum('gross_pay'), 2),
            ],
        ]);
    }
}
