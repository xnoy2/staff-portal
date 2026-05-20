<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $request->validate([
            'from'    => ['required', 'date'],
            'to'      => ['required', 'date', 'after_or_equal:from'],
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        $from = $request->date('from');
        $to   = $request->date('to');

        // Export from approved PayrollRun records so numbers always match
        // what was reviewed and signed off, not a live re-computation.
        $runs = PayrollRun::with('user')
            ->whereBetween('period_from', [$from->toDateString(), $to->toDateString()])
            ->where('status', 'approved')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->orderBy('period_from')
            ->orderBy('user_id')
            ->get();

        $filename = 'payroll_' . $from->format('Y-m-d') . '_to_' . $to->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($runs) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Employee ID',
                'Name',
                'Period From',
                'Period To',
                'Regular Hours',
                'Overtime Hours',
                'Total Hours',
                'Hourly Rate (£)',
                'Regular Pay (£)',
                'Overtime Pay (£)',
                'Gross Pay (£)',
                'Shifts',
                'Approved By',
                'Approved At',
                'Note',
            ]);

            foreach ($runs as $run) {
                $noRate = is_null($run->hourly_rate);

                fputcsv($out, [
                    $run->user->employee_id,
                    $run->user->name,
                    $run->period_from->toDateString(),
                    $run->period_to->toDateString(),
                    number_format($run->regular_hours, 2),
                    number_format($run->overtime_hours, 2),
                    number_format($run->total_hours, 2),
                    $noRate ? 'N/A' : number_format($run->hourly_rate, 2),
                    $noRate ? 'N/A' : number_format($run->regular_pay, 2),
                    $noRate ? 'N/A' : number_format($run->overtime_pay, 2),
                    $noRate ? 'N/A' : number_format($run->gross_pay, 2),
                    $run->shifts_count,
                    $run->approvedBy?->name ?? '',
                    $run->approved_at?->toDateString() ?? '',
                    $noRate ? 'No hourly rate set' : '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
