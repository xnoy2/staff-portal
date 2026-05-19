<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Services\BcfApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BcfController extends Controller
{
    public function index(): Response
    {
        $user    = auth()->user();
        $service = new BcfApiService();
        $raw     = $service->getOrders();
        $all     = $raw['orders'] ?? [];

        $isPrivileged = $user->hasAnyRole(['admin', 'manager', 'hr']);

        if ($isPrivileged || ! $user->bcf_worker_id) {
            $orders = $all;
        } else {
            $orders = array_values(array_filter(
                $all,
                fn ($o) => ($o['worker']['id'] ?? null) === $user->bcf_worker_id
            ));
        }

        return Inertia::render('Bcf/Orders', [
            'orders'       => $orders,
            'isPrivileged' => $isPrivileged,
            'linked'       => (bool) $user->bcf_worker_id,
        ]);
    }

    public function show(string $id): Response|RedirectResponse
    {
        $service = new BcfApiService();
        $raw     = $service->getOrder($id);

        if (empty($raw) || empty($raw['order'])) {
            return redirect()->route('bcf.index')->with('error', 'Order not found.');
        }

        $stages = $raw['stages'] ?? [];

        // Attach linked jobs from our DB to each stage
        $stageIds = array_column($stages, 'id');
        $linkedJobs = Job::whereIn('bcf_stage_id', $stageIds)
            ->with('staff:id,name,avatar')
            ->orderBy('date')
            ->get()
            ->groupBy('bcf_stage_id');

        $stages = array_map(function ($stage) use ($linkedJobs) {
            $stage['linked_jobs'] = $linkedJobs->get($stage['id'], collect())
                ->map(fn ($j) => [
                    'id'         => $j->id,
                    'title'      => $j->title,
                    'date'       => $j->date->toDateString(),
                    'status'     => $j->status,
                    'start_time' => $j->start_time,
                    'staff'      => $j->staff->map(fn ($u) => [
                        'id'         => $u->id,
                        'name'       => $u->name,
                        'avatar_url' => $u->avatar_url,
                    ]),
                ])
                ->values()
                ->all();
            return $stage;
        }, $stages);

        return Inertia::render('Bcf/Order', [
            'order'  => $raw['order'],
            'stages' => $stages,
        ]);
    }

    // ── Proxy: stages for a given order (used by job form dropdown) ───────────

    public function stagesForOrder(string $id): JsonResponse
    {
        $raw    = (new BcfApiService())->getOrder($id);
        $stages = $raw['stages'] ?? [];

        return response()->json(
            collect($stages)
                ->sortBy('stage_number')
                ->values()
                ->map(fn ($s) => ['id' => $s['id'], 'label' => $s['label'], 'status' => $s['status'] ?? 'pending'])
        );
    }

    // ── Stage status ──────────────────────────────────────────────────────────

    public function updateStage(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,done'],
        ]);

        (new BcfApiService())->updateStage($id, $data['status']);

        return back()->with('success', 'Stage updated.');
    }

    // ── Task completion ───────────────────────────────────────────────────────

    public function completeTask(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'completed' => ['required', 'boolean'],
            'notes'     => ['nullable', 'string', 'max:2000'],
        ]);

        (new BcfApiService())->completeTask($id, $data['completed'], $data['notes'] ?? null);

        return back()->with('success', 'Task updated.');
    }
}
