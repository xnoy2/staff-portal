<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BcfController;
use App\Http\Controllers\BgrController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayrollExportController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\VanAllocationController;
use App\Http\Controllers\VanController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\HrController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\SubcontractorController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\QrScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/guide', [HelpController::class, 'guide'])->name('guide');
    Route::get('/schedule',                              [ScheduleController::class, 'index'])->name('schedule');
    Route::get('/calendar',                              [CalendarController::class,  'index'])->name('calendar');
    Route::post('/schedule/staff',                       [ScheduleController::class, 'store'])->name('schedule.staff.store');
    Route::post('/schedule/staff/pattern',               [ScheduleController::class, 'setWeeklyPattern'])->name('schedule.staff.pattern');
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
        Route::patch('/{timeEntry}',         [AttendanceController::class, 'update'])->name('update');
        Route::post('/{timeEntry}/approve',  [AttendanceController::class, 'approve'])->name('approve');
        Route::post('/{timeEntry}/reject',   [AttendanceController::class, 'reject'])->name('reject');
        Route::post('/{user}/force-out',     [AttendanceController::class, 'forceClockOut'])->name('force-out');
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
        Route::get('/list',                  [JobController::class, 'myJobs'])->name('list');
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

    // Daily log — staff "My Day" (EOD summary + day photos + job tags)
    Route::get('/my-day', [DailyLogController::class, 'myDay'])->name('my-day');
    Route::prefix('daily-log')->name('daily-log.')->group(function () {
        Route::post('/photo',              [DailyLogController::class, 'uploadPhoto'])->name('photo');
        Route::post('/save',               [DailyLogController::class, 'saveLog'])->name('save');
        Route::post('/{dailyLog}/reopen',  [DailyLogController::class, 'reopenLog'])->name('reopen');
    });

    // Manager: team activity logs
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('/',                    [DailyLogController::class, 'managerIndex'])->name('index');
        Route::get('/export',              [DailyLogController::class, 'export'])->name('export');
        Route::get('/{dailyLog}',          [DailyLogController::class, 'managerShow'])->name('show');
        Route::post('/{dailyLog}/acknowledge', [DailyLogController::class, 'acknowledge'])->name('acknowledge');
    });

    // Vans
    Route::resource('vans', VanController::class);
    Route::post('/vans/{van}/toggle-active', [VanController::class, 'toggleActive'])->name('vans.toggle-active');

    // Van Allocations (nested under van)
    Route::post('/vans/{van}/allocations',                          [VanAllocationController::class, 'store'])->name('vans.allocations.store');
    Route::put('/vans/{van}/allocations/{allocation}',              [VanAllocationController::class, 'update'])->name('vans.allocations.update');
    Route::delete('/vans/{van}/allocations/{allocation}',           [VanAllocationController::class, 'destroy'])->name('vans.allocations.destroy');

    // Van assignment (individual, with history)
    Route::post('/vans/{van}/assign',  [VanController::class, 'assign'])->name('vans.assign');
    Route::post('/vans/{van}/return',  [VanController::class, 'returnVan'])->name('vans.return');

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
    Route::get('/staff/{staff}/onboarding',                                    [OnboardingController::class, 'show'])->name('staff.onboarding');
    Route::post('/staff/{staff}/onboarding',                                   [OnboardingController::class, 'store'])->name('staff.onboarding.store');
    Route::post('/staff/{staff}/onboarding/documents',                         [OnboardingController::class, 'uploadDocument'])->name('staff.onboarding.documents.upload');
    Route::get('/staff/{staff}/onboarding/documents/{document}/download',      [OnboardingController::class, 'downloadDocument'])->name('staff.onboarding.documents.download');
    Route::delete('/staff/{staff}/onboarding/documents/{document}',            [OnboardingController::class, 'deleteDocument'])->name('staff.onboarding.documents.delete');

    // HR: contracts / legal documents + restrictive-covenant agreements
    Route::post('/staff/{staff}/documents',                       [HrController::class, 'uploadDocument'])->name('staff.documents.upload');
    Route::get('/staff/{staff}/documents/{document}/download',    [HrController::class, 'downloadDocument'])->name('staff.documents.download');
    Route::delete('/staff/{staff}/documents/{document}',          [HrController::class, 'deleteDocument'])->name('staff.documents.delete');
    Route::post('/staff/{staff}/agreements',                      [HrController::class, 'issueAgreement'])->name('staff.agreements.issue');
    Route::get('/agreements/{agreement}',                         [HrController::class, 'showAgreement'])->name('agreements.show');
    Route::post('/agreements/{agreement}/acknowledge',            [HrController::class, 'acknowledgeAgreement'])->name('agreements.acknowledge');
    Route::delete('/agreements/{agreement}',                      [HrController::class, 'deleteAgreement'])->name('agreements.delete');

    Route::get('/my-payslip',                          [PayslipController::class, 'mine'])->name('my-payslip');
    Route::get('/staff/{staff}/payslip',              [PayslipController::class, 'show'])->name('staff.payslip');

    // Notifications
    Route::post('/notifications/{id}/read',  [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',   [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Audit Log (admin/manager only)
    Route::get('/audit-log', [AuditController::class, 'index'])->name('audit-log');

    // Reports (admin/manager only)
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');
    Route::get('/reports/payroll-export', [PayrollExportController::class, 'export'])->name('payroll.export');

    // Payroll runs
    Route::get('/payroll',                          [PayrollRunController::class, 'index'])->name('payroll.index');
    Route::post('/payroll',                         [PayrollRunController::class, 'store'])->name('payroll.store');
    Route::post('/payroll/{run}/approve',           [PayrollRunController::class, 'approve'])->name('payroll.approve');
    Route::post('/payroll/approve-all',             [PayrollRunController::class, 'approveAll'])->name('payroll.approve-all');
    Route::post('/payroll/cutoff',                  [PayrollRunController::class, 'updateCutoff'])->name('payroll.cutoff');
    Route::patch('/payroll/{run}/deductions',       [PayrollRunController::class, 'updateDeductions'])->name('payroll.deductions');
    Route::delete('/payroll/{run}',                 [PayrollRunController::class, 'destroy'])->name('payroll.destroy');
    Route::post('/payroll/check',                   [PayrollRunController::class, 'checkPeriod'])->name('payroll.check');
    Route::post('/payroll/send',                    [PayrollRunController::class, 'sendPayroll'])->name('payroll.send');

    // BCF Orders
    Route::prefix('bcf')->name('bcf.')->group(function () {
        Route::get('/',                      [BcfController::class, 'index'])->name('index');
        Route::get('/orders/{id}',           [BcfController::class, 'show'])->name('show');
        Route::get('/orders/{id}/stages',    [BcfController::class, 'stagesForOrder'])->name('order.stages');
        Route::patch('/stages/{id}',         [BcfController::class, 'updateStage'])->name('stages.update');
        Route::patch('/tasks/{id}',          [BcfController::class, 'completeTask'])->name('tasks.complete');
    });

    // BGR Client Projects
    Route::prefix('client-projects')->name('bgr.')->group(function () {
        Route::get('/',                                                                     [BgrController::class, 'index'])->name('index');
        Route::get('/photo',                                                                [BgrController::class, 'photo'])->name('photo');
        Route::post('/debug-session',                                                       [BgrController::class, 'debugSession'])->name('debug-session');
        Route::post('/connect',                                                             [BgrController::class, 'connect'])->name('connect');
        Route::delete('/disconnect',                                                        [BgrController::class, 'disconnect'])->name('disconnect');
        Route::get('/{id}/stages',                                                          [BgrController::class, 'stagesForProject'])->name('project.stages');
        Route::get('/{id}',                                                                 [BgrController::class, 'show'])->name('show');
        Route::post('/{projectId}/tasks/{stageId}/{substageId}/toggle',                    [BgrController::class, 'toggleTask'])->name('tasks.toggle');
        Route::post('/{projectId}/tasks/{stageId}/{substageId}/note',                      [BgrController::class, 'updateTaskNote'])->name('tasks.note');
        Route::post('/{projectId}/updates',                                                 [BgrController::class, 'storeUpdate'])->name('updates.store');
    });

    // Training
    Route::prefix('training')->name('training.')->group(function () {
        Route::get('/',                                          [TrainingController::class, 'index'])->name('index');
        Route::get('/stream/{lesson}',                           [TrainingController::class, 'stream'])->name('stream');
        Route::get('/{module}',                                  [TrainingController::class, 'module'])->name('module');
        Route::get('/{module}/certificate',                      [TrainingController::class, 'certificate'])->name('certificate');
        Route::get('/{module}/{lesson}',                         [TrainingController::class, 'watch'])->name('watch');
        Route::post('/{lesson}/progress',                        [TrainingController::class, 'updateProgress'])->name('progress');
        // Admin / Manager
        Route::post('/modules',                                  [TrainingController::class, 'storeModule'])->name('modules.store');
        Route::patch('/modules/{module}',                        [TrainingController::class, 'updateModule'])->name('modules.update');
        Route::post('/modules/{module}/toggle',                  [TrainingController::class, 'toggleModule'])->name('modules.toggle');
        Route::delete('/modules/{module}',                       [TrainingController::class, 'destroyModule'])->name('modules.destroy');
        Route::post('/modules/{module}/enrollments',             [TrainingController::class, 'manageEnrollments'])->name('modules.enrollments');
        Route::post('/modules/{module}/lessons',                 [TrainingController::class, 'storeLesson'])->name('lessons.store');
        Route::patch('/lessons/{lesson}',                        [TrainingController::class, 'updateLesson'])->name('lessons.update');
        Route::post('/lessons/{lesson}/toggle',                  [TrainingController::class, 'toggleLesson'])->name('lessons.toggle');
        Route::delete('/lessons/{lesson}',                       [TrainingController::class, 'destroyLesson'])->name('lessons.destroy');
    });

    // Overtime
    Route::prefix('overtime')->name('overtime.')->group(function () {
        Route::get('/',                           [OvertimeController::class, 'index'])->name('index');
        Route::post('/',                          [OvertimeController::class, 'store'])->name('store');
        Route::put('/{overtimeRequest}',          [OvertimeController::class, 'update'])->name('update');
        Route::delete('/{overtimeRequest}',       [OvertimeController::class, 'destroy'])->name('destroy');
        Route::post('/{overtimeRequest}/approve', [OvertimeController::class, 'approve'])->name('approve');
        Route::post('/{overtimeRequest}/reject',  [OvertimeController::class, 'reject'])->name('reject');
    });

    // Subcontractors
    Route::prefix('subcontractors')->name('subcontractors.')->group(function () {
        Route::get('/',                                              [SubcontractorController::class, 'index'])->name('index');
        Route::post('/',                                             [SubcontractorController::class, 'store'])->name('store');
        Route::put('/{subcontractor}',                               [SubcontractorController::class, 'update'])->name('update');
        Route::delete('/{subcontractor}',                            [SubcontractorController::class, 'destroy'])->name('destroy');
        Route::post('/{subcontractor}/photos',                       [SubcontractorController::class, 'uploadPhoto'])->name('photos.upload');
        Route::delete('/{subcontractor}/photos/{photo}',             [SubcontractorController::class, 'deletePhoto'])->name('photos.delete');
    });

    // Workspaces (Trello-style, contain boards)
    Route::prefix('workspaces')->name('workspaces.')->group(function () {
        Route::post('/',                          [WorkspaceController::class, 'store'])->name('store');
        Route::get('/{workspace}',                [WorkspaceController::class, 'show'])->name('show');
        Route::get('/{workspace}/members',        [WorkspaceController::class, 'members'])->name('members');
        Route::get('/{workspace}/settings',       [WorkspaceController::class, 'settings'])->name('settings');
        Route::patch('/{workspace}',              [WorkspaceController::class, 'update'])->name('update');
        Route::delete('/{workspace}',             [WorkspaceController::class, 'destroy'])->name('destroy');
        Route::post('/{workspace}/members',       [WorkspaceController::class, 'storeMember'])->name('members.store');
        Route::patch('/{workspace}/members/{user}', [WorkspaceController::class, 'updateMember'])->name('members.update');
        Route::delete('/{workspace}/members/{user}', [WorkspaceController::class, 'destroyMember'])->name('members.destroy');
        // Board creation is workspace-scoped
        Route::post('/{workspace}/boards',        [BoardController::class, 'store'])->name('boards.store');
    });

    // Boards (Trello-style)
    Route::prefix('boards')->name('boards.')->group(function () {
        Route::get('/',                          [BoardController::class, 'index'])->name('index');
        Route::get('/{board}',                   [BoardController::class, 'show'])->name('show');
        Route::patch('/{board}',                 [BoardController::class, 'update'])->name('update');
        Route::delete('/{board}',                [BoardController::class, 'destroy'])->name('destroy');
        // Lists
        Route::post('/{board}/lists',            [BoardController::class, 'storeList'])->name('lists.store');
        Route::post('/lists/reorder',            [BoardController::class, 'reorderLists'])->name('lists.reorder');
        Route::patch('/lists/{list}',            [BoardController::class, 'updateList'])->name('lists.update');
        Route::delete('/lists/{list}',           [BoardController::class, 'destroyList'])->name('lists.destroy');
        // Cards
        Route::post('/lists/{list}/cards',       [CardController::class, 'store'])->name('cards.store');
        Route::post('/cards/{card}/move',        [CardController::class, 'move'])->name('cards.move');
        Route::patch('/cards/{card}',            [CardController::class, 'update'])->name('cards.update');
        Route::delete('/cards/{card}',           [CardController::class, 'destroy'])->name('cards.destroy');
        // Checklist items
        Route::post('/cards/{card}/checklist',   [CardController::class, 'storeChecklistItem'])->name('checklist.store');
        Route::patch('/checklist/{item}',        [CardController::class, 'updateChecklistItem'])->name('checklist.update');
        Route::delete('/checklist/{item}',       [CardController::class, 'destroyChecklistItem'])->name('checklist.destroy');
        // Labels
        Route::patch('/labels/{label}',          [BoardController::class, 'updateLabel'])->name('labels.update');
        Route::post('/cards/{card}/labels/{label}/toggle', [CardController::class, 'toggleLabel'])->name('cards.labels.toggle');
        // Attachments
        Route::post('/cards/{card}/attachments', [CardController::class, 'storeAttachment'])->name('cards.attachments.store');
        Route::patch('/attachments/{attachment}', [CardController::class, 'updateAttachment'])->name('attachments.update');
        Route::delete('/attachments/{attachment}', [CardController::class, 'destroyAttachment'])->name('attachments.destroy');
        // Comments
        Route::post('/cards/{card}/comments',    [CardController::class, 'storeComment'])->name('cards.comments.store');
        Route::delete('/comments/{comment}',     [CardController::class, 'destroyComment'])->name('comments.destroy');
    });

    // Newsfeed (intranet)
    Route::prefix('feed')->name('feed.')->group(function () {
        Route::get('/',                                  [FeedController::class, 'index'])->name('index');
        Route::post('/',                                 [FeedController::class, 'store'])->name('store');
        Route::post('/upload',                           [FeedController::class, 'upload'])->name('upload');
        Route::patch('/{post}',                          [FeedController::class, 'update'])->name('update');
        Route::delete('/{post}',                         [FeedController::class, 'destroy'])->name('destroy');
        Route::post('/{post}/pin',                       [FeedController::class, 'pin'])->name('pin');
        Route::post('/{post}/react',                     [FeedController::class, 'react'])->name('react');
        Route::post('/{post}/comments',                  [FeedController::class, 'comment'])->name('comments.store');
        Route::patch('/{post}/comments/{comment}',       [FeedController::class, 'updateComment'])->name('comments.update');
        Route::delete('/{post}/comments/{comment}',      [FeedController::class, 'destroyComment'])->name('comments.destroy');
    });

    // Knowledge Base
    Route::prefix('knowledge-base')->name('kb.')->group(function () {
        Route::get('/',                                          [KnowledgeBaseController::class, 'index'])->name('index');
        Route::get('/{categorySlug}/{articleSlug}',             [KnowledgeBaseController::class, 'show'])->name('show');
        Route::post('/upload',                                  [KnowledgeBaseController::class, 'upload'])->name('upload');
        Route::get('/media/{path}',                             [KnowledgeBaseController::class, 'serveMedia'])->name('media')->where('path', '.*');
        // Admin / Manager
        Route::post('/categories',                              [KnowledgeBaseController::class, 'storeCategory'])->name('categories.store');
        Route::patch('/categories/{category}',                  [KnowledgeBaseController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}',                 [KnowledgeBaseController::class, 'destroyCategory'])->name('categories.destroy');
        Route::post('/categories/{category}/articles',          [KnowledgeBaseController::class, 'storeArticle'])->name('articles.store');
        Route::patch('/categories/{category}/articles/{article}', [KnowledgeBaseController::class, 'updateArticle'])->name('articles.update');
        Route::post('/categories/{category}/articles/{article}/toggle', [KnowledgeBaseController::class, 'toggleArticle'])->name('articles.toggle');
        Route::delete('/categories/{category}/articles/{article}', [KnowledgeBaseController::class, 'destroyArticle'])->name('articles.destroy');
    });

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
