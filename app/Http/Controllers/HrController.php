<?php

namespace App\Http\Controllers;

use App\Models\StaffAgreement;
use App\Models\StaffDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrController extends Controller
{
    /** HR/admin/manager may manage documents & issue agreements. */
    private function canManage(): bool
    {
        return request()->user()->hasAnyRole(['admin', 'manager', 'hr']);
    }

    // ── Documents ─────────────────────────────────────────────────────────

    public function uploadDocument(Request $request, User $staff): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(StaffDocument::CATEGORIES))],
            'title'    => ['nullable', 'string', 'max:150'],
            'document' => ['required', 'file', 'max:15360', // 15 MB
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        $file       = $request->file('document');
        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        // Stored on r2 (durable) under an unguessable key; only ever streamed
        // back through the authenticated download() route, never a public URL.
        $path = $file->storeAs("hr-documents/{$staff->id}", $storedName, 'r2');

        StaffDocument::create([
            'user_id'       => $staff->id,
            'category'      => $data['category'],
            'title'         => $data['title'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'path'          => $path,
            'uploaded_by'   => $request->user()->id,
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function downloadDocument(Request $request, User $staff, StaffDocument $document): StreamedResponse
    {
        // Managers/HR/admin, or the staff member viewing their own file.
        abort_unless($this->canManage() || $request->user()->id === $staff->id, 403);
        abort_if($document->user_id !== $staff->id, 404);
        abort_unless(Storage::disk('r2')->exists($document->path), 404);

        return Storage::disk('r2')->download($document->path, $document->original_name);
    }

    public function deleteDocument(Request $request, User $staff, StaffDocument $document): RedirectResponse
    {
        abort_unless($this->canManage(), 403);
        abort_if($document->user_id !== $staff->id, 404);

        Storage::disk('r2')->delete($document->path);
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }

    // ── Agreements ────────────────────────────────────────────────────────

    public function issueAgreement(Request $request, User $staff): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        $data = $request->validate([
            'type'           => ['required', Rule::in(array_keys(StaffAgreement::TYPES))],
            'title'          => ['nullable', 'string', 'max:150'],
            'company'        => ['required', 'string', 'max:150'],
            'duration_years' => ['nullable', 'integer', 'min:0', 'max:99'],
            'radius_miles'   => ['nullable', 'integer', 'min:0', 'max:9999'],
            'body'           => ['nullable', 'string', 'max:20000'],
        ]);

        $body = $data['body'] ?? null;
        if (blank($body)) {
            abort_if($data['type'] !== 'non_compete', 422, 'Agreement body is required.');
            $body = StaffAgreement::defaultBody(
                $data['company'],
                (int) ($data['duration_years'] ?? 4),
                (int) ($data['radius_miles'] ?? 50),
            );
        }

        $nextVersion = StaffAgreement::where('user_id', $staff->id)
            ->where('type', $data['type'])
            ->max('version') + 1;

        StaffAgreement::create([
            'user_id'        => $staff->id,
            'type'           => $data['type'],
            'title'          => $data['title'] ?: StaffAgreement::TYPES[$data['type']],
            'body'           => $body,
            'version'        => $nextVersion,
            'duration_years' => $data['duration_years'] ?? null,
            'radius_miles'   => $data['radius_miles'] ?? null,
            'status'         => 'pending',
            'issued_by'      => $request->user()->id,
            'issued_at'      => now(),
        ]);

        return back()->with('success', 'Agreement issued. The staff member will be asked to sign it.');
    }

    public function showAgreement(Request $request, StaffAgreement $agreement): Response
    {
        $viewer  = $request->user();
        $isOwner = $viewer->id === $agreement->user_id;
        abort_unless($isOwner || $this->canManage(), 403);

        $agreement->loadMissing(['user:id,name,employee_id', 'issuedBy:id,name']);
        $tz = $viewer->timezone;

        return Inertia::render('Agreements/Show', [
            'agreement' => [
                'id'                => $agreement->id,
                'type'              => $agreement->type,
                'type_label'        => StaffAgreement::TYPES[$agreement->type] ?? 'Agreement',
                'title'             => $agreement->title,
                'body'              => $agreement->body,
                'version'           => $agreement->version,
                'duration_years'    => $agreement->duration_years,
                'radius_miles'      => $agreement->radius_miles,
                'status'            => $agreement->status,
                'employee_name'     => $agreement->user?->name,
                'employee_id'       => $agreement->user?->employee_id,
                'issued_by'         => $agreement->issuedBy?->name,
                'issued_at'         => $agreement->issued_at?->copy()->setTimezone($tz)->format('j F Y'),
                'acknowledged_at'   => $agreement->acknowledged_at?->copy()->setTimezone($tz)->format('j F Y, g:i A'),
                'acknowledged_name' => $agreement->acknowledged_name,
                'acknowledged_ip'   => $agreement->acknowledged_ip,
            ],
            'canSign' => $isOwner && $agreement->isPending(),
            'isOwner' => $isOwner,
        ]);
    }

    public function acknowledgeAgreement(Request $request, StaffAgreement $agreement): RedirectResponse
    {
        // Only the employee it was issued to can sign it, and only while pending.
        abort_unless($request->user()->id === $agreement->user_id, 403);
        abort_unless($agreement->isPending(), 409, 'This agreement has already been actioned.');

        $data = $request->validate([
            'agree'     => ['accepted'],
            'full_name' => ['required', 'string', 'max:150'],
        ]);

        $agreement->update([
            'status'            => 'acknowledged',
            'acknowledged_at'   => now(),
            'acknowledged_ip'   => $request->ip(),
            'acknowledged_name' => trim($data['full_name']),
        ]);

        return redirect()
            ->route('agreements.show', $agreement->id)
            ->with('success', 'Agreement signed. Thank you.');
    }

    public function deleteAgreement(Request $request, StaffAgreement $agreement): RedirectResponse
    {
        abort_unless($this->canManage(), 403);
        // Signed agreements are legal records — never delete them, only pending ones.
        abort_unless($agreement->isPending(), 409, 'A signed agreement cannot be deleted.');

        $agreement->delete();

        return back()->with('success', 'Pending agreement removed.');
    }
}
