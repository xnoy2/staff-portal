<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class QrScanController extends Controller
{
    public function show(): Response
    {
        abort_unless(
            auth()->user()->hasAnyRole(['admin', 'manager', 'site_head']),
            403,
            'QR scanning requires site head or higher role.'
        );

        return Inertia::render('Attendance/Scan');
    }

    public function myQr(): Response
    {
        $user = auth()->user();

        $recentEntries = \App\Models\TimeEntry::forUser($user->id)
            ->approved()
            ->latest('clock_in')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id'        => $e->id,
                'date'      => $e->clock_in->toDateString(),
                'clock_in'  => $e->clock_in->format('H:i'),
                'clock_out' => $e->clock_out?->format('H:i'),
                'hours'     => $e->total_hours,
            ]);

        return Inertia::render('MyQr', [
            'qrPayload'     => base64_encode($user->id),
            'recentEntries' => $recentEntries,
        ]);
    }

    public function scan(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->hasAnyRole(['admin', 'manager', 'site_head']),
            403
        );

        $request->validate([
            'scanned_data' => 'required|string|max:512',
        ]);

        // Decode: payload is base64(uuid)
        $decoded = base64_decode($request->input('scanned_data'), strict: true);

        if ($decoded === false || ! Str::isUuid($decoded)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code. Could not identify a staff member.',
            ], 422);
        }

        $scannedUser = User::find($decoded);

        if (! $scannedUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found for this QR code.',
            ], 404);
        }

        if (! $scannedUser->is_active) {
            return response()->json([
                'success' => false,
                'message' => "Account for {$scannedUser->name} is deactivated.",
            ], 403);
        }

        $existingEntry = TimeEntry::active()->forUser($scannedUser->id)->first();

        if ($existingEntry) {
            // Clock out
            $existingEntry->clock_out = now();
            $existingEntry->calculateHours();
            $existingEntry->save();

            activity()
                ->performedOn($existingEntry)
                ->causedBy($request->user())
                ->log('qr_clock_out');

            return response()->json([
                'success'  => true,
                'action'   => 'clock_out',
                'user'     => ['id' => $scannedUser->id, 'name' => $scannedUser->name, 'avatar_url' => $scannedUser->avatar_url],
                'duration' => $existingEntry->duration_label,
                'message'  => "Clocked out {$scannedUser->name} after {$existingEntry->duration_label}.",
            ]);
        }

        // Clock in — auto-approve if manager is scanning
        $autoApprove = $request->user()->hasAnyRole(['admin', 'manager']);

        $entry = TimeEntry::create([
            'user_id'    => $scannedUser->id,
            'clock_in'   => now(),
            'source'     => 'site_head',
            'status'     => $autoApprove ? 'approved' : 'pending',
            'entered_by' => $request->user()->id,
            'approved_by' => $autoApprove ? $request->user()->id : null,
            'approved_at' => $autoApprove ? now() : null,
        ]);

        activity()
            ->performedOn($entry)
            ->causedBy($request->user())
            ->log('qr_clock_in');

        return response()->json([
            'success' => true,
            'action'  => 'clock_in',
            'user'    => ['id' => $scannedUser->id, 'name' => $scannedUser->name, 'avatar_url' => $scannedUser->avatar_url],
            'message' => "Clocked in {$scannedUser->name} at " . now()->format('H:i') . '.',
        ]);
    }
}
