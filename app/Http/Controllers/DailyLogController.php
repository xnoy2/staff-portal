<?php

namespace App\Http\Controllers;

use App\Models\ActivityEntry;
use App\Models\DailyLog;
use App\Models\Job;
use App\Models\User;
use App\Notifications\DailyLogAcknowledged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DailyLogController extends Controller
{
    // ── Staff: My Day ─────────────────────────────────────────────────────────

    public function myDay(Request $request): Response
    {
        $user = $request->user();
        $tz   = $user->timezone;

        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'), $tz)->toDateString()
            : Carbon::now($tz)->toDateString();

        $log = DailyLog::where('user_id', $user->id)->where('log_date', $date)->first();
        if ($log) {
            $log->load(['activities.job:id,title,date']);
        }

        // Jobs assigned to this staff member around this day (for linking + prefill)
        $jobs = Job::whereHas('staff', fn ($q) => $q->where('users.id', $user->id))
            ->whereBetween('date', [
                Carbon::parse($date)->subDays(7)->toDateString(),
                Carbon::parse($date)->addDay()->toDateString(),
            ])
            ->where('status', '!=', 'cancelled')
            ->orderBy('date', 'desc')
            ->limit(40)
            ->get(['id', 'title', 'date', 'status']);

        // Recent history (last 14 logs)
        $history = DailyLog::where('user_id', $user->id)
            ->withCount('activities')
            ->withSum('activities', 'duration_minutes')
            ->orderBy('log_date', 'desc')
            ->limit(14)
            ->get()
            ->map(fn ($l) => [
                'log_date'   => $l->log_date->toDateString(),
                'status'     => $l->status,
                'minutes'    => $l->total_minutes,
                'activities' => (int) $l->activities_count,
            ]);

        return Inertia::render('DailyLog/MyDay', [
            'date'       => $date,
            'today'      => Carbon::now($tz)->toDateString(),
            'log'        => $log ? $this->logPayload($log) : null,
            'jobs'       => $jobs->map(fn ($j) => [
                'id'      => $j->id,
                'title'   => $j->title,
                'date'    => $j->date?->toDateString(),
                'is_today'=> $j->date?->toDateString() === $date,
            ]),
            'categories' => ActivityEntry::CATEGORIES,
            'history'    => $history,
        ]);
    }

    // ── Staff: activities ─────────────────────────────────────────────────────

    public function storeActivity(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $this->validateActivity($request, true);

        $log = DailyLog::firstOrCreate(
            ['user_id' => $user->id, 'log_date' => $data['date']],
            ['status' => 'draft'],
        );

        $sort = (int) (ActivityEntry::where('daily_log_id', $log->id)->max('sort_order') ?? -1) + 1;

        ActivityEntry::create([
            'daily_log_id'     => $log->id,
            'user_id'          => $user->id,
            'description'      => $data['description'],
            'category'         => $data['category'] ?? 'other',
            'job_id'           => $data['job_id'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'photos'           => $data['photos'] ?? [],
            'sort_order'       => $sort,
        ]);

        return back();
    }

    public function updateActivity(Request $request, ActivityEntry $activity): RedirectResponse
    {
        abort_unless($activity->user_id === $request->user()->id, 403);
        $data = $this->validateActivity($request, false);

        $activity->update([
            'description'      => $data['description'],
            'category'         => $data['category'] ?? 'other',
            'job_id'           => $data['job_id'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'photos'           => $data['photos'] ?? $activity->photos ?? [],
        ]);

        return back();
    }

    public function destroyActivity(Request $request, ActivityEntry $activity): RedirectResponse
    {
        abort_unless($activity->user_id === $request->user()->id, 403);

        foreach (($activity->photos ?? []) as $p) {
            if (! empty($p['path'])) {
                Storage::disk($this->mediaDisk())->delete($p['path']);
            }
        }
        $activity->delete();

        return back();
    }

    public function reorderActivities(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['string']]);
        foreach ($data['ids'] as $i => $id) {
            ActivityEntry::where('id', $id)->where('user_id', $request->user()->id)
                ->update(['sort_order' => $i]);
        }
        return back();
    }

    // ── Staff: EOD summary / submit ───────────────────────────────────────────

    public function saveLog(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'date'          => ['required', 'date'],
            'summary'       => ['nullable', 'string', 'max:5000'],
            'blockers'      => ['nullable', 'string', 'max:5000'],
            'plan_tomorrow' => ['nullable', 'string', 'max:5000'],
            'submit'        => ['nullable', 'boolean'],
        ]);

        $log = DailyLog::firstOrCreate(
            ['user_id' => $user->id, 'log_date' => $data['date']],
            ['status' => 'draft'],
        );

        $log->fill([
            'summary'       => $data['summary'] ?? null,
            'blockers'      => $data['blockers'] ?? null,
            'plan_tomorrow' => $data['plan_tomorrow'] ?? null,
        ]);

        if ($request->boolean('submit')) {
            $log->status       = 'submitted';
            $log->submitted_at = now();
        }

        $log->save();

        return back();
    }

    public function reopenLog(Request $request, DailyLog $dailyLog): RedirectResponse
    {
        abort_unless($dailyLog->user_id === $request->user()->id, 403);
        $dailyLog->update(['status' => 'draft', 'submitted_at' => null]);
        return back();
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:20480'],
        ]);

        $file = $request->file('file');
        $path = $file->store('activity-logs/photos', $this->mediaDisk());

        return response()->json([
            'path' => $path,
            'url'  => route('kb.media', $path),
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }

    // ── Manager: team activity logs ───────────────────────────────────────────

    public function managerIndex(Request $request): Response
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $query = DailyLog::with(['user:id,name,avatar,timezone'])
            ->withSum('activities', 'duration_minutes')
            ->withCount('activities')
            ->orderBy('log_date', 'desc');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('from')) {
            $query->where('log_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->where('log_date', '<=', $request->input('to'));
        }
        if ($request->filled('job_id')) {
            $jobId = $request->input('job_id');
            $query->whereHas('activities', fn ($q) => $q->where('job_id', $jobId));
        }

        $logs = $query->paginate(20)->withQueryString();

        return Inertia::render('DailyLog/ManagerIndex', [
            'logs'      => $logs->through(fn ($l) => [
                'id'               => $l->id,
                'user'             => ['id' => $l->user->id, 'name' => $l->user->name, 'avatar_url' => $l->user->avatar_url],
                'log_date'         => $l->log_date->toDateString(),
                'status'           => $l->status,
                'minutes'          => $l->total_minutes,
                'activities_count' => $l->activities_count,
                'acknowledged'     => ! is_null($l->acknowledged_at),
                'submitted_at'     => $l->submitted_at?->toIso8601String(),
            ]),
            'staffList'    => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'jobs'         => Job::whereIn('id', ActivityEntry::whereNotNull('job_id')->distinct()->pluck('job_id'))
                ->orderBy('date', 'desc')
                ->limit(200)
                ->get(['id', 'title']),
            'filters'      => $request->only(['user_id', 'status', 'from', 'to', 'job_id']),
            'pendingToday' => $this->notSubmittedTodayCount(),
        ]);
    }

    public function managerShow(Request $request, DailyLog $dailyLog): Response
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $dailyLog->load(['user:id,name,avatar,timezone', 'activities.job:id,title', 'acknowledgedBy:id,name']);

        return Inertia::render('DailyLog/ManagerShow', [
            'log' => $this->logPayload($dailyLog, includeUser: true),
        ]);
    }

    public function acknowledge(Request $request, DailyLog $dailyLog): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $data = $request->validate(['comment' => ['nullable', 'string', 'max:2000']]);

        $dailyLog->update([
            'acknowledged_by'  => $request->user()->id,
            'acknowledged_at'  => now(),
            'manager_comment'  => $data['comment'] ?? null,
        ]);

        $dailyLog->user->notify(new DailyLogAcknowledged($dailyLog, $request->user()));

        return back();
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $query = ActivityEntry::with(['user:id,name,employee_id,timezone', 'job:id,title', 'dailyLog:id,log_date,status'])
            ->whereHas('dailyLog', function ($q) use ($request) {
                if ($request->filled('user_id')) $q->where('user_id', $request->input('user_id'));
                if ($request->filled('from'))     $q->where('log_date', '>=', $request->input('from'));
                if ($request->filled('to'))       $q->where('log_date', '<=', $request->input('to'));
                if ($request->filled('status'))   $q->where('status', $request->input('status'));
            })
            ->orderBy('created_at');

        $filename = 'activity_logs_' . now()->format('Y-m-d_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee ID', 'Name', 'Date', 'Category', 'Activity', 'Duration (min)', 'Job', 'Log status', 'Photos']);
            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $a) {
                    fputcsv($handle, [
                        $a->user?->employee_id ?? '',
                        $a->user?->name ?? '',
                        $a->dailyLog?->log_date?->toDateString() ?? '',
                        $a->category,
                        $a->description,
                        $a->duration_minutes ?? '',
                        $a->job?->title ?? '',
                        $a->dailyLog?->status ?? '',
                        count($a->photos ?? []),
                    ]);
                }
            });
            fclose($handle);
        }, 200, $headers);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateActivity(Request $request, bool $withDate): array
    {
        $rules = [
            'description'      => ['required', 'string', 'max:2000'],
            'category'         => ['nullable', 'in:' . implode(',', ActivityEntry::CATEGORIES)],
            'job_id'           => ['nullable', 'string', 'exists:work_orders,id'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'photos'           => ['nullable', 'array'],
            'photos.*.path'    => ['required', 'string'],
            'photos.*.name'    => ['nullable', 'string'],
            'photos.*.size'    => ['nullable', 'integer'],
        ];
        if ($withDate) {
            $rules['date'] = ['required', 'date'];
        }
        return $request->validate($rules);
    }

    private function logPayload(DailyLog $log, bool $includeUser = false): array
    {
        $payload = [
            'id'              => $log->id,
            'log_date'        => $log->log_date->toDateString(),
            'status'          => $log->status,
            'summary'         => $log->summary,
            'blockers'        => $log->blockers,
            'plan_tomorrow'   => $log->plan_tomorrow,
            'submitted_at'    => $log->submitted_at?->toIso8601String(),
            'total_minutes'   => $log->total_minutes,
            'acknowledged'    => ! is_null($log->acknowledged_at),
            'acknowledged_by' => $log->acknowledgedBy?->name,
            'acknowledged_at' => $log->acknowledged_at?->toIso8601String(),
            'manager_comment' => $log->manager_comment,
            'activities'      => $log->activities->map(fn ($a) => [
                'id'               => $a->id,
                'description'      => $a->description,
                'category'         => $a->category,
                'duration_minutes' => $a->duration_minutes,
                'job'              => $a->job ? ['id' => $a->job->id, 'title' => $a->job->title] : null,
                'photos'           => collect($a->photos ?? [])->map(fn ($p) => [
                    'path' => $p['path'] ?? null,
                    'name' => $p['name'] ?? null,
                    'url'  => isset($p['path']) ? route('kb.media', $p['path']) : null,
                ])->values(),
                'sort_order'       => $a->sort_order,
            ]),
        ];

        if ($includeUser && $log->relationLoaded('user')) {
            $payload['user'] = [
                'id'         => $log->user->id,
                'name'       => $log->user->name,
                'avatar_url' => $log->user->avatar_url,
            ];
        }

        return $payload;
    }

    private function notSubmittedTodayCount(): int
    {
        // Active staff who have attendance or a job today but no submitted log.
        $today = now()->toDateString();
        return DailyLog::where('log_date', $today)->where('status', 'draft')->count();
    }

    private function mediaDisk(): string
    {
        return config('filesystems.disks.r2.bucket') ? 'r2' : 'public';
    }
}
