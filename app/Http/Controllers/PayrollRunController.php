<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\PayslipApproved;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PayrollRunController extends Controller
{
    public function index(Request $request): Response
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $cutoffDay = (int) Setting::get('payroll_cutoff_day', 25);
        $current   = PayrollRun::currentPeriod($cutoffDay);

        $runs = PayrollRun::with(['user', 'approvedBy'])
            ->when($request->filled('from'), fn ($q) => $q->where('period_from', $request->from))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('period_from', 'desc')
            ->orderByRaw("FIELD(status, 'draft', 'approved')")
            ->get()
            ->map(fn ($r) => [
                'id'             => $r->id,
                'period_from'    => $r->period_from->toDateString(),
                'period_to'      => $r->period_to->toDateString(),
                'status'         => $r->status,
                'staff_name'     => $r->user->name,
                'staff_id'       => $r->user->employee_id,
                'staff_uuid'     => $r->user_id,
                'avatar_url'     => $r->user->avatar_url,
                'total_hours'    => $r->total_hours,
                'gross_pay'      => $r->gross_pay,
                'has_rate'       => ! is_null($r->hourly_rate),
                'approved_by'    => $r->approvedBy?->name,
                'approved_at'    => $r->approved_at?->toDateString(),
                'shifts_count'   => $r->shifts_count,
            ]);

        // Available period options (distinct period_from dates from existing runs)
        $periods = PayrollRun::selectRaw('period_from, period_to')
            ->distinct()
            ->orderBy('period_from', 'desc')
            ->get()
            ->map(fn ($r) => [
                'from' => $r->period_from,
                'to'   => $r->period_to,
            ]);

        return Inertia::render('Payroll/Index', [
            'runs'       => $runs,
            'periods'    => $periods,
            'current'    => $current,
            'cutoffDay'  => $cutoffDay,
            'filters'    => $request->only(['from', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($request->from)->startOfDay();
        $to   = Carbon::parse($request->to)->endOfDay();

        $staff = User::where('is_active', true)
            ->get()
            ->filter(fn ($u) => ! $u->hasRole('admin'));

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
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager']), 403);

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

        return back()->with('success', "{$run->user->name}'s payslip approved.");
    }

    public function approveAll(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager']), 403);

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
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager']), 403);

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

    public function destroy(PayrollRun $run): RedirectResponse
    {
        abort_if(! auth()->user()->hasAnyRole(['admin', 'manager']), 403);
        abort_if($run->status === 'approved', 403, 'Cannot delete an approved payslip.');

        $run->delete();

        return back()->with('success', 'Draft payslip deleted.');
    }
}
