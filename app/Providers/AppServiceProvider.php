<?php

namespace App\Providers;

use App\Models\Job;
use App\Models\Project;
use App\Models\User;
use App\Models\Van;
use App\Policies\JobPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\StaffPolicy;
use App\Policies\VanPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Job::class,     JobPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(User::class,    StaffPolicy::class);
        Gate::policy(Van::class,     VanPolicy::class);

        Vite::prefetch(concurrency: 3);

        Event::listen(Login::class, function (Login $event) {
            $event->user->updateQuietly(['last_login_at' => now()]);
        });

        // Clock-in / clock-out: 10 per minute per user
        RateLimiter::for('clock-actions', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // QR scan: 60 per minute per user (allows rapid scanning of many staff)
        RateLimiter::for('qr-scan', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
