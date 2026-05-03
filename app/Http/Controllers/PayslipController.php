<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayslipController extends Controller
{
    public function mine(Request $request): Response
    {
        return $this->show($request, auth()->user());
    }

    public function show(Request $request, User $staff): Response
    {
        $viewer = auth()->user();
        abort_unless(
            $viewer->hasAnyRole(['admin', 'manager']) || $viewer->id === $staff->id,
            403
        );

        $staff->load('roles');

        // ── Locked run mode ───────────────────────────────────────────────────
        if ($request->filled('run_id')) {
            $run = PayrollRun::where('id', $request->run_id)
                ->where('user_id', $staff->id)
                ->firstOrFail();

            return Inertia::render('Staff/Payslip', [
                'staffMember'  => $this->staffData($staff),
                'period'       => [
                    'from' => $run->period_from->toDateString(),
                    'to'   => $run->period_to->toDateString(),
                ],
                'entries'      => $run->entries_snapshot,
                'regularHours' => $run->regular_hours,
                'overtimeHours'=> $run->overtime_hours,
                'totalHours'   => $run->total_hours,
                'hourlyRate'   => (float) ($run->hourly_rate ?? 0),
                'regularPay'   => $run->regular_pay,
                'overtimePay'  => $run->overtime_pay,
                'grossPay'     => $run->gross_pay,
                'hasRate'      => ! is_null($run->hourly_rate),
                'isLocked'     => true,
                'runId'        => $run->id,
                'runStatus'    => $run->status,
            ]);
        }

        // ── Live compute mode ─────────────────────────────────────────────────
        $from = $request->filled('from')
            ? \Carbon\Carbon::parse($request->from)->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $to = $request->filled('to')
            ? \Carbon\Carbon::parse($request->to)->endOfDay()
            : now()->endOfMonth()->endOfDay();

        $entries = TimeEntry::with('breaks')
            ->forUser($staff->id)
            ->whereBetween('clock_in', [$from, $to])
            ->where('status', 'approved')
            ->withSum('breaks', 'duration_minutes')
            ->orderBy('clock_in')
            ->get();

        $regularHours  = 0.0;
        $overtimeHours = 0.0;

        $entryRows = $entries->map(function ($entry) use (&$regularHours, &$overtimeHours) {
            $hours = (float) $entry->total_hours;
            $regH  = $entry->is_overtime ? min($hours, 8) : $hours;
            $otH   = $entry->is_overtime ? max(0, $hours - 8) : 0;
            $regularHours  += $regH;
            $overtimeHours += $otH;

            return [
                'date'           => $entry->clock_in->toDateString(),
                'day'            => $entry->clock_in->format('D'),
                'clock_in'       => $entry->clock_in->format('H:i'),
                'clock_out'      => $entry->clock_out?->format('H:i'),
                'break_mins'     => (int) ($entry->breaks_sum_duration_minutes ?? 0),
                'total_hours'    => round($hours, 2),
                'regular_hours'  => round($regH, 2),
                'overtime_hours' => round($otH, 2),
                'is_overtime'    => $entry->is_overtime,
            ];
        });

        $rate        = (float) ($staff->hourly_rate ?? 0);
        $regularPay  = round($regularHours * $rate, 2);
        $overtimePay = round($overtimeHours * $rate * 1.5, 2);

        return Inertia::render('Staff/Payslip', [
            'staffMember'  => $this->staffData($staff),
            'period'       => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'entries'      => $entryRows,
            'regularHours' => round($regularHours, 2),
            'overtimeHours'=> round($overtimeHours, 2),
            'totalHours'   => round($regularHours + $overtimeHours, 2),
            'hourlyRate'   => $rate,
            'regularPay'   => $regularPay,
            'overtimePay'  => $overtimePay,
            'grossPay'     => round($regularPay + $overtimePay, 2),
            'hasRate'      => ! is_null($staff->hourly_rate),
            'isLocked'     => false,
            'runId'        => null,
            'runStatus'    => null,
        ]);
    }

    private function staffData(User $staff): array
    {
        return [
            'id'               => $staff->id,
            'employee_id'      => $staff->employee_id,
            'name'             => $staff->name,
            'email'            => $staff->email,
            'roles'            => $staff->getRoleNames(),
            'hire_date'        => $staff->hire_date?->toDateString(),
            'hourly_rate'      => $staff->hourly_rate,
            'contracted_hours' => $staff->contracted_hours ?? 40,
        ];
    }
}
