<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * GET /api/staff
     * Paginated list of staff members.
     *
     * Query params:
     *   active=1|0     filter by active status
     *   role=manager   filter by role name
     *   search=jo      match name / email / employee_id
     *   per_page=50    page size (max 200)
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('roles:id,name');

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->string('role')));
        }

        if ($request->filled('search')) {
            $term = '%' . $request->string('search') . '%';
            $query->where(fn ($q) => $q
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('employee_id', 'like', $term));
        }

        $perPage = min((int) $request->input('per_page', 50), 200);

        $staff = $query->orderBy('name')->paginate($perPage);

        $staff->getCollection()->transform(fn (User $u) => $this->transform($u));

        return response()->json($staff);
    }

    /**
     * GET /api/staff/summary
     * Headcount roll-up for dashboard tiles.
     */
    public function summary(): JsonResponse
    {
        $total    = User::count();
        $active   = User::where('is_active', true)->count();
        $byRole   = User::query()
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->selectRaw('roles.name as role, count(*) as total')
            ->groupBy('roles.name')
            ->pluck('total', 'role');

        $newThisMonth = User::whereNotNull('hire_date')
            ->whereYear('hire_date', now()->year)
            ->whereMonth('hire_date', now()->month)
            ->count();

        return response()->json([
            'total_staff'    => $total,
            'active_staff'   => $active,
            'inactive_staff' => $total - $active,
            'new_this_month' => $newThisMonth,
            'by_role'        => $byRole,
        ]);
    }

    private function transform(User $u): array
    {
        return [
            'id'              => $u->id,
            'employee_id'     => $u->employee_id,
            'name'            => $u->name,
            'email'           => $u->email,
            'is_active'       => $u->is_active,
            'hire_date'       => $u->hire_date?->toDateString(),
            'hourly_rate'     => $u->hourly_rate !== null ? (float) $u->hourly_rate : null,
            'contracted_hours'=> $u->contracted_hours !== null ? (float) $u->contracted_hours : null,
            'roles'           => $u->roles->pluck('name'),
            'avatar_url'      => $u->avatar_url,
        ];
    }
}
