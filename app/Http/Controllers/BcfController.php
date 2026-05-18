<?php

namespace App\Http\Controllers;

use App\Services\BcfApiService;
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

        // Admins and managers see all orders; other staff see only their assigned ones
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

        return Inertia::render('Bcf/Order', [
            'order'  => $raw['order'],
            'stages' => $raw['stages'] ?? [],
        ]);
    }

    public function updateStage(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,done'],
        ]);

        (new BcfApiService())->updateStage($id, $data['status']);

        return back()->with('success', 'Stage updated.');
    }

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
