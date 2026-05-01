<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'settings'    => Setting::allCast(),
            'preferences' => auth()->user()->preferences ?? [],
        ]);
    }

    /** Save system settings — admin only. */
    public function update(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        foreach ($request->except('_token') as $key => $value) {
            $setting = Setting::find($key);
            if ($setting) {
                $setting->value = is_bool($value) ? (int) $value : $value;
                $setting->save();
            }
        }

        Cache::forget('app_settings');

        return back()->with('success', 'Settings saved.');
    }

    /** Save the authenticated user's own preferences. */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $user->update([
            'preferences' => array_merge($user->preferences ?? [], $request->except('_token')),
        ]);

        return back()->with('success', 'Preferences saved.');
    }
}
