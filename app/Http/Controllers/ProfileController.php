<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user()->load('projects');

        return Inertia::render('Profile/Edit', [
            'profileUser' => [
                'id'                      => $user->id,
                'employee_id'             => $user->employee_id,
                'name'                    => $user->name,
                'email'                   => $user->email,
                'avatar_url'              => $user->avatar_url,
                'hire_date'               => $user->hire_date?->toDateString(),
                'emergency_contact_name'  => $user->emergency_contact_name,
                'emergency_contact_phone' => $user->emergency_contact_phone,
                'certifications'          => $user->certifications ?? [],
                'notes'                   => $user->notes,
                'roles'                   => $user->getRoleNames(),
                'created_at'              => $user->created_at->toDateString(),
            ],
            'projects' => $user->projects->map(fn ($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'customer' => $p->customer,
                'business' => $p->business,
                'status'   => $p->status,
                'role'     => $p->pivot->role,
            ]),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->except('avatar');

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('r2')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'r2');
            $data['avatar'] = $path;
        }

        if ($request->user()->isDirty('email')) {
            $data['email_verified_at'] = null;
        }

        $user->fill($data)->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $request->user()->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
