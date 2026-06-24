<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayslipController extends Controller
{
    public function mine(Request $request): Response
    {
        return $this->show($request, auth()->user(), selfView: true);
    }

    public function show(Request $request, User $staff, bool $selfView = false): Response
    {
        $viewer = auth()->user();
        abort_unless(
            $viewer->hasAnyRole(['admin', 'manager', 'hr']) || $viewer->id === $staff->id,
            403
        );

        $staff->load('roles');

        $pastRuns = $this->pastRuns($staff);

        // ── Locked run mode ───────────────────────────────────────────────────
        if ($request->filled('run_id')) {
            $run = PayrollRun::with('approvedBy')
                ->where('id', $request->run_id)
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
                'leavePay'     => (float) ($run->leave_pay ?? 0),
                'leaveDays'    => (float) ($run->leave_days ?? 0),
                'grossPay'     => $run->gross_pay,
                'hasRate'      => ! is_null($run->hourly_rate),
                'isLocked'     => true,
                'runId'        => $run->id,
                'runStatus'    => $run->status,
                'generatedAt'  => $run->created_at->toDateString(),
                'approvedBy'   => $run->approvedBy?->name,
                'approvedAt'   => $run->approved_at?->toDateString(),
                'deductions'   => $run->deductions ?? [],
                'netPay'       => $run->net_pay,
                'canManage'    => $viewer->hasAnyRole(['admin', 'manager', 'hr']),
                'pastRuns'     => $pastRuns,
                'selfView'     => $selfView,
            ]);
        }

        // ── Live compute mode ─────────────────────────────────────────────────
        $from = $request->filled('from')
            ? \Carbon\Carbon::parse($request->from)->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $to = $request->filled('to')
            ? \Carbon\Carbon::parse($request->to)->endOfDay()
            : now()->endOfMonth()->endOfDay();

        // Shared computation: 8h cap, approved-OT split, worker-timezone formatting.
        $computed      = PayrollRun::computePeriod($staff, $from, $to);
        $entryRows     = $computed['rows'];
        $regularHours  = $computed['regularHours'];
        $overtimeHours = $computed['overtimeHours'];

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
            'pastRuns'     => $pastRuns,
            'selfView'     => $selfView,
        ]);
    }

    private function pastRuns(User $staff): array
    {
        return PayrollRun::where('user_id', $staff->id)
            ->orderBy('period_from', 'desc')
            ->get()
            ->map(fn ($r) => [
                'id'         => $r->id,
                'period_from'=> $r->period_from->toDateString(),
                'period_to'  => $r->period_to->toDateString(),
                'gross_pay'  => (float) $r->gross_pay,
                'has_rate'   => ! is_null($r->hourly_rate),
                'status'     => $r->status,
            ])
            ->all();
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
