<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\LeaveRequest;
use App\Models\Project;
use App\Models\ProjectChecklistItem;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Van;
use App\Notifications\JobAssigned;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Job::class);
        $user         = $request->user();
        $isManager    = $user->hasAnyRole(['admin', 'manager']);
        $isSiteHead   = $user->hasRole('site_head');
        $isPrivileged = $isManager || $isSiteHead;
        $date         = $request->input('date', today()->toDateString());

        $query = Job::forDate($date)
            ->with([
                'project:id,name,customer,business',
                'van:id,registration,make,model',
                'staff:id,name,avatar',
            ])
            ->orderBy('start_time')
            ->orderBy('created_at');

        if ($isSiteHead && ! $isManager) {
            // Site head: only jobs linked to projects they are assigned to
            $projectIds = $user->projects()->pluck('projects.id');
            $query->whereIn('project_id', $projectIds);
        } elseif (! $isPrivileged) {
            // Staff: only jobs they are directly assigned to
            $query->whereHas('staff', fn ($q) => $q->where('users.id', $user->id));
        }

        $jobs = $query->get();

        // Load today's time entries for all assigned staff in one query
        $staffIds = $jobs->flatMap(fn ($j) => $j->staff->pluck('id'))->unique()->values();

        $entries = TimeEntry::whereIn('user_id', $staffIds)
            ->whereDate('clock_in', $date)
            ->get()
            ->groupBy('user_id');

        $formatted = $jobs->map(fn ($job) => $this->format($job, $entries));

        return Inertia::render('Jobs/Index', [
            'jobs'         => $formatted,
            'date'         => $date,
            'isPrivileged' => $isPrivileged,
            'projects'     => $isPrivileged
                ? ($isSiteHead && ! $isManager
                    ? $user->projects()
                        ->whereIn('status', ['planning', 'active', 'on_hold'])
                        ->orderBy('name')->get(['projects.id', 'projects.name', 'projects.customer', 'projects.business'])
                    : Project::whereIn('status', ['planning', 'active', 'on_hold'])
                        ->orderBy('name')->get(['id', 'name', 'customer', 'business']))
                : [],
            'vans'         => $isPrivileged
                ? Van::where('is_active', true)->orderBy('registration')->get(['id', 'registration', 'make', 'model'])
                : [],
            'staffList'    => $isPrivileged
                ? User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'avatar'])
                : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Job::class);
        $user = $request->user();

        $data = $request->validate([
            'project_id'  => ['nullable', 'exists:projects,id'],
            'van_id'      => ['nullable', 'exists:vans,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date'        => ['required', 'date'],
            'start_time'  => ['nullable', 'date_format:H:i'],
            'end_time'    => ['nullable', 'date_format:H:i'],
            'notes'       => ['nullable', 'string', 'max:2000'],
            'staff_ids'   => ['nullable', 'array'],
            'staff_ids.*' => ['exists:users,id'],
        ]);

        // Site heads can only create jobs for their own projects
        if ($user->hasRole('site_head') && ! $user->hasAnyRole(['admin', 'manager'])) {
            if (! empty($data['project_id']) && ! $user->projects()->where('projects.id', $data['project_id'])->exists()) {
                abort(403, 'You can only create jobs for projects assigned to you.');
            }
        }

        $job = Job::create([...$data, 'created_by' => $user->id]);

        if (!empty($data['staff_ids'])) {
            $job->staff()->sync($data['staff_ids']);
            $this->notifyNewlyAssigned($job, $data['staff_ids'], []);
        }

        $this->syncChecklistItem($job);

        $warning = $this->leaveConflictWarning($data['staff_ids'] ?? [], $data['date']);

        return $warning
            ? back()->with('warning', 'Job created. Warning: ' . $warning)
            : back()->with('success', 'Job created.');
    }

    public function update(Request $request, Job $job): RedirectResponse
    {
        $this->authorize('update', $job);
        $user = $request->user();

        $data = $request->validate([
            'project_id'  => ['nullable', 'exists:projects,id'],
            'van_id'      => ['nullable', 'exists:vans,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date'        => ['required', 'date'],
            'start_time'  => ['nullable', 'date_format:H:i'],
            'end_time'    => ['nullable', 'date_format:H:i'],
            'notes'       => ['nullable', 'string', 'max:2000'],
            'staff_ids'   => ['nullable', 'array'],
            'staff_ids.*' => ['exists:users,id'],
        ]);

        // Site heads can only edit jobs belonging to their projects
        if ($user->hasRole('site_head') && ! $user->hasAnyRole(['admin', 'manager'])) {
            $projectId = $data['project_id'] ?? $job->project_id;
            if ($projectId && ! $user->projects()->where('projects.id', $projectId)->exists()) {
                abort(403, 'You can only edit jobs for projects assigned to you.');
            }
        }

        $previousStaffIds = $job->staff()->pluck('users.id')->toArray();
        $job->update($data);
        $newStaffIds = $data['staff_ids'] ?? [];
        $job->staff()->sync($newStaffIds);
        $this->notifyNewlyAssigned($job, $newStaffIds, $previousStaffIds);

        $this->syncChecklistItem($job);

        $warning = $this->leaveConflictWarning($newStaffIds, $data['date']);

        return $warning
            ? back()->with('warning', 'Job updated. Warning: ' . $warning)
            : back()->with('success', 'Job updated.');
    }

    public function updateStatus(Request $request, Job $job): RedirectResponse
    {
        $this->authorize('update', $job);

        $request->validate([
            'status' => ['required', 'in:scheduled,in_progress,completed,cancelled'],
        ]);

        $job->update(['status' => $request->status]);

        $item = ProjectChecklistItem::where('job_id', $job->id)->first();
        if ($item) {
            $completed = $request->status === 'completed';
            $item->update([
                'is_completed' => $completed,
                'completed_by' => $completed ? $request->user()->id : null,
                'completed_at' => $completed ? now() : null,
            ]);
        }

        return back()->with('success', 'Status updated.');
    }

    public function destroy(Request $request, Job $job): RedirectResponse
    {
        $this->authorize('delete', $job);

        ProjectChecklistItem::where('job_id', $job->id)->delete();
        $job->delete();

        return back()->with('success', 'Job removed.');
    }

    private function leaveConflictWarning(array $staffIds, string $date): ?string
    {
        if (empty($staffIds)) return null;

        $names = LeaveRequest::approved()
            ->whereIn('user_id', $staffIds)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->with('user:id,name')
            ->get()
            ->map(fn ($l) => $l->user?->name)
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) return null;

        return $names->join(', ') . ' ha' . ($names->count() === 1 ? 's' : 've') . ' approved leave on this date.';
    }

    private function notifyNewlyAssigned(Job $job, array $newIds, array $previousIds): void
    {
        $added = array_diff($newIds, $previousIds);
        if (empty($added)) return;

        $users = User::whereIn('id', $added)->get();
        foreach ($users as $user) {
            $user->notify(new JobAssigned(
                jobTitle: $job->title,
                jobDate:  $job->date->format('d M Y'),
                jobId:    $job->id,
            ));
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function syncChecklistItem(Job $job): void
    {
        $existing = ProjectChecklistItem::where('job_id', $job->id)->first();
        $title    = $job->title . ' — ' . $job->date->format('d M Y');

        if ($job->project_id) {
            if ($existing) {
                if ($existing->project_id !== $job->project_id) {
                    // Project changed — move item to new project
                    $existing->delete();
                    $this->createChecklistItem($job, $title);
                } else {
                    // Same project — just sync the title and date
                    $existing->update(['title' => $title]);
                }
            } else {
                $this->createChecklistItem($job, $title);
            }
        } elseif ($existing) {
            // Project removed from job — delete the linked item
            $existing->delete();
        }
    }

    private function createChecklistItem(Job $job, string $title): void
    {
        $maxOrder = ProjectChecklistItem::where('project_id', $job->project_id)->max('sort_order') ?? 0;

        ProjectChecklistItem::create([
            'project_id' => $job->project_id,
            'job_id'     => $job->id,
            'title'      => $title,
            'sort_order' => $maxOrder + 1,
        ]);
    }

    private function format(Job $job, $entries): array
    {
        return [
            'id'          => $job->id,
            'title'       => $job->title,
            'description' => $job->description,
            'date'        => $job->date->toDateString(),
            'start_time'  => $job->start_time,
            'end_time'    => $job->end_time,
            'status'      => $job->status,
            'notes'       => $job->notes,
            'project'     => $job->project ? [
                'id'       => $job->project->id,
                'name'     => $job->project->name,
                'customer' => $job->project->customer,
                'business' => $job->project->business,
            ] : null,
            'van'         => $job->van ? [
                'id'           => $job->van->id,
                'registration' => $job->van->registration,
                'label'        => "{$job->van->registration} — {$job->van->make} {$job->van->model}",
            ] : null,
            'staff'       => $job->staff->map(function ($u) use ($entries) {
                $userEntries  = $entries->get($u->id, collect());
                $activeEntry  = $userEntries->whereNull('clock_out')->first();
                $hoursToday   = round($userEntries->sum('total_hours'), 1);

                return [
                    'id'         => $u->id,
                    'name'       => $u->name,
                    'avatar_url' => $u->avatar_url,
                    'clocked_in' => (bool) $activeEntry,
                    'hours_today'=> $hoursToday,
                ];
            }),
        ];
    }
}
