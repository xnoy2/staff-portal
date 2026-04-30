<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Business;
use App\Models\Project;
use App\Models\ProjectChecklistItem;
use App\Models\User;
use App\Models\Van;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct()
    {
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['admin', 'manager']),
            403
        );
    }

    public function index(Request $request): Response
    {
        $query = Project::with(['van', 'staff', 'creator'])
            ->withCount(['checklistItems', 'checklistItems as completed_checklist_count' => fn ($q) => $q->where('is_completed', true)])
            ->latest();

        if ($request->filled('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('customer', 'like', "%{$request->search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('phase')) {
            $query->where('phase', $request->phase);
        }

        if ($request->filled('business')) {
            $query->where('business', $request->business);
        }

        $projects = $query->paginate(15)->withQueryString()
            ->through(fn ($p) => $this->summarise($p));

        $statusCounts = Project::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Projects/Index', [
            'projects'     => $projects,
            'statusCounts' => $statusCounts,
            'filters'      => $request->only(['search', 'status', 'phase', 'business']),
            'businesses'   => Business::orderBy('name')->get(['id', 'name', 'code', 'color', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Create', [
            'staffList'  => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'vans'       => Van::where('is_active', true)->orderBy('registration')->get(['id', 'registration', 'make', 'model']),
            'businesses' => Business::active()->orderBy('name')->get(['id', 'name', 'code', 'color']),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create([
            ...$request->safe()->except(['staff_ids', 'staff_roles']),
            'created_by' => $request->user()->id,
        ]);

        $this->syncStaff($project, $request);

        activity()->performedOn($project)->causedBy($request->user())->log('project_created');

        return redirect()->route('projects.show', $project)
            ->with('success', "Project \"{$project->name}\" created.");
    }

    public function show(Project $project): Response
    {
        $project->load(['van', 'creator', 'staff', 'checklistItems.completedBy']);

        return Inertia::render('Projects/Show', [
            'project' => $this->detail($project),
        ]);
    }

    public function edit(Project $project): Response
    {
        $project->load(['staff']);

        return Inertia::render('Projects/Edit', [
            'project'    => $this->detail($project),
            'staffList'  => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'vans'       => Van::where('is_active', true)->orderBy('registration')->get(['id', 'registration', 'make', 'model']),
            'businesses' => Business::active()->orderBy('name')->get(['id', 'name', 'code', 'color']),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->safe()->except(['staff_ids', 'staff_roles']));

        $this->syncStaff($project, $request);

        activity()->performedOn($project)->causedBy($request->user())->log('project_updated');

        return redirect()->route('projects.show', $project)
            ->with('success', "Project updated.");
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $name = $project->name;
        $project->delete();

        activity()->causedBy($request->user())->log("project_deleted: {$name}");

        return redirect()->route('projects.index')
            ->with('success', "\"{$name}\" has been deleted.");
    }

    // ── Checklist ────────────────────────────────────────────────────

    public function addChecklistItem(Request $request, Project $project): RedirectResponse
    {
        $request->validate(['title' => ['required', 'string', 'max:255']]);

        $project->checklistItems()->create([
            'title'      => $request->title,
            'sort_order' => $project->checklistItems()->max('sort_order') + 1,
        ]);

        return back()->with('success', 'Item added.');
    }

    public function toggleChecklistItem(Request $request, Project $project, ProjectChecklistItem $item): RedirectResponse
    {
        $item->update([
            'is_completed' => !$item->is_completed,
            'completed_by' => !$item->is_completed ? $request->user()->id : null,
            'completed_at' => !$item->is_completed ? now() : null,
        ]);

        return back();
    }

    public function deleteChecklistItem(Project $project, ProjectChecklistItem $item): RedirectResponse
    {
        $item->delete();
        return back();
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function syncStaff(Project $project, Request $request): void
    {
        $ids   = $request->input('staff_ids', []);
        $roles = $request->input('staff_roles', []);
        $sync  = [];
        foreach ($ids as $id) {
            $sync[$id] = ['role' => $roles[$id] ?? 'support'];
        }
        $project->staff()->sync($sync);
    }

    private function summarise(Project $project): array
    {
        return [
            'id'               => $project->id,
            'business'         => $project->business,
            'name'             => $project->name,
            'customer'         => $project->customer,
            'address'          => $project->address,
            'status'           => $project->status,
            'phase'            => $project->phase,
            'start_date'       => $project->start_date?->toDateString(),
            'end_date'         => $project->end_date?->toDateString(),
            'budget'           => $project->budget,
            'budget_spent'     => $project->budget_spent,
            'budget_progress'  => $project->budget_progress,
            'checklist_total'  => $project->checklist_items_count ?? 0,
            'checklist_done'   => $project->completed_checklist_count ?? 0,
            'van'              => $project->van ? $project->van->only(['id', 'registration', 'make', 'model']) : null,
            'staff_count'      => $project->staff->count(),
            'created_at'       => $project->created_at->toDateString(),
        ];
    }

    private function detail(Project $project): array
    {
        return [
            ...$this->summarise($project),
            'notes'     => $project->notes,
            'staff'     => $project->staff->map(fn ($u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'avatar_url'=> $u->avatar_url,
                'role'      => $u->pivot->role,
            ]),
            'checklist' => $project->checklistItems->map(fn ($i) => [
                'id'           => $i->id,
                'job_id'       => $i->job_id,
                'title'        => $i->title,
                'is_completed' => $i->is_completed,
                'completed_by' => $i->completedBy?->name,
                'completed_at' => $i->completed_at?->format('d M Y H:i'),
                'sort_order'   => $i->sort_order,
            ]),
            'creator'   => $project->creator?->name,
        ];
    }
}
