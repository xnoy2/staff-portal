<?php

namespace App\Http\Controllers;

use App\Models\Van;
use App\Models\VanAllocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VanAllocationController extends Controller
{
    public function __construct()
    {
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['admin', 'manager']),
            403
        );
    }

    public function store(Request $request, Van $van): RedirectResponse
    {
        $data = $request->validate([
            'project_id'     => ['nullable', 'exists:projects,id'],
            'allocated_from' => ['required', 'date'],
            'allocated_to'   => ['required', 'date', 'gte:allocated_from'],
            'purpose'        => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ]);

        $van->allocations()->create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Allocation added.');
    }

    public function update(Request $request, Van $van, VanAllocation $allocation): RedirectResponse
    {
        abort_unless($allocation->van_id === $van->id, 404);

        $data = $request->validate([
            'project_id'     => ['nullable', 'exists:projects,id'],
            'allocated_from' => ['required', 'date'],
            'allocated_to'   => ['required', 'date', 'gte:allocated_from'],
            'purpose'        => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ]);

        $allocation->update($data);

        return back()->with('success', 'Allocation updated.');
    }

    public function destroy(Van $van, VanAllocation $allocation): RedirectResponse
    {
        abort_unless($allocation->van_id === $van->id, 404);

        $allocation->delete();

        return back()->with('success', 'Allocation removed.');
    }
}
