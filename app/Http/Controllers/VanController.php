<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Project;
use App\Models\User;
use App\Models\Van;
use App\Models\VanAllocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VanController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Van::class);
        $query = Van::withCount('projects');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('registration', 'like', "%{$request->search}%")
                  ->orWhere('make', 'like', "%{$request->search}%")
                  ->orWhere('model', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $vans = $query->orderBy('registration')->paginate(20)->withQueryString()
            ->through(fn ($v) => $this->summarise($v));

        return Inertia::render('Vans/Index', [
            'vans'    => $vans,
            'filters' => $request->only(['search', 'status']),
            'counts'  => [
                'total'    => Van::count(),
                'active'   => Van::where('is_active', true)->count(),
                'inactive' => Van::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Van::class);
        return Inertia::render('Vans/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Van::class);
        $data = $request->validate([
            'registration' => ['required', 'string', 'max:20', 'unique:vans,registration'],
            'make'         => ['required', 'string', 'max:100'],
            'model'        => ['required', 'string', 'max:100'],
            'year'         => ['nullable', 'integer', 'min:1990', 'max:' . (date('Y') + 1)],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ]);

        $van = Van::create([...$data, 'is_active' => true]);

        return redirect()->route('vans.show', $van)
            ->with('success', "Van {$van->registration} added.");
    }

    public function show(Van $van): Response
    {
        $this->authorize('view', $van);
        $van->loadCount('projects');

        $projects = $van->projects()
            ->orderByRaw("FIELD(status, 'active', 'on_hold', 'planning', 'complete')")
            ->get(['id', 'name', 'customer', 'business', 'status']);

        $recentJobs = Job::where('van_id', $van->id)
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($j) => [
                'id'         => $j->id,
                'title'      => $j->title,
                'date'       => $j->date->format('d M Y'),
                'start_time' => $j->start_time,
                'status'     => $j->status,
            ]);

        $allocations = VanAllocation::where('van_id', $van->id)
            ->with('project:id,name,status,business')
            ->with('creator:id,name')
            ->orderBy('allocated_from', 'desc')
            ->get()
            ->map(fn ($a) => [
                'id'             => $a->id,
                'allocated_from' => $a->allocated_from->toDateString(),
                'allocated_to'   => $a->allocated_to->toDateString(),
                'purpose'        => $a->purpose,
                'notes'          => $a->notes,
                'status'         => $a->status,
                'project'        => $a->project ? [
                    'id'       => $a->project->id,
                    'name'     => $a->project->name,
                    'status'   => $a->project->status,
                    'business' => $a->project->business,
                ] : null,
                'created_by_name' => $a->creator?->name,
            ]);

        $projectOptions = Project::whereIn('status', ['planning', 'active', 'on_hold'])
            ->orderBy('name')
            ->get(['id', 'name', 'customer', 'business', 'status']);

        // Formally assigned staff (van_user pivot)
        $assignedStaff = $van->staff()->get()->map(fn ($u) => [
            'id'          => $u->id,
            'name'        => $u->name,
            'avatar_url'  => $u->avatar_url,
            'role'        => $u->roles->first()?->name ?? 'staff',
            'assigned_at' => $u->pivot->assigned_at,
        ]);

        // Historical usage derived from jobs
        $staffUsage = DB::table('work_order_user')
            ->join('work_orders', 'work_orders.id', '=', 'work_order_user.work_order_id')
            ->join('users', 'users.id', '=', 'work_order_user.user_id')
            ->where('work_orders.van_id', $van->id)
            ->select(
                'users.id',
                'users.name',
                'users.avatar',
                DB::raw('COUNT(*) as job_count'),
                DB::raw('MAX(work_orders.date) as last_used')
            )
            ->groupBy('users.id', 'users.name', 'users.avatar')
            ->orderByDesc('last_used')
            ->get()
            ->map(fn ($u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'avatar_url'=> $u->avatar
                    ? asset('storage/' . $u->avatar)
                    : 'https://ui-avatars.com/api/?name=' . urlencode($u->name) . '&background=3B6D11&color=fff&size=128',
                'job_count' => $u->job_count,
                'last_used' => $u->last_used,
            ]);

        // Staff options for the assign dropdown (exclude already assigned)
        $assignedIds   = $assignedStaff->pluck('id')->toArray();
        $staffOptions  = User::where('is_active', true)
            ->whereNotIn('id', $assignedIds)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar'])
            ->map(fn ($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'avatar_url' => $u->avatar_url,
            ]);

        return Inertia::render('Vans/Show', [
            'van'            => $this->detail($van, $projects),
            'recentJobs'     => $recentJobs,
            'allocations'    => $allocations,
            'projectOptions' => $projectOptions,
            'assignedStaff'  => $assignedStaff,
            'staffUsage'     => $staffUsage,
            'staffOptions'   => $staffOptions,
        ]);
    }

    public function edit(Van $van): Response
    {
        $this->authorize('update', $van);
        return Inertia::render('Vans/Edit', [
            'van' => $this->detail($van),
        ]);
    }

    public function update(Request $request, Van $van): RedirectResponse
    {
        $this->authorize('update', $van);
        $data = $request->validate([
            'registration' => ['required', 'string', 'max:20', 'unique:vans,registration,' . $van->id],
            'make'         => ['required', 'string', 'max:100'],
            'model'        => ['required', 'string', 'max:100'],
            'year'         => ['nullable', 'integer', 'min:1990', 'max:' . (date('Y') + 1)],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ]);

        $van->update($data);

        return redirect()->route('vans.show', $van)
            ->with('success', 'Van updated.');
    }

    public function destroy(Van $van): RedirectResponse
    {
        $this->authorize('delete', $van);
        $reg = $van->registration;
        $van->delete();

        return redirect()->route('vans.index')
            ->with('success', "Van {$reg} removed.");
    }

    public function toggleActive(Van $van): RedirectResponse
    {
        $this->authorize('update', $van);
        $van->update(['is_active' => !$van->is_active]);

        $status = $van->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Van {$van->registration} {$status}.");
    }

    public function assignStaff(Request $request, Van $van): RedirectResponse
    {
        $this->authorize('update', $van);
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $van->staff()->syncWithoutDetaching([
            $request->user_id => ['assigned_at' => now()],
        ]);

        return back()->with('success', 'Staff member assigned to van.');
    }

    public function unassignStaff(Van $van, User $user): RedirectResponse
    {
        $this->authorize('update', $van);
        $van->staff()->detach($user->id);

        return back()->with('success', 'Staff member removed from van.');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function summarise(Van $van): array
    {
        return [
            'id'             => $van->id,
            'registration'   => $van->registration,
            'make'           => $van->make,
            'model'          => $van->model,
            'year'           => $van->year,
            'is_active'      => $van->is_active,
            'projects_count' => $van->projects_count ?? 0,
            'display_name'   => $van->display_name,
        ];
    }

    private function detail(Van $van, $projects = null): array
    {
        return [
            ...$this->summarise($van),
            'notes'    => $van->notes,
            'projects' => $projects ? $projects->map(fn ($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'customer' => $p->customer,
                'business' => $p->business,
                'status'   => $p->status,
            ]) : [],
        ];
    }
}
