<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function __construct()
    {
        abort_unless(
            auth()->check() && auth()->user()->hasRole('admin'),
            403
        );
    }

    public function index(): Response
    {
        return Inertia::render('Businesses/Index', [
            'businesses' => Business::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'code'  => ['required', 'string', 'max:20', 'alpha_dash', 'unique:businesses,code'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $data['code'] = strtolower($data['code']);
        Business::create($data);

        return back()->with('success', "Business \"{$data['name']}\" created.");
    }

    public function update(Request $request, Business $business): RedirectResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'code'  => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('businesses', 'code')->ignore($business->id)],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $data['code'] = strtolower($data['code']);
        $business->update($data);

        return back()->with('success', "Business \"{$business->name}\" updated.");
    }

    public function destroy(Business $business): RedirectResponse
    {
        if (Project::where('business', $business->code)->exists()) {
            return back()->withErrors(['delete' => "Cannot delete \"{$business->name}\" — projects are assigned to it."]);
        }

        $business->delete();

        return back()->with('success', "Business \"{$business->name}\" deleted.");
    }

    public function toggleActive(Business $business): RedirectResponse
    {
        $business->update(['is_active' => ! $business->is_active]);

        $label = $business->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Business \"{$business->name}\" {$label}.");
    }
}
