<?php

namespace App\Models;

use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PayrollRun extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'period_from', 'period_to',
        'regular_hours', 'overtime_hours', 'total_hours',
        'hourly_rate', 'regular_pay', 'overtime_pay', 'gross_pay',
        'shifts_count', 'status', 'generated_by', 'approved_by',
        'approved_at', 'entries_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'period_from'      => 'date',
            'period_to'        => 'date',
            'approved_at'      => 'datetime',
            'entries_snapshot' => 'array',
            'regular_hours'    => 'float',
            'overtime_hours'   => 'float',
            'total_hours'      => 'float',
            'hourly_rate'      => 'float',
            'regular_pay'      => 'float',
            'overtime_pay'     => 'float',
            'gross_pay'        => 'float',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Static helpers ────────────────────────────────────────────────────────

    /**
     * Compute the current payroll period based on the cut-off day.
     * e.g. cutoff=25: before/on 25th → period is 26th last month to 25th this month.
     */
    public static function currentPeriod(int $cutoffDay): array
    {
        $today = now();

        if ($today->day <= $cutoffDay) {
            $fromMonth = $today->copy()->subMonth();
            $toMonth   = $today->copy();
        } else {
            $fromMonth = $today->copy();
            $toMonth   = $today->copy()->addMonth();
        }

        $fromDay = min($cutoffDay + 1, $fromMonth->daysInMonth);
        $toDay   = min($cutoffDay, $toMonth->daysInMonth);

        return [
            'from' => $fromMonth->setDay($fromDay)->toDateString(),
            'to'   => $toMonth->setDay($toDay)->toDateString(),
        ];
    }

    /**
     * Generate a payroll run for a staff member and period, then save it.
     */
    public static function generate(User $staff, Carbon $from, Carbon $to, ?string $generatedBy): self
    {
        $entries = TimeEntry::with('breaks')
            ->forUser($staff->id)
            ->whereBetween('clock_in', [$from, $to])
            ->where('status', 'approved')
            ->withSum('breaks', 'duration_minutes')
            ->orderBy('clock_in')
            ->get();

        $regularHours  = 0.0;
        $overtimeHours = 0.0;

        $snapshot = $entries->map(function ($entry) use (&$regularHours, &$overtimeHours) {
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
        })->values()->toArray();

        $rate        = (float) ($staff->hourly_rate ?? 0);
        $regularPay  = round($regularHours * $rate, 2);
        $overtimePay = round($overtimeHours * $rate * 1.5, 2);

        return static::create([
            'user_id'          => $staff->id,
            'period_from'      => $from->toDateString(),
            'period_to'        => $to->toDateString(),
            'regular_hours'    => round($regularHours, 2),
            'overtime_hours'   => round($overtimeHours, 2),
            'total_hours'      => round($regularHours + $overtimeHours, 2),
            'hourly_rate'      => $staff->hourly_rate,
            'regular_pay'      => $regularPay,
            'overtime_pay'     => $overtimePay,
            'gross_pay'        => round($regularPay + $overtimePay, 2),
            'shifts_count'     => $entries->count(),
            'status'           => 'draft',
            'generated_by'     => $generatedBy,
            'entries_snapshot' => $snapshot,
        ]);
    }
}
