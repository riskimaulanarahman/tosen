<?php

use App\Http\Controllers\LeaveController;
use Illuminate\Support\Facades\Route;

// Leave management routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Employee leave routes
    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
    Route::get('/leave/{leaveRequest}', [LeaveController::class, 'show'])->name('leave.show');
    Route::post('/leave/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');
    
    // Owner/Manager approval routes
    Route::middleware('owner')->group(function () {
        Route::get('/leave/approvals', [LeaveController::class, 'approvals'])->name('leave.approvals');
        Route::post('/leave/{leaveRequest}/approve', [LeaveController::class, 'approve'])->name('leave.approve');
    });
});
