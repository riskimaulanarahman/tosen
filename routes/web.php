<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OtpVerificationController;
use App\Http\Controllers\OutletController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\SyncFlowController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// TOSEN-TOGA Presence Landing Page
Route::get('/', [LandingPageController::class, 'index'])->name('landing.page');
Route::post('/trial-request', [LandingPageController::class, 'requestTrial'])->name('landing.trial');
Route::post('/demo-request', [LandingPageController::class, 'requestDemo'])->name('landing.demo');

// SyncFlow Landing Page Routes
Route::get('/syncflow', function () {
    return inertia('SyncFlowLanding');
})->name('syncflow.landing');

// SyncFlow API Routes
Route::prefix('api/syncflow')->group(function () {
    Route::post('/trial', [SyncFlowController::class, 'submitTrial'])->name('syncflow.trial');
    Route::post('/demo', [SyncFlowController::class, 'submitDemo'])->name('syncflow.demo');
});

// SyncFlow Admin Routes (protected)
Route::prefix('admin/syncflow')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/trials', [SyncFlowController::class, 'getTrialRequests'])->name('admin.syncflow.trials');
    Route::get('/demos', [SyncFlowController::class, 'getDemoRequests'])->name('admin.syncflow.demos');
    Route::put('/trials/{id}/status', [SyncFlowController::class, 'updateTrialStatus'])->name('admin.syncflow.trials.status');
    Route::put('/demos/{id}/status', [SyncFlowController::class, 'updateDemoStatus'])->name('admin.syncflow.demos.status');
});

// Legacy welcome route (can be removed later)
Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Attendance routes
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/checkin', [AttendanceController::class, 'checkin'])->middleware('throttle:5,1')->name('attendance.checkin');
    Route::post('/attendance/checkout', [AttendanceController::class, 'checkout'])->middleware('throttle:10,1')->name('attendance.checkout');
    Route::get('/attendance/status', [AttendanceController::class, 'status'])->name('attendance.status');
    
    // Offline attendance routes
    Route::post('/attendance/offline/store', [AttendanceController::class, 'storeOffline'])->name('attendance.offline.store');
    Route::post('/attendance/offline/sync', [AttendanceController::class, 'syncOffline'])->name('attendance.offline.sync');
    Route::get('/attendance/offline/stats', [AttendanceController::class, 'offlineStats'])->name('attendance.offline.stats');
    Route::delete('/attendance/offline/clear', [AttendanceController::class, 'clearOffline'])->name('attendance.offline.clear');
});

// Owner routes
Route::middleware(['auth', 'owner'])->group(function () {
    Route::post('/outlets/{outlet}/employees', [EmployeeController::class, 'store'])->name('outlets.employees.store');
    Route::resource('outlets', OutletController::class);
    
    // Employee Management routes
    Route::get('/employees', [App\Http\Controllers\EmployeeManagementController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [App\Http\Controllers\EmployeeManagementController::class, 'create'])->name('employees.create');
    Route::post('/employees', [App\Http\Controllers\EmployeeManagementController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}', [App\Http\Controllers\EmployeeManagementController::class, 'show'])->name('employees.show');
    Route::get('/employees/{employee}/edit', [App\Http\Controllers\EmployeeManagementController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}', [App\Http\Controllers\EmployeeManagementController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [App\Http\Controllers\EmployeeManagementController::class, 'destroy'])->name('employees.destroy');
    Route::post('/employees/{employee}/transfer', [App\Http\Controllers\EmployeeManagementController::class, 'transfer'])->name('employees.transfer');
    Route::post('/employees/{employee}/resend-email', [App\Http\Controllers\EmployeeManagementController::class, 'resendEmail'])->name('employees.resend-email');
    Route::post('/employees/bulk-assign', [App\Http\Controllers\EmployeeManagementController::class, 'bulkAssign'])->name('employees.bulk-assign');
    
    // Reports routes
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pivot', [App\Http\Controllers\ReportController::class, 'pivot'])->name('reports.pivot');
    Route::get('/reports/export', [App\Http\Controllers\ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/export-pivot', [App\Http\Controllers\ReportController::class, 'exportPivot'])->name('reports.export.pivot');
    Route::get('/reports/summary', [App\Http\Controllers\ReportController::class, 'summary'])->name('reports.summary');
});

// OTP Verification routes
Route::middleware('guest')->prefix('verification')->group(function () {
    Route::get('/notice', [OtpVerificationController::class, 'show'])->name('verification.notice');
    Route::post('/send', [OtpVerificationController::class, 'send'])->middleware('throttle:5,1')->name('verification.send');
    Route::post('/verify', [OtpVerificationController::class, 'verify'])->name('verification.verify');
});


require __DIR__.'/leave.php';
require __DIR__.'/shift.php';
require __DIR__.'/payroll.php';
require __DIR__.'/auth.php';
