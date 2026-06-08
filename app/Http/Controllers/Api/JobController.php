<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /**
     * GET /api/jobs
     * Paginated jobs (work orders).
     *
     * Query params:
     *   from=YYYY-MM-DD   date >=
     *   to=YYYY-MM-DD     date <=
     *   status=scheduled  scheduled|in_progress|completed
     *   per_page=50       page size (max 200)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Job::query()
            ->with(['project:id,name,business', 'van:id,registration', 'staff:id,name'])
            ->orderByDesc('date')
            ->orderBy('start_time');

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->string('to'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = min((int) $request->input('per_page', 50), 200);

        $jobs = $query->paginate($perPage);

        $jobs->getCollection()->transform(fn (Job $j) => [
            'id'         => $j->id,
            'title'      => $j->title,
            'status'     => $j->status,
            'date'       => $j->date?->toDateString(),
            'start_time' => $j->start_time,
            'end_time'   => $j->end_time,
            'project'    => $j->project ? [
                'name'     => $j->project->name,
                'business' => $j->project->business,
            ] : null,
            'van'        => $j->van?->registration,
            'staff'      => $j->staff->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
        ]);

        return response()->json($jobs);
    }

    /**
     * GET /api/jobs/summary
     * Jobs grouped by status + project roll-ups for dashboard charts.
     */
    public function summary(): JsonResponse
    {
        $jobsByStatus = Job::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $todaysJobs = Job::forDate(today()->toDateString())->count();

        $projectsByStatus = Project::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $projectsByBusiness = Project::query()
            ->selectRaw('business, count(*) as total')
            ->groupBy('business')
            ->pluck('total', 'business');

        return response()->json([
            'todays_jobs'          => $todaysJobs,
            'jobs_by_status'       => $jobsByStatus,
            'projects_by_status'   => $projectsByStatus,
            'projects_by_business' => $projectsByBusiness,
        ]);
    }
}
