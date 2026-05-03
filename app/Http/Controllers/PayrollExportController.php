<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        $from = $request->date('from')->startOfDay();
        $to   = $request->date('to')->endOfDay();

        $query = TimeEntry::with(['user'])
            ->whereBetween('clock_in', [$from, $to])
            ->where('status', 'approved')
            ->withSum('breaks', 'duration_minutes');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Group entries by user
        $byUser = $query->get()->groupBy('user_id');

        $filename = 'payroll_' . $from->format('Y-m-d') . '_to_' . $to->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($byUser, $from, $to) {
            $out = fopen('php://output', 'w');

            // Header row
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
                'Note',
            ]);

            foreach ($byUser as $userId => $entries) {
                $user = $entries->first()->user;

                $regularHours  = 0;
                $overtimeHours = 0;
                $shifts        = $entries->count();
                $noRate        = is_null($user->hourly_rate);

                foreach ($entries as $entry) {
                    $hours = (float) $entry->total_hours;

                    if ($entry->is_overtime) {
                        // First 8 hours are regular; remainder is overtime
                        $regularHours  += min($hours, 8);
                        $overtimeHours += max(0, $hours - 8);
                    } else {
                        $regularHours += $hours;
                    }
                }

                $rate         = (float) ($user->hourly_rate ?? 0);
                $regularPay   = round($regularHours * $rate, 2);
                $overtimePay  = round($overtimeHours * $rate * 1.5, 2);
                $grossPay     = round($regularPay + $overtimePay, 2);

                fputcsv($out, [
                    $user->employee_id,
                    $user->name,
                    $from->toDateString(),
                    $to->toDateString(),
                    number_format($regularHours, 2),
                    number_format($overtimeHours, 2),
                    number_format($regularHours + $overtimeHours, 2),
                    $noRate ? 'N/A' : number_format($rate, 2),
                    $noRate ? 'N/A' : number_format($regularPay, 2),
                    $noRate ? 'N/A' : number_format($overtimePay, 2),
                    $noRate ? 'N/A' : number_format($grossPay, 2),
                    $shifts,
                    $noRate ? 'No hourly rate set' : '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
