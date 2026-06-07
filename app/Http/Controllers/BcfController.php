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
        $user         = auth()->user();
        $isPrivileged = $user->hasAnyRole(['admin', 'manager', 'hr']);
        $error        = null;
        $orders       = [];

        try {
            $raw  = (new BcfApiService())->getOrders();
            $all  = $raw['orders'] ?? [];

            if ($isPrivileged || ! $user->bcf_worker_id) {
                $orders = $all;
            } else {
                // Cast both sides to string — BCF API may return integer IDs
                $workerId = (string) $user->bcf_worker_id;
                $orders   = array_values(array_filter(
                    $all,
                    fn ($o) => (string) ($o['worker']['id'] ?? '') === $workerId
                ));
            }
        } catch (\Throwable $e) {
            \Log::error('BCF API error (index): ' . $e->getMessage());
            $error = 'Could not reach the BCF system. Please try again shortly.';
        }

        return Inertia::render('Bcf/Orders', [
            'orders'       => $orders,
            'isPrivileged' => $isPrivileged,
            'linked'       => (bool) $user->bcf_worker_id,
            'error'        => $error,
        ]);
    }

    public function show(string $id): Response|RedirectResponse
    {
        try {
            $raw = (new BcfApiService())->getOrder($id);
        } catch (\Throwable $e) {
            \Log::error('BCF API error (show): ' . $e->getMessage());
            return redirect()->route('bcf.index')->with('error', 'Could not reach the BCF system. Please try again shortly.');
        }

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
        try {
            $raw = (new BcfApiService())->getOrder($id);
        } catch (\Throwable $e) {
            \Log::error('BCF API error (stagesForOrder): ' . $e->getMessage());
            return response()->json([], 502);
        }
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
