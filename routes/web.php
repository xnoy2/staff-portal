<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\VanAllocationController;
use App\Http\Controllers\VanController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/schedule',                              [ScheduleController::class, 'index'])->name('schedule');
    Route::get('/calendar',                              [CalendarController::class,  'index'])->name('calendar');
    Route::post('/schedule/staff',                       [ScheduleController::class, 'store'])->name('schedule.staff.store');
    Route::delete('/schedule/staff/{staffSchedule}',     [ScheduleController::class, 'destroyEntry'])->name('schedule.staff.destroy');

    // Attendance
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/',                [AttendanceController::class, 'index'])->name('index');
        Route::post('/clock-in',       [AttendanceController::class, 'clockIn'])->name('clock-in')->middleware('throttle:clock-actions');
        Route::post('/clock-out',      [AttendanceController::class, 'clockOut'])->name('clock-out')->middleware('throttle:clock-actions');
        Route::get('/active-entry',    [AttendanceController::class, 'activeEntry'])->name('active-entry');
        Route::post('/break/start',    [AttendanceController::class, 'startBreak'])->name('break.start');
        Route::post('/break/end',      [AttendanceController::class, 'endBreak'])->name('break.end');
        Route::post('/bulk-approve',   [AttendanceController::class, 'bulkApprove'])->name('bulk-approve');
        Route::post('/manual',         [AttendanceController::class, 'manual'])->name('manual');
        Route::get('/export',          [AttendanceController::class, 'export'])->name('export');
        Route::post('/{timeEntry}/approve', [AttendanceController::class, 'approve'])->name('approve');
        Route::post('/{timeEntry}/reject',  [AttendanceController::class, 'reject'])->name('reject');
        Route::post('/scan',           [QrScanController::class, 'scan'])->name('scan')->middleware('throttle:qr-scan');
    });

    // QR Scanner page
    Route::get('/qr-scanner', [QrScanController::class, 'show'])->name('qr-scanner');

    // My QR Code page (all authenticated staff)
    Route::get('/my-qr', [QrScanController::class, 'myQr'])->name('my-qr');

    // Projects
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/checklist',                   [ProjectController::class, 'addChecklistItem'])->name('projects.checklist.add');
    Route::patch('/projects/{project}/checklist/{item}/toggle',    [ProjectController::class, 'toggleChecklistItem'])->name('projects.checklist.toggle');
    Route::delete('/projects/{project}/checklist/{item}',          [ProjectController::class, 'deleteChecklistItem'])->name('projects.checklist.delete');

    // Live Board / Jobs
    Route::prefix('jobs')->name('jobs.')->group(function () {
        Route::get('/',                      [JobController::class, 'index'])->name('index');
        Route::post('/',                     [JobController::class, 'store'])->name('store');
        Route::put('/{job}',                 [JobController::class, 'update'])->name('update');
        Route::patch('/{job}/status',        [JobController::class, 'updateStatus'])->name('status');
        Route::delete('/{job}',              [JobController::class, 'destroy'])->name('destroy');
    });

    // Leave
    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/',                          [LeaveController::class, 'index'])->name('index');
        Route::post('/',                         [LeaveController::class, 'store'])->name('store');
        Route::post('/{leave}/approve',          [LeaveController::class, 'approve'])->name('approve');
        Route::post('/{leave}/reject',           [LeaveController::class, 'reject'])->name('reject');
        Route::delete('/{leave}',                [LeaveController::class, 'destroy'])->name('destroy');
    });

    // Vans
    Route::resource('vans', VanController::class);
    Route::post('/vans/{van}/toggle-active', [VanController::class, 'toggleActive'])->name('vans.toggle-active');

    // Van Allocations (nested under van)
    Route::post('/vans/{van}/allocations',                          [VanAllocationController::class, 'store'])->name('vans.allocations.store');
    Route::put('/vans/{van}/allocations/{allocation}',              [VanAllocationController::class, 'update'])->name('vans.allocations.update');
    Route::delete('/vans/{van}/allocations/{allocation}',           [VanAllocationController::class, 'destroy'])->name('vans.allocations.destroy');

    // Van Staff assignment
    Route::post('/vans/{van}/staff',              [VanController::class, 'assignStaff'])->name('vans.staff.assign');
    Route::delete('/vans/{van}/staff/{user}',     [VanController::class, 'unassignStaff'])->name('vans.staff.unassign');

    // Businesses (admin only)
    Route::get('/businesses',                               [BusinessController::class, 'index'])->name('businesses.index');
    Route::post('/businesses',                              [BusinessController::class, 'store'])->name('businesses.store');
    Route::put('/businesses/{business}',                    [BusinessController::class, 'update'])->name('businesses.update');
    Route::delete('/businesses/{business}',                 [BusinessController::class, 'destroy'])->name('businesses.destroy');
    Route::post('/businesses/{business}/toggle-active',     [BusinessController::class, 'toggleActive'])->name('businesses.toggle-active');

    // Staff management
    Route::resource('staff', StaffController::class);
    Route::post('/staff/{staff}/toggle-active',       [StaffController::class, 'toggleActive'])->name('staff.toggle-active');
    Route::post('/staff/{staff}/force-password-reset',[StaffController::class, 'forcePasswordReset'])->name('staff.force-password-reset');

    // Audit Log (admin/manager only)
    Route::get('/audit-log', [AuditController::class, 'index'])->name('audit-log');

    // Reports (admin/manager only)
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');

    // Settings
    Route::get('/settings',              [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings',             [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences');

    // Profile
    Route::get('/profile',             [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile',            [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password',    [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile',          [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/change-password',  [ChangePasswordController::class, 'show'])->name('password.change');
    Route::post('/change-password', [ChangePasswordController::class, 'update'])->name('password.change.update');
});

// ── Dev Console (separate session auth) ──────────────────────────────────────
Route::prefix('dev')->name('dev.')->group(function () {
    Route::get('/login',   [\App\Http\Controllers\DevController::class, 'showLogin'])->name('login');
    Route::post('/login',  [\App\Http\Controllers\DevController::class, 'login'])->name('authenticate');
    Route::post('/logout', [\App\Http\Controllers\DevController::class, 'logout'])->name('logout');
    Route::middleware(\App\Http\Middleware\DevAuth::class)->group(function () {
        Route::get('/', [\App\Http\Controllers\DevController::class, 'index'])->name('dashboard');
    });
});

require __DIR__.'/auth.php';
