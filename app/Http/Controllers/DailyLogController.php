<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\Job;
use App\Models\User;
use App\Notifications\DailyLogAcknowledged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        // Jobs assigned to this staff member around this day (for tagging)
        $jobs = Job::whereHas('staff', fn ($q) => $q->where('users.id', $user->id))
            ->whereBetween('date', [
                Carbon::parse($date)->subDays(14)->toDateString(),
                Carbon::parse($date)->addDay()->toDateString(),
            ])
            ->where('status', '!=', 'cancelled')
            ->orderBy('date', 'desc')
            ->limit(60)
            ->get(['id', 'title', 'date', 'status']);

        $history = DailyLog::where('user_id', $user->id)
            ->orderBy('log_date', 'desc')
            ->limit(14)
            ->get(['id', 'log_date', 'status', 'photos'])
            ->map(fn ($l) => [
                'log_date' => $l->log_date->toDateString(),
                'status'   => $l->status,
                'photos'   => count($l->photos ?? []),
            ]);

        return Inertia::render('DailyLog/MyDay', [
            'date'    => $date,
            'today'   => Carbon::now($tz)->toDateString(),
            'log'     => $log ? $this->logPayload($log) : null,
            'jobs'    => $jobs->map(fn ($j) => [
                'id'       => $j->id,
                'title'    => $j->title,
                'date'     => $j->date?->toDateString(),
                'is_today' => $j->date?->toDateString() === $date,
            ]),
            'history' => $history,
        ]);
    }

    // ── Staff: save the day (EOD + photos + job tags) ─────────────────────────

    public function saveLog(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'date'          => ['required', 'date'],
            'summary'       => ['nullable', 'string', 'max:5000'],
            'blockers'      => ['nullable', 'string', 'max:5000'],
            'plan_tomorrow' => ['nullable', 'string', 'max:5000'],
            'photos'        => ['nullable', 'array'],
            'photos.*.path' => ['required', 'string'],
            'photos.*.name' => ['nullable', 'string'],
            'photos.*.size' => ['nullable', 'integer'],
            'jobs'          => ['nullable', 'array'],
            'jobs.*'        => ['string', 'exists:work_orders,id'],
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
            'photos'        => $data['photos'] ?? [],
            'jobs'          => $data['jobs'] ?? [],
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
        $request->validate(['file' => ['required', 'file', 'image', 'max:20480']]);

        $file = $request->file('file');
        $path = $file->store('daily-logs/photos', $this->mediaDisk());

        return response()->json([
            'path' => $path,
            'url'  => route('kb.media', $path),
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }

    // ── Manager: team daily logs ──────────────────────────────────────────────

    public function managerIndex(Request $request): Response
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $query = DailyLog::with(['user:id,name,avatar,timezone'])
            ->orderBy('log_date', 'desc');

        if ($request->filled('user_id')) $query->where('user_id', $request->input('user_id'));
        if ($request->filled('status'))  $query->where('status', $request->input('status'));
        if ($request->filled('from'))    $query->where('log_date', '>=', $request->input('from'));
        if ($request->filled('to'))      $query->where('log_date', '<=', $request->input('to'));
        if ($request->filled('job_id'))  $query->whereJsonContains('jobs', $request->input('job_id'));

        $logs = $query->paginate(20)->withQueryString();

        return Inertia::render('DailyLog/ManagerIndex', [
            'logs' => $logs->through(fn ($l) => [
                'id'           => $l->id,
                'user'         => ['id' => $l->user->id, 'name' => $l->user->name, 'avatar_url' => $l->user->avatar_url],
                'log_date'     => $l->log_date->toDateString(),
                'status'       => $l->status,
                'photos'       => count($l->photos ?? []),
                'jobs'         => count($l->jobs ?? []),
                'has_summary'  => filled($l->summary),
                'acknowledged' => ! is_null($l->acknowledged_at),
            ]),
            'staffList'    => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'jobs'         => Job::orderBy('date', 'desc')->limit(100)->get(['id', 'title']),
            'filters'      => $request->only(['user_id', 'status', 'from', 'to', 'job_id']),
            'pendingToday' => DailyLog::where('log_date', now()->toDateString())->where('status', 'draft')->count(),
        ]);
    }

    public function managerShow(Request $request, DailyLog $dailyLog): Response
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $dailyLog->load(['user:id,name,avatar,timezone', 'acknowledgedBy:id,name']);

        return Inertia::render('DailyLog/ManagerShow', [
            'log' => $this->logPayload($dailyLog, includeUser: true),
        ]);
    }

    public function acknowledge(Request $request, DailyLog $dailyLog): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $data = $request->validate(['comment' => ['nullable', 'string', 'max:2000']]);

        $dailyLog->update([
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => now(),
            'manager_comment' => $data['comment'] ?? null,
        ]);

        $dailyLog->user->notify(new DailyLogAcknowledged($dailyLog, $request->user()));

        return back();
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $query = DailyLog::with('user:id,name,employee_id')->orderBy('log_date', 'desc');
        if ($request->filled('user_id')) $query->where('user_id', $request->input('user_id'));
        if ($request->filled('status'))  $query->where('status', $request->input('status'));
        if ($request->filled('from'))    $query->where('log_date', '>=', $request->input('from'));
        if ($request->filled('to'))      $query->where('log_date', '<=', $request->input('to'));
        if ($request->filled('job_id'))  $query->whereJsonContains('jobs', $request->input('job_id'));

        $filename = 'daily_logs_' . now()->format('Y-m-d_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee ID', 'Name', 'Date', 'Status', 'Accomplishments', 'Blockers', 'Plan for tomorrow', 'Jobs', 'Photos']);
            $query->chunk(200, function ($rows) use ($handle) {
                $titles = $this->jobTitleMap($rows->pluck('jobs')->flatten()->filter()->unique()->all());
                foreach ($rows as $l) {
                    fputcsv($handle, [
                        $l->user?->employee_id ?? '',
                        $l->user?->name ?? '',
                        $l->log_date->toDateString(),
                        $l->status,
                        $l->summary,
                        $l->blockers,
                        $l->plan_tomorrow,
                        collect($l->jobs ?? [])->map(fn ($id) => $titles[$id] ?? $id)->implode('; '),
                        count($l->photos ?? []),
                    ]);
                }
            });
            fclose($handle);
        }, 200, $headers);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function logPayload(DailyLog $log, bool $includeUser = false): array
    {
        $titles = $this->jobTitleMap($log->jobs ?? []);

        $payload = [
            'id'              => $log->id,
            'log_date'        => $log->log_date->toDateString(),
            'status'          => $log->status,
            'summary'         => $log->summary,
            'blockers'        => $log->blockers,
            'plan_tomorrow'   => $log->plan_tomorrow,
            'submitted_at'    => $log->submitted_at?->toIso8601String(),
            'photos'          => collect($log->photos ?? [])->map(fn ($p) => [
                'path' => $p['path'] ?? null,
                'name' => $p['name'] ?? null,
                'size' => $p['size'] ?? null,
                'url'  => isset($p['path']) ? route('kb.media', $p['path']) : null,
            ])->values(),
            'jobs'            => collect($log->jobs ?? [])->map(fn ($id) => [
                'id' => $id, 'title' => $titles[$id] ?? 'Job',
            ])->values(),
            'acknowledged'    => ! is_null($log->acknowledged_at),
            'acknowledged_by' => $log->acknowledgedBy?->name,
            'acknowledged_at' => $log->acknowledged_at?->toIso8601String(),
            'manager_comment' => $log->manager_comment,
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

    private function jobTitleMap(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return Job::whereIn('id', $ids)->pluck('title', 'id')->all();
    }

    private function mediaDisk(): string
    {
        return config('filesystems.disks.r2.bucket') ? 'r2' : 'public';
    }
}
