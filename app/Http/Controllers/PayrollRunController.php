<?php

namespace App\Http\Controllers;

use App\Mail\PayrollSummaryMail;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Setting;
use App\Models\TimeEntry;
use App\Models\User;
use App\Notifications\PayslipApproved;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class PayrollRunController extends Controller
{
    public function index(Request $request): Response
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $cutoffDay = (int) Setting::get('payroll_cutoff_day', 25);
        $current   = PayrollRun::currentPeriod($cutoffDay);

        $runs = PayrollRun::with(['user', 'approvedBy'])
            ->when($request->filled('from'), fn ($q) => $q->where('period_from', $request->from))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('period_from', 'desc')
            ->orderByRaw("CASE WHEN status = 'draft' THEN 0 ELSE 1 END")
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($r) => [
                'id'             => $r->id,
                'period_from'    => $r->period_from->toDateString(),
                'period_to'      => $r->period_to->toDateString(),
                'status'         => $r->status,
                'staff_name'     => $r->user?->name ?? 'Deleted staff',
                'staff_id'       => $r->user?->employee_id,
                'staff_uuid'     => $r->user_id,
                'avatar_url'     => $r->user?->avatar_url,
                'total_hours'    => $r->total_hours,
                'gross_pay'      => $r->gross_pay,
                'has_rate'       => ! is_null($r->hourly_rate),
                'approved_by'    => $r->approvedBy?->name,
                'approved_at'    => $r->approved_at?->toDateString(),
                'shifts_count'   => $r->shifts_count,
                'leave_days'     => (float) ($r->leave_days ?? 0),
            ]);

        // Totals for the selected period — drives the summary bar and Approve All button
        $periodTotals = null;
        if ($request->filled('from')) {
            $agg = PayrollRun::where('period_from', $request->from)
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->selectRaw('
                    COUNT(*) as count,
                    SUM(total_hours) as total_hours,
                    SUM(gross_pay) as gross_pay,
                    SUM(CASE WHEN status = "draft"    THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count
                ')
                ->first();

            $periodTotals = [
                'count'          => (int) $agg->count,
                'total_hours'    => round((float) $agg->total_hours, 2),
                'gross_pay'      => round((float) $agg->gross_pay, 2),
                'draft_count'    => (int) $agg->draft_count,
                'approved_count' => (int) $agg->approved_count,
            ];
        }

        $draftCount = $periodTotals['draft_count'] ?? 0;

        // Available period options (distinct period_from dates from existing runs)
        $periods = PayrollRun::selectRaw('period_from, period_to')
            ->distinct()
            ->orderBy('period_from', 'desc')
            ->get()
            ->map(fn ($r) => [
                'from' => $r->period_from->toDateString(),
                'to'   => $r->period_to->toDateString(),
            ]);

        $lastAutoRun = PayrollRun::whereNull('generated_by')
            ->latest()
            ->value('created_at');

        return Inertia::render('Payroll/Index', [
            'runs'              => $runs,
            'periods'           => $periods,
            'current'           => $current,
            'cutoffDay'         => $cutoffDay,
            'draftCount'        => $draftCount,
            'periodTotals'      => $periodTotals,
            'lastAutoRun'       => $lastAutoRun?->toDateTimeString(),
            'filters'           => $request->only(['from', 'status']),
            'payrollRecipient'  => config('services.payroll.recipient_email'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($request->from)->startOfDay();
        $to   = Carbon::parse($request->to)->endOfDay();

        $staff = User::where('is_active', true)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))
            ->get();

        $generated = 0;
        $skipped   = 0;

        foreach ($staff as $user) {
            $exists = PayrollRun::where('user_id', $user->id)
                ->where('period_from', $from->toDateString())
                ->where('period_to', $to->toDateString())
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            PayrollRun::generate($user, $from, $to, auth()->id());
            $generated++;
        }

        $msg = "Generated {$generated} payslip" . ($generated !== 1 ? 's' : '') . '.';
        if ($skipped) {
            $msg .= " {$skipped} already existed and were skipped.";
        }

        return redirect()->route('payroll.index')->with('success', $msg);
    }

    public function approve(PayrollRun $run): RedirectResponse
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $run->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $run->user?->notify(new PayslipApproved(
            periodFrom: $run->period_from->toDateString(),
            periodTo:   $run->period_to->toDateString(),
            grossPay:   (float) $run->gross_pay,
        ));

        return back()->with('success', ($run->user?->name ?? 'Staff') . "'s payslip approved.");
    }

    public function approveAll(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date'],
        ]);

        $runs = PayrollRun::with('user')
            ->where('period_from', $request->from)
            ->where('period_to', $request->to)
            ->where('status', 'draft')
            ->get();

        $runs->each->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        foreach ($runs as $run) {
            $run->user?->notify(new PayslipApproved(
                periodFrom: $run->period_from->toDateString(),
                periodTo:   $run->period_to->toDateString(),
                grossPay:   (float) $run->gross_pay,
            ));
        }

        $count = $runs->count();

        return back()->with('success', "Approved {$count} payslip" . ($count !== 1 ? 's' : '') . '.');
    }

    public function updateCutoff(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $request->validate([
            'cutoff_day' => ['required', 'integer', 'min:1', 'max:28'],
        ]);

        Setting::updateOrCreate(
            ['key' => 'payroll_cutoff_day'],
            ['value' => $request->cutoff_day, 'type' => 'integer', 'group' => 'payroll']
        );

        Cache::forget('app_settings');

        return back()->with('success', 'Cut-off day updated to the ' . $request->cutoff_day . 'th.');
    }

    public function updateDeductions(Request $request, PayrollRun $run): RedirectResponse
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);
        abort_if($run->status === 'approved', 403, 'Cannot edit an approved payslip.');

        $data = $request->validate([
            'deductions'                => ['nullable', 'array'],
            'deductions.*.description'  => ['required', 'string', 'max:100'],
            'deductions.*.amount'       => ['required', 'numeric', 'min:0'],
        ]);

        $deductions    = $data['deductions'] ?? [];
        $totalDeducted = collect($deductions)->sum('amount');

        $run->update([
            'deductions' => $deductions,
            'net_pay'    => round($run->gross_pay - $totalDeducted, 2),
        ]);

        return back()->with('success', 'Deductions saved.');
    }

    public function destroy(PayrollRun $run): RedirectResponse
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);
        abort_if($run->status === 'approved', 403, 'Cannot delete an approved payslip.');

        $run->delete();

        return back()->with('success', 'Draft payslip deleted.');
    }

    public function checkPeriod(Request $request): JsonResponse
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($request->from)->startOfDay();
        $to   = Carbon::parse($request->to)->endOfDay();

        $staff = User::where('is_active', true)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))
            ->orderBy('name')
            ->get();

        $rows = $staff->map(function ($user) use ($from, $to) {
            $approved = TimeEntry::forUser($user->id)
                ->whereBetween('clock_in', [$from, $to])
                ->where('status', 'approved')
                ->count();

            $pending = TimeEntry::forUser($user->id)
                ->whereBetween('clock_in', [$from, $to])
                ->where('status', 'pending')
                ->count();

            $leaveDays = (float) LeaveRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('start_date', '<=', $to->toDateString())
                ->where('end_date', '>=', $from->toDateString())
                ->sum('days');

            $hasRun = PayrollRun::where('user_id', $user->id)
                ->where('period_from', $from->toDateString())
                ->where('period_to', $to->toDateString())
                ->exists();

            if ($hasRun) {
                $status = 'exists';
            } elseif ($approved > 0) {
                $status = 'ready';
            } elseif ($pending > 0) {
                $status = 'pending_only';
            } else {
                $status = 'no_entries';
            }

            return [
                'name'        => $user->name,
                'employee_id' => $user->employee_id ?? '—',
                'avatar_url'  => $user->avatar_url,
                'approved'    => $approved,
                'pending'     => $pending,
                'leave_days'  => $leaveDays,
                'status'      => $status,
            ];
        });

        return response()->json([
            'staff'        => $rows->values(),
            'ready'        => $rows->where('status', 'ready')->count(),
            'pending_only' => $rows->where('status', 'pending_only')->count(),
            'no_entries'   => $rows->where('status', 'no_entries')->count(),
            'exists'       => $rows->where('status', 'exists')->count(),
        ]);
    }

    public function sendPayroll(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $runs = PayrollRun::with(['user', 'approvedBy'])
            ->where('period_from', $request->from)
            ->where('period_to', $request->to)
            ->where('status', 'approved')
            ->orderByRaw('(SELECT name FROM users WHERE users.id = payroll_runs.user_id)')
            ->get();

        if ($runs->isEmpty()) {
            return back()->with('error', 'No approved payslips found for this period. Approve all payslips first.');
        }

        $recipient = config('services.payroll.recipient_email');

        Mail::to($recipient)->send(new PayrollSummaryMail(
            runs:        $runs,
            periodFrom:  $request->from,
            periodTo:    $request->to,
            sentByName:  auth()->user()->name,
        ));

        return back()->with('success', "Payroll summary for {$runs->count()} staff sent to {$recipient}.");
    }
}
