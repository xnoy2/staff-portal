<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PayrollSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public string  $periodFrom;
    public string  $periodTo;
    public string  $sentByName;
    public float   $totalHours;
    public float   $totalGross;
    public float   $totalNet;
    public int     $staffCount;
    public array   $rows;
    public string  $csvFilename;

    private string $csvContent;

    public function __construct(
        Collection $runs,
        string $periodFrom,
        string $periodTo,
        string $sentByName,
    ) {
        $this->periodFrom = $periodFrom;
        $this->periodTo   = $periodTo;
        $this->sentByName = $sentByName;
        $this->staffCount = $runs->count();
        $this->csvFilename = 'payroll_' . $periodFrom . '_to_' . $periodTo . '.csv';

        // Pre-process rows for the view
        $this->rows = $runs->map(function ($run) {
            $deductions = collect($run->deductions ?? [])->sum('amount');
            $netPay     = $run->net_pay ?? ($run->gross_pay - $deductions);
            return [
                'employee_id'    => $run->user->employee_id ?? '—',
                'name'           => $run->user->name,
                'shifts'         => $run->shifts_count,
                'regular_hours'  => round($run->regular_hours, 2),
                'overtime_hours' => round($run->overtime_hours, 2),
                'total_hours'    => round($run->total_hours, 2),
                'has_rate'       => ! is_null($run->hourly_rate),
                'hourly_rate'    => $run->hourly_rate,
                'gross_pay'      => round($run->gross_pay, 2),
                'deductions'     => round($deductions, 2),
                'net_pay'        => round($netPay, 2),
                'approved_by'    => $run->approvedBy?->name ?? '—',
                'approved_at'    => $run->approved_at?->format('d M Y') ?? '—',
            ];
        })->values()->toArray();

        $this->totalHours = round($runs->sum('total_hours'), 2);
        $this->totalGross = round($runs->sum('gross_pay'), 2);
        $this->totalNet   = round($runs->sum(fn ($r) => $r->net_pay ?? $r->gross_pay), 2);
        $this->csvContent = $this->buildCsv($runs);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payroll Summary — ' . $this->periodFrom . ' to ' . $this->periodTo,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.payroll-summary');
    }

    public function attachments(): array
    {
        $csv = $this->csvContent;

        return [
            Attachment::fromData(fn () => $csv, $this->csvFilename)
                ->withMime('text/csv'),
        ];
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function buildCsv(Collection $runs): string
    {
        $lines = [];
        $lines[] = implode(',', [
            'Employee ID', 'Name',
            'Period From', 'Period To',
            'Shifts',
            'Regular Hours', 'Overtime Hours', 'Total Hours',
            'Hourly Rate (£)', 'Regular Pay (£)', 'Overtime Pay (£)',
            'Gross Pay (£)', 'Deductions (£)', 'Net Pay (£)',
            'Approved By', 'Approved At',
        ]);

        foreach ($runs as $run) {
            $deductions = collect($run->deductions ?? [])->sum('amount');
            $noRate     = is_null($run->hourly_rate);

            $lines[] = implode(',', [
                $run->user->employee_id ?? '',
                '"' . str_replace('"', '""', $run->user->name) . '"',
                $run->period_from->toDateString(),
                $run->period_to->toDateString(),
                $run->shifts_count,
                number_format($run->regular_hours, 2),
                number_format($run->overtime_hours, 2),
                number_format($run->total_hours, 2),
                $noRate ? 'N/A' : number_format($run->hourly_rate, 2),
                $noRate ? 'N/A' : number_format($run->regular_pay,  2),
                $noRate ? 'N/A' : number_format($run->overtime_pay, 2),
                $noRate ? 'N/A' : number_format($run->gross_pay,    2),
                number_format($deductions, 2),
                $noRate ? 'N/A' : number_format($run->net_pay ?? ($run->gross_pay - $deductions), 2),
                '"' . str_replace('"', '""', $run->approvedBy?->name ?? '') . '"',
                $run->approved_at?->toDateString() ?? '',
            ]);
        }

        return implode("\r\n", $lines);
    }
}
