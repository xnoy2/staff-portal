<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Project;
use App\Models\User;
use App\Models\Van;
use App\Models\VanAllocation;
use App\Models\VanAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        $query->with(['currentAssignment.user:id,name,avatar']);

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
            'photo'        => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('vans', $this->photoDisk());
        }

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

        // Current assignment
        $current = VanAssignment::where('van_id', $van->id)
            ->whereNull('returned_at')
            ->with('user:id,name,avatar', 'assignedBy:id,name')
            ->orderBy('assigned_at', 'desc')
            ->first();

        $currentDriver = $current ? $this->assignmentPayload($current) : null;

        // Full assignment history (excluding current active one)
        $history = VanAssignment::where('van_id', $van->id)
            ->whereNotNull('returned_at')
            ->with('user:id,name,avatar', 'assignedBy:id,name', 'returnedBy:id,name')
            ->orderBy('assigned_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($a) => $this->assignmentPayload($a));

        // Staff options — all active users (the new driver replaces the current one)
        $staffOptions = User::where('is_active', true)
            ->when($current, fn ($q) => $q->where('id', '!=', $current->user_id))
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
            'currentDriver'  => $currentDriver,
            'assignmentHistory' => $history,
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
            'photo'        => ['nullable', 'image', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('photo')) {
            if ($van->photo) {
                Storage::disk($this->photoDisk())->delete($van->photo);
            }
            $data['photo'] = $request->file('photo')->store('vans', $this->photoDisk());
        } elseif ($request->boolean('remove_photo')) {
            if ($van->photo) {
                Storage::disk($this->photoDisk())->delete($van->photo);
            }
            $data['photo'] = null;
        }

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

    public function assign(Request $request, Van $van): RedirectResponse
    {
        $this->authorize('update', $van);
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'notes'   => ['nullable', 'string', 'max:500'],
        ]);

        $now = now();

        // End any current active assignment
        VanAssignment::where('van_id', $van->id)
            ->whereNull('returned_at')
            ->update(['returned_at' => $now, 'returned_by' => $request->user()->id]);

        VanAssignment::create([
            'van_id'      => $van->id,
            'user_id'     => $data['user_id'],
            'assigned_by' => $request->user()->id,
            'assigned_at' => $now,
            'notes'       => $data['notes'] ?? null,
        ]);

        $driver = User::find($data['user_id']);

        return back()->with('success', "{$driver->name} is now assigned to {$van->registration}.");
    }

    public function returnVan(Request $request, Van $van): RedirectResponse
    {
        $this->authorize('update', $van);

        $updated = VanAssignment::where('van_id', $van->id)
            ->whereNull('returned_at')
            ->update(['returned_at' => now(), 'returned_by' => $request->user()->id]);

        if (! $updated) {
            return back()->with('error', 'This van has no active assignment.');
        }

        return back()->with('success', "{$van->registration} marked as returned.");
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function photoDisk(): string
    {
        return config('filesystems.disks.r2.bucket') ? 'r2' : 'public';
    }

    private function summarise(Van $van): array
    {
        $ca = $van->relationLoaded('currentAssignment') ? $van->currentAssignment : null;

        return [
            'id'             => $van->id,
            'registration'   => $van->registration,
            'make'           => $van->make,
            'model'          => $van->model,
            'year'           => $van->year,
            'is_active'      => $van->is_active,
            'projects_count' => $van->projects_count ?? 0,
            'display_name'   => $van->display_name,
            'photo_url'      => $van->photo_url,
            'current_driver' => $ca && $ca->user ? [
                'id'         => $ca->user->id,
                'name'       => $ca->user->name,
                'avatar_url' => $ca->user->avatar_url,
                'since'      => $ca->assigned_at->toDateString(),
            ] : null,
        ];
    }

    private function assignmentPayload(VanAssignment $a): array
    {
        return [
            'id'          => $a->id,
            'user'        => $a->user ? [
                'id'         => $a->user->id,
                'name'       => $a->user->name,
                'avatar_url' => $a->user->avatar_url,
            ] : null,
            'assigned_by'  => $a->assignedBy?->name,
            'assigned_at'  => $a->assigned_at->toIso8601String(),
            'returned_at'  => $a->returned_at?->toIso8601String(),
            'returned_by'  => $a->returnedBy?->name,
            'duration'     => $a->duration,
            'is_active'    => $a->is_active,
            'notes'        => $a->notes,
        ];
    }

    private function detail(Van $van, $projects = null): array
    {
        return [
            ...$this->summarise($van),
            'notes'     => $van->notes,
            'has_photo' => (bool) $van->photo,
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
