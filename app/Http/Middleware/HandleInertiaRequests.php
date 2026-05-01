<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'is_active'  => $user->is_active,
                    'roles'      => $user->getRoleNames(),
                ] : null,
            ],
            'permissions' => $user
                ? $user->getAllPermissions()->pluck('name')
                : [],
            'appSettings' => Cache::remember('app_settings', 300, function () {
                try {
                    return [
                        'app_name'      => Setting::get('app_name', 'Staff Portal'),
                        'company_name'  => Setting::get('company_name', 'BCF'),
                        'primary_color' => Setting::get('primary_color', '#EF233C'),
                        'sidebar_color' => Setting::get('sidebar_color', '#2B2D42'),
                    ];
                } catch (\Throwable) {
                    return ['app_name' => 'Staff Portal', 'company_name' => 'BCF', 'primary_color' => '#EF233C', 'sidebar_color' => '#2B2D42'];
                }
            }),
            'flash' => [
                'success'       => session('success'),
                'error'         => session('error'),
                'warning'       => session('warning'),
                'info'          => session('info'),
                'temp_password' => session('temp_password'),
            ],
        ];
    }
}
