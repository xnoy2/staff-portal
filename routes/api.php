<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\StaffController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Read-only integration API (Bearer token via Laravel Sanctum)
|--------------------------------------------------------------------------
| Consumed by the CEO Dashboard. All routes require a personal access token:
|     Authorization: Bearer <token>
|     Accept: application/json
| Mint tokens with:  php artisan api:token "CEO Dashboard"
*/

// Unauthenticated health check — handy for the integrator to verify reachability.
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'time'   => now()->toIso8601String(),
]));

Route::middleware('auth:sanctum')->group(function () {
    // Identity of the token holder.
    Route::get('/me', fn (Request $request) => response()->json([
        'id'    => $request->user()->id,
        'name'  => $request->user()->name,
        'email' => $request->user()->email,
        'roles' => $request->user()->getRoleNames(),
    ]));

    // Consolidated CEO snapshot.
    Route::get('/dashboard', [DashboardController::class, 'snapshot']);

    // Staff
    Route::get('/staff',         [StaffController::class, 'index']);
    Route::get('/staff/summary', [StaffController::class, 'summary']);

    // Attendance
    Route::get('/attendance',         [AttendanceController::class, 'index']);
    Route::get('/attendance/summary', [AttendanceController::class, 'summary']);

    // Payroll
    Route::get('/payroll/runs',    [PayrollController::class, 'runs']);
    Route::get('/payroll/summary', [PayrollController::class, 'summary']);

    // Jobs / Projects
    Route::get('/jobs',         [JobController::class, 'index']);
    Route::get('/jobs/summary', [JobController::class, 'summary']);
});
