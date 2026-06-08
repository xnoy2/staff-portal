<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    /**
     * GET /api/payroll/runs
     * Paginated payroll runs.
     *
     * Query params:
     *   from=YYYY-MM-DD   period_from >=
     *   to=YYYY-MM-DD     period_to   <=
     *   status=approved   draft|approved
     *   user_id=<uuid>    single staff member
     *   per_page=50       page size (max 200)
     */
    public function runs(Request $request): JsonResponse
    {
        $query = PayrollRun::query()
            ->with('user:id,name,employee_id')
            ->orderByDesc('period_from');

        if ($request->filled('from')) {
            $query->whereDate('period_from', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('period_to', '<=', $request->string('to'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->string('user_id'));
        }

        $perPage = min((int) $request->input('per_page', 50), 200);

        $runs = $query->paginate($perPage);

        $runs->getCollection()->transform(fn (PayrollRun $r) => [
            'id'             => $r->id,
            'staff'          => $r->user ? [
                'id'          => $r->user->id,
                'name'        => $r->user->name,
                'employee_id' => $r->user->employee_id,
            ] : null,
            'period_from'    => $r->period_from?->toDateString(),
            'period_to'      => $r->period_to?->toDateString(),
            'regular_hours'  => $r->regular_hours,
            'overtime_hours' => $r->overtime_hours,
            'total_hours'    => $r->total_hours,
            'hourly_rate'    => $r->hourly_rate,
            'regular_pay'    => $r->regular_pay,
            'overtime_pay'   => $r->overtime_pay,
            'gross_pay'      => $r->gross_pay,
            'net_pay'        => $r->net_pay,
            'shifts_count'   => $r->shifts_count,
            'status'         => $r->status,
        ]);

        return response()->json($runs);
    }

    /**
     * GET /api/payroll/summary
     * Totals across payroll runs (optionally filtered by period / status).
     *
     * Query params: from, to, status (same semantics as /runs).
     */
    public function summary(Request $request): JsonResponse
    {
        $query = PayrollRun::query();

        if ($request->filled('from')) {
            $query->whereDate('period_from', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('period_to', '<=', $request->string('to'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'runs_count'          => (int) $query->count(),
            'total_gross_pay'     => round((float) $query->sum('gross_pay'), 2),
            'total_net_pay'       => round((float) $query->sum('net_pay'), 2),
            'total_regular_hours' => round((float) $query->sum('regular_hours'), 2),
            'total_overtime_hours'=> round((float) $query->sum('overtime_hours'), 2),
            'total_hours'         => round((float) $query->sum('total_hours'), 2),
        ]);
    }
}
