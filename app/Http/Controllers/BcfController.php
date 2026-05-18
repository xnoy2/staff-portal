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
        $service = new BcfApiService();
        $raw     = $service->getOrders();

        // API may return array directly or wrapped in a key
        $orders = is_array($raw) && isset($raw[0]) ? $raw : ($raw['orders'] ?? $raw['data'] ?? []);

        return Inertia::render('Bcf/Orders', [
            'orders' => $orders,
        ]);
    }

    public function show(string $id): Response|RedirectResponse
    {
        $service = new BcfApiService();
        $raw     = $service->getOrder($id);

        if (empty($raw)) {
            return redirect()->route('bcf.index')->with('error', 'Order not found.');
        }

        // API may return the order directly or wrapped
        $order = $raw['order'] ?? $raw['data'] ?? $raw;

        return Inertia::render('Bcf/Order', [
            'order' => $order,
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
