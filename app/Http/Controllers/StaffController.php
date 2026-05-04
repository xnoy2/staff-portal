<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\PayrollRun;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);
        $query = User::with('roles')
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->role));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Route is admin/manager-only; fields here are intentional for staff management UI.
        $staff = $query->paginate(15)->withQueryString()
            ->through(fn ($user) => [
                'id'                   => $user->id,
                'employee_id'          => $user->employee_id,
                'name'                 => $user->name,
                'email'                => $user->email,
                'avatar_url'           => $user->avatar_url,
                'is_active'            => $user->is_active,
                'must_change_password' => $user->must_change_password,
                'hire_date'            => $user->hire_date?->toDateString(),
                'roles'                => $user->getRoleNames(),
                'created_at'           => $user->created_at->toDateString(),
            ]);

        return Inertia::render('Staff/Index', [
            'staff'   => $staff,
            'filters' => $request->only(['search', 'role', 'status']),
            'roles'   => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);
        return Inertia::render('Staff/Create', [
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $temporaryPassword = Str::password(12);

        $avatarPath = $request->hasFile('avatar')
            ? $request->file('avatar')->store('avatars', $this->avatarDisk())
            : null;

        $user = User::create([
            'name'                    => $request->name,
            'email'                   => $request->email,
            'password'                => Hash::make($temporaryPassword),
            'is_active'               => $request->boolean('is_active', true),
            'must_change_password'    => true,
            'hire_date'               => $request->hire_date,
            'emergency_contact_name'  => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'certifications'          => $request->certifications ?? [],
            'notes'                   => $request->notes,
            'annual_leave_days'       => $request->integer('annual_leave_days', 28),
            'hourly_rate'             => $request->filled('hourly_rate') ? round((float) $request->input('hourly_rate'), 2) : null,
            'contracted_hours'        => $request->integer('contracted_hours', 40),
            'avatar'                  => $avatarPath,
        ]);

        $user->syncRoles([$request->role]);

        activity()
            ->performedOn($user)
            ->causedBy($request->user())
            ->log('user_created');

        return redirect()->route('staff.show', $user->id)
            ->with('success', "{$user->name} has been added.")
            ->with('temp_password', $temporaryPassword);
    }

    public function show(User $staff): Response
    {
        $this->authorize('view', $staff);
        $staff->load(['roles', 'projects']);

        $recentEntries = TimeEntry::forUser($staff->id)
            ->with('approvedBy:id,name')
            ->orderBy('clock_in', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($e) => [
                'id'        => $e->id,
                'date'      => $e->clock_in?->toDateString(),
                'clock_in'  => $e->clock_in?->format('H:i'),
                'clock_out' => $e->clock_out?->format('H:i'),
                'hours'     => $e->total_hours,
                'status'    => $e->status,
                'source'    => $e->source,
            ]);

        $totalHours = TimeEntry::forUser($staff->id)->approved()->sum('total_hours');

        return Inertia::render('Staff/Show', [
            'staffMember'  => [
                'id'                      => $staff->id,
                'employee_id'             => $staff->employee_id,
                'name'                    => $staff->name,
                'email'                   => $staff->email,
                'avatar_url'              => $staff->avatar_url,
                'is_active'               => $staff->is_active,
                'must_change_password'    => $staff->must_change_password,
                'hire_date'               => $staff->hire_date?->toDateString(),
                'emergency_contact_name'  => $staff->emergency_contact_name,
                'emergency_contact_phone' => $staff->emergency_contact_phone,
                'certifications'          => $staff->certifications ?? [],
                'notes'                   => $staff->notes,
                'roles'                   => $staff->getRoleNames(),
                'created_at'             => $staff->created_at?->toDateString(),
                'hourly_rate'            => $staff->hourly_rate,
                'annual_leave_days'      => $staff->annual_leave_days,
                'contracted_hours'       => $staff->contracted_hours ?? 40,
            ],
            'recentEntries'    => $recentEntries,
            'totalHours'       => round($totalHours, 2),
            'recentPayrollRuns'=> PayrollRun::where('user_id', $staff->id)
                ->orderBy('period_from', 'desc')
                ->limit(6)
                ->get()
                ->map(fn ($r) => [
                    'id'          => $r->id,
                    'period_from' => $r->period_from->toDateString(),
                    'period_to'   => $r->period_to->toDateString(),
                    'gross_pay'   => $r->gross_pay,
                    'total_hours' => $r->total_hours,
                    'status'      => $r->status,
                    'has_rate'    => ! is_null($r->hourly_rate),
                ]),
            'projects'      => $staff->projects->map(fn ($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'customer' => $p->customer,
                'business' => $p->business,
                'status'   => $p->status,
                'role'     => $p->pivot->role,
            ]),
        ]);
    }

    public function edit(User $staff): Response
    {
        $this->authorize('update', $staff);
        $staff->load('roles');

        return Inertia::render('Staff/Edit', [
            'staffMember' => [
                'id'                      => $staff->id,
                'name'                    => $staff->name,
                'email'                   => $staff->email,
                'avatar_url'              => $staff->avatar_url,
                'is_active'               => $staff->is_active,
                'must_change_password'    => $staff->must_change_password,
                'hire_date'               => $staff->hire_date?->toDateString(),
                'emergency_contact_name'  => $staff->emergency_contact_name,
                'emergency_contact_phone' => $staff->emergency_contact_phone,
                'certifications'          => $staff->certifications ?? [],
                'notes'                   => $staff->notes,
                'roles'                   => $staff->getRoleNames(),
                'annual_leave_days'       => $staff->annual_leave_days,
                'hourly_rate'             => $staff->hourly_rate,
                'contracted_hours'        => $staff->contracted_hours ?? 40,
            ],
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        $data = [
            'name'                    => $request->name,
            'email'                   => $request->email,
            'is_active'               => $request->boolean('is_active', true),
            'must_change_password'    => $request->boolean('must_change_password'),
            'hire_date'               => $request->hire_date,
            'emergency_contact_name'  => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'certifications'          => $request->certifications ?? [],
            'notes'                   => $request->notes,
            'annual_leave_days'       => $request->integer('annual_leave_days', 28),
            'hourly_rate'             => $request->filled('hourly_rate') ? round((float) $request->input('hourly_rate'), 2) : null,
            'contracted_hours'        => $request->integer('contracted_hours', 40),
        ];

        if ($request->hasFile('avatar')) {
            // Delete old file if stored locally
            if ($staff->avatar) {
                Storage::disk($this->avatarDisk())->delete($staff->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', $this->avatarDisk());
        }

        $staff->update($data);

        $staff->syncRoles([$request->role]);

        activity()
            ->performedOn($staff)
            ->causedBy($request->user())
            ->log('user_updated');

        return redirect()->route('staff.index')
            ->with('success', "{$staff->name} has been updated.");
    }

    private function avatarDisk(): string
    {
        return config('filesystems.disks.r2.bucket') ? 'r2' : 'public';
    }

    public function destroy(Request $request, User $staff): RedirectResponse
    {
        $this->authorize('delete', $staff);

        $name = $staff->name;
        $staff->delete();

        activity()
            ->causedBy($request->user())
            ->log("user_deleted: {$name}");

        return redirect()->route('staff.index')
            ->with('success', "{$name} has been deleted.");
    }

    public function toggleActive(Request $request, User $staff): RedirectResponse
    {
        $this->authorize('toggleActive', $staff);
        $staff->update(['is_active' => ! $staff->is_active]);

        $status = $staff->is_active ? 'activated' : 'deactivated';

        activity()
            ->performedOn($staff)
            ->causedBy($request->user())
            ->log("user_{$status}");

        return back()->with('success', "{$staff->name} has been {$status}.");
    }

    public function forcePasswordReset(Request $request, User $staff): RedirectResponse
    {
        $this->authorize('update', $staff);
        $staff->update(['must_change_password' => true]);

        activity()
            ->performedOn($staff)
            ->causedBy($request->user())
            ->log('user_forced_password_reset');

        return back()->with('success', "{$staff->name} will be prompted to change their password on next login.");
    }
}
