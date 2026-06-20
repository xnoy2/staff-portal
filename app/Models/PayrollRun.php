<?php

namespace App\Models;

use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PayrollRun extends Model
{
    use HasUuids;

    /** Leave types that are paid at normal rate (others, e.g. unpaid, pay nothing). */
    public const PAID_LEAVE_TYPES = ['annual', 'sick', 'compassionate'];

    /** Assumed working days per week for converting weekly contracted hours to a daily basis. */
    public const WORK_DAYS_PER_WEEK = 5;

    protected $fillable = [
        'user_id', 'period_from', 'period_to',
        'regular_hours', 'overtime_hours', 'total_hours',
        'hourly_rate', 'regular_pay', 'overtime_pay', 'leave_pay', 'gross_pay', 'net_pay',
        'shifts_count', 'status', 'generated_by', 'approved_by',
        'approved_at', 'entries_snapshot', 'deductions',
        'leave_days', 'leave_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'period_from'      => 'date',
            'period_to'        => 'date',
            'approved_at'      => 'datetime',
            'entries_snapshot' => 'array',
            'leave_snapshot'   => 'array',
            'leave_days'       => 'float',
            'regular_hours'    => 'float',
            'overtime_hours'   => 'float',
            'total_hours'      => 'float',
            'hourly_rate'      => 'float',
            'regular_pay'      => 'float',
            'overtime_pay'     => 'float',
            'leave_pay'        => 'float',
            'gross_pay'        => 'float',
            'net_pay'          => 'float',
            'deductions'       => 'array',
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

        // ── Approved overtime allowance per day ───────────────────────────────
        // OT premium is only paid for hours covered by an approved OvertimeRequest.
        // Build a per-date pool of approved OT hours, consumed as entries claim it.
        $otPool = [];
        OvertimeRequest::where('user_id', $staff->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->each(function ($r) use (&$otPool) {
                $d = $r->date->toDateString();
                $otPool[$d] = ($otPool[$d] ?? 0.0) + (float) $r->duration_hours;
            });

        $regularHours  = 0.0;
        $overtimeHours = 0.0;

        $snapshot = $entries->map(function ($entry) use (&$regularHours, &$overtimeHours, &$otPool) {
            $hours     = (float) $entry->total_hours;
            $date      = $entry->clock_in->toDateString();
            $available = $otPool[$date] ?? 0.0;

            [$regH, $otH] = static::splitHours($hours, $entry->is_overtime, $entry->ot_type, $available);
            $otPool[$date] = max(0.0, $available - $otH); // consume approved allowance

            $regularHours  += $regH;
            $overtimeHours += $otH;

            return [
                'date'           => $date,
                'day'            => $entry->clock_in->format('D'),
                'clock_in'       => $entry->clock_in->format('H:i'),
                'clock_out'      => $entry->clock_out?->format('H:i'),
                'break_mins'     => (int) ($entry->breaks_sum_duration_minutes ?? 0),
                'total_hours'    => round($hours, 2),
                'regular_hours'  => round($regH, 2),
                'overtime_hours' => round($otH, 2),
                'is_overtime'    => $entry->is_overtime,
                'ot_type'        => $entry->ot_type,
            ];
        })->values()->toArray();

        // ── Pay rate + leave basis ────────────────────────────────────────────
        $rate       = (float) ($staff->hourly_rate ?? 0);
        $dailyHours = (float) ($staff->contracted_hours ?? 40) / self::WORK_DAYS_PER_WEEK;

        $pStart = $from->copy()->startOfDay();
        $pEnd   = $to->copy()->startOfDay();

        // ── Leave overlapping this period (paid leave adds to gross) ───────────
        $leaveRecords = LeaveRequest::where('user_id', $staff->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $to->toDateString())
            ->where('end_date', '>=', $from->toDateString())
            ->get();

        $leaveDays = 0.0;
        $leavePay  = 0.0;

        $leaveSnapshot = $leaveRecords->map(function ($l) use ($pStart, $pEnd, $dailyHours, $rate, &$leaveDays, &$leavePay) {
            // Count only the portion of the leave that falls inside this period
            $segStart = $l->start_date->lt($pStart) ? $pStart->copy() : $l->start_date->copy();
            $segEnd   = $l->end_date->gt($pEnd)     ? $pEnd->copy()   : $l->end_date->copy();
            $inPeriodDays = $segStart->gt($segEnd) ? 0.0 : LeaveRequest::workingDays($segStart, $segEnd);

            $paid = in_array($l->type, self::PAID_LEAVE_TYPES, true);
            $pay  = $paid ? round($inPeriodDays * $dailyHours * $rate, 2) : 0.0;

            $leaveDays += $inPeriodDays;
            $leavePay  += $pay;

            return [
                'type'       => $l->type,
                'start_date' => $l->start_date->toDateString(),
                'end_date'   => $l->end_date->toDateString(),
                'days'       => $inPeriodDays,
                'paid'       => $paid,
                'pay'        => $pay,
            ];
        })->values()->toArray();

        // ── Pay calculation ───────────────────────────────────────────────────
        $regularPay  = round($regularHours * $rate, 2);
        $overtimePay = round($overtimeHours * $rate * 1.5, 2);
        $leavePay    = round($leavePay, 2);
        $grossPay    = round($regularPay + $overtimePay + $leavePay, 2);

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
            'leave_pay'        => $leavePay,
            'gross_pay'        => $grossPay,
            'net_pay'          => $grossPay, // no deductions yet
            'shifts_count'     => $entries->count(),
            'status'           => 'draft',
            'generated_by'     => $generatedBy,
            'entries_snapshot' => $snapshot,
            'leave_days'       => round($leaveDays, 2),
            'leave_snapshot'   => $leaveSnapshot,
        ]);
    }

    /**
     * Split worked hours into [regularHours, overtimeHours].
     *
     * All worked hours are always paid; the OT *premium* (1.5×) only applies to
     * hours that are covered by an approved OvertimeRequest ($approvedOtHours).
     * Anything beyond the approved allowance is paid at the regular rate.
     *
     * OT-eligible hours per shift:
     *  - ot_type set (clocked in as OT/RDOT) → the whole shift is eligible
     *  - is_overtime (>8h, no ot_type)       → only the hours beyond 8 are eligible
     *  - otherwise                            → none
     */
    public static function splitHours(float $hours, bool $isOvertime, ?string $otType, float $approvedOtHours = 0.0): array
    {
        if ($otType !== null) {
            $eligible = $hours;
        } elseif ($isOvertime) {
            $eligible = max(0.0, $hours - 8.0);
        } else {
            $eligible = 0.0;
        }

        $overtime = min($eligible, max(0.0, $approvedOtHours));
        $regular  = $hours - $overtime;

        return [$regular, $overtime];
    }
}
