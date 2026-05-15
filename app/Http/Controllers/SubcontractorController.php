<?php

namespace App\Http\Controllers;

use App\Models\Subcontractor;
use App\Models\SubcontractorPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SubcontractorController extends Controller
{
    private function authorise(): void
    {
        abort_unless(request()->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);
    }

    public function index(Request $request): Response
    {
        abort_unless(request()->user()->hasAnyRole(['admin', 'manager', 'hr', 'site_head']), 403);

        $query = Subcontractor::with(['photos'])
            ->orderBy('trade')
            ->orderBy('name');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($qb) =>
                $qb->where('name', 'like', "%{$q}%")
                   ->orWhere('company', 'like', "%{$q}%")
                   ->orWhere('trade', 'like', "%{$q}%")
            );
        }

        if ($request->filled('trade')) {
            $query->where('trade', $request->trade);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $subcontractors = $query->get()->map(fn ($s) => $this->payload($s));

        return Inertia::render('Subcontractors/Index', [
            'subcontractors' => $subcontractors,
            'trades'         => self::TRADES,
            'filters'        => $request->only(['search', 'trade', 'status']),
            'canEdit'        => request()->user()->hasAnyRole(['admin', 'manager', 'hr']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorise();

        $data = $request->validate([
            'name'                   => ['required', 'string', 'max:255'],
            'trade'                  => ['required', 'string'],
            'company'                => ['nullable', 'string', 'max:255'],
            'email'                  => ['nullable', 'email', 'max:255'],
            'phone'                  => ['nullable', 'string', 'max:30'],
            'qualification_verified' => ['boolean'],
            'insurance_verified'     => ['boolean'],
            'notes'                  => ['nullable', 'string'],
            'is_active'              => ['boolean'],
        ]);

        Subcontractor::create(array_merge($data, ['created_by' => $request->user()->id]));

        return back()->with('success', 'Subcontractor added.');
    }

    public function update(Request $request, Subcontractor $subcontractor): RedirectResponse
    {
        $this->authorise();

        $data = $request->validate([
            'name'                   => ['required', 'string', 'max:255'],
            'trade'                  => ['required', 'string'],
            'company'                => ['nullable', 'string', 'max:255'],
            'email'                  => ['nullable', 'email', 'max:255'],
            'phone'                  => ['nullable', 'string', 'max:30'],
            'qualification_verified' => ['boolean'],
            'insurance_verified'     => ['boolean'],
            'notes'                  => ['nullable', 'string'],
            'is_active'              => ['boolean'],
        ]);

        $subcontractor->update($data);

        return back()->with('success', 'Subcontractor updated.');
    }

    public function destroy(Subcontractor $subcontractor): RedirectResponse
    {
        $this->authorise();

        // Delete photos from storage
        foreach ($subcontractor->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }

        $subcontractor->delete();

        return back()->with('success', 'Subcontractor removed.');
    }

    public function uploadPhoto(Request $request, Subcontractor $subcontractor): RedirectResponse
    {
        $this->authorise();

        $request->validate([
            'photo'   => ['required', 'image', 'max:5120'], // 5 MB
            'type'    => ['required', 'in:before,after'],
            'caption' => ['nullable', 'string', 'max:200'],
        ]);

        $file       = $request->file('photo');
        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path       = $file->storeAs("subcontractors/{$subcontractor->id}", $storedName, 'public');

        SubcontractorPhoto::create([
            'subcontractor_id' => $subcontractor->id,
            'type'             => $request->type,
            'path'             => $path,
            'original_name'    => $file->getClientOriginalName(),
            'caption'          => $request->caption,
            'uploaded_by'      => $request->user()->id,
        ]);

        return back()->with('success', 'Photo uploaded.');
    }

    public function deletePhoto(Subcontractor $subcontractor, SubcontractorPhoto $photo): RedirectResponse
    {
        $this->authorise();
        abort_if($photo->subcontractor_id !== $subcontractor->id, 404);

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return back()->with('success', 'Photo deleted.');
    }

    private function payload(Subcontractor $s): array
    {
        return [
            'id'                     => $s->id,
            'name'                   => $s->name,
            'trade'                  => $s->trade,
            'company'                => $s->company,
            'email'                  => $s->email,
            'phone'                  => $s->phone,
            'qualification_verified' => $s->qualification_verified,
            'insurance_verified'     => $s->insurance_verified,
            'notes'                  => $s->notes,
            'is_active'              => $s->is_active,
            'photos'                 => $s->photos->map(fn ($p) => [
                'id'            => $p->id,
                'type'          => $p->type,
                'url'           => $p->url,
                'original_name' => $p->original_name,
                'caption'       => $p->caption,
                'uploaded_at'   => $p->created_at->toDateString(),
            ])->values(),
        ];
    }

    const TRADES = [
        'Groundworker / Concrete Team',
        'Joiner / Carpenter',
        'Roofing Contractor',
        'Electrician',
        'Plumber',
        'Dryliner / Insulation Installer',
        'Painter & Decorator',
        'Flooring Installer',
        'Steel Fabricator',
        'Tiler',
        'Kitchen Fitter',
        'Labourer',
        'Digger Driver',
        'Waste Removal',
        'Skip Hire',
        'Resin Install',
        'Plasterer',
    ];
}
