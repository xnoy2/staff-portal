<?php

namespace App\Http\Controllers;

use App\Models\StaffMedicalDocument;
use App\Models\StaffOnboardingForm;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OnboardingController extends Controller
{
    private function canAccess(User $staff): bool
    {
        return request()->user()->hasAnyRole(['admin', 'manager', 'hr'])
            || request()->user()->id === $staff->id;
    }

    public function show(User $staff): Response
    {
        $this->authorize('view', $staff);

        $form      = StaffOnboardingForm::where('user_id', $staff->id)->first();
        $documents = StaffMedicalDocument::where('user_id', $staff->id)
            ->with('uploadedBy:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($d) => [
                'id'            => $d->id,
                'original_name' => $d->original_name,
                'mime_type'     => $d->mime_type,
                'size'          => $d->size,
                'uploaded_by'   => $d->uploadedBy?->name,
                'uploaded_at'   => $d->created_at->toDateString(),
            ]);

        return Inertia::render('Staff/Onboarding', [
            'staffMember' => [
                'id'                      => $staff->id,
                'name'                    => $staff->name,
                'email'                   => $staff->email,
                'avatar_url'              => $staff->avatar_url,
                'employee_id'             => $staff->employee_id,
                'hire_date'               => $staff->hire_date?->toDateString(),
                'emergency_contact_name'  => $staff->emergency_contact_name,
                'emergency_contact_phone' => $staff->emergency_contact_phone,
            ],
            'form'      => $form ? $this->formPayload($form) : null,
            'documents' => $documents,
            'canEdit'   => $this->canAccess($staff),
        ]);
    }

    public function store(Request $request, User $staff): RedirectResponse
    {
        abort_unless($this->canAccess($staff), 403);

        $data = $request->validate([
            'address'                 => ['nullable', 'string', 'max:500'],
            'phone'                   => ['nullable', 'string', 'max:30'],
            'national_insurance'      => ['nullable', 'string', 'max:20'],
            'emergency_contact'       => ['nullable', 'string', 'max:200'],
            'position'                => ['nullable', 'string', 'max:100'],
            'start_date'              => ['nullable', 'date'],
            'supervisor'              => ['nullable', 'string', 'max:100'],
            'employment_type'         => ['nullable', 'in:full_time,part_time,self_employed'],
            'previous_experience'     => ['nullable', 'string'],
            'qualifications'          => ['nullable', 'string'],
            'driving_licence'         => ['nullable', 'boolean'],
            'own_transport'           => ['nullable', 'boolean'],
            'medical_information'     => ['nullable', 'string'],
            'criminal_convictions'    => ['nullable', 'boolean'],
            'criminal_details'        => ['nullable', 'string'],
            'dbs_consent'             => ['boolean'],
            'dbs_signed_date'         => ['nullable', 'date'],
            'bank_account_name'       => ['nullable', 'string', 'max:100'],
            'bank_sort_code'          => ['nullable', 'string', 'max:10'],
            'bank_account_number'     => ['nullable', 'string', 'max:20'],
            'doc_id'                  => ['boolean'],
            'doc_proof_of_address'    => ['boolean'],
            'doc_cis_utr'             => ['boolean'],
            'doc_tickets'             => ['boolean'],
            'declaration_signed_date' => ['nullable', 'date'],
        ]);

        $existing = StaffOnboardingForm::where('user_id', $staff->id)->first();

        StaffOnboardingForm::updateOrCreate(
            ['user_id' => $staff->id],
            array_merge($data, [
                'created_by' => $existing?->created_by ?? $request->user()->id,
                'updated_by' => $request->user()->id,
            ])
        );

        return back()->with('success', 'Onboarding form saved.');
    }

    public function uploadDocument(Request $request, User $staff): RedirectResponse
    {
        abort_unless($this->canAccess($staff), 403);

        $request->validate([
            'document' => ['required', 'file', 'max:10240', // 10 MB
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        $file       = $request->file('document');
        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path       = $file->storeAs("medical-documents/{$staff->id}", $storedName, 'private');

        StaffMedicalDocument::create([
            'user_id'       => $staff->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_name'   => $storedName,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'path'          => $path,
            'uploaded_by'   => $request->user()->id,
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function downloadDocument(User $staff, StaffMedicalDocument $document): StreamedResponse
    {
        abort_unless($this->canAccess($staff), 403);
        abort_if($document->user_id !== $staff->id, 404);

        abort_unless(Storage::disk('private')->exists($document->path), 404);

        return Storage::disk('private')->download($document->path, $document->original_name);
    }

    public function deleteDocument(Request $request, User $staff, StaffMedicalDocument $document): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);
        abort_if($document->user_id !== $staff->id, 404);

        Storage::disk('private')->delete($document->path);
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }

    private function formPayload(StaffOnboardingForm $f): array
    {
        return [
            'address'                 => $f->address,
            'phone'                   => $f->phone,
            'national_insurance'      => $f->national_insurance,
            'emergency_contact'       => $f->emergency_contact,
            'position'                => $f->position,
            'start_date'              => $f->start_date?->toDateString(),
            'supervisor'              => $f->supervisor,
            'employment_type'         => $f->employment_type,
            'previous_experience'     => $f->previous_experience,
            'qualifications'          => $f->qualifications,
            'driving_licence'         => $f->driving_licence,
            'own_transport'           => $f->own_transport,
            'medical_information'     => $f->medical_information,
            'criminal_convictions'    => $f->criminal_convictions,
            'criminal_details'        => $f->criminal_details,
            'dbs_consent'             => $f->dbs_consent,
            'dbs_signed_date'         => $f->dbs_signed_date?->toDateString(),
            'bank_account_name'       => $f->bank_account_name,
            'bank_sort_code'          => $f->bank_sort_code,
            'bank_account_number'     => $f->bank_account_number,
            'doc_id'                  => $f->doc_id,
            'doc_proof_of_address'    => $f->doc_proof_of_address,
            'doc_cis_utr'             => $f->doc_cis_utr,
            'doc_tickets'             => $f->doc_tickets,
            'declaration_signed_date' => $f->declaration_signed_date?->toDateString(),
            'updated_at'              => $f->updated_at?->toDateTimeString(),
        ];
    }
}
