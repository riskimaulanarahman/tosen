<?php

use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

// Shift management routes
Route::middleware(['auth', 'owner'])->group(function () {
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::get('/shifts/generate-schedule', [ShiftController::class, 'schedulePage'])->name('shifts.schedule');
    Route::get('/shifts/assign-employee', [ShiftController::class, 'assignmentPage'])->name('shifts.assign.index');
    Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::get('/shifts/create', [ShiftController::class, 'create'])->name('shifts.create');
    Route::get('/shifts/{shift}/edit', [ShiftController::class, 'edit'])->name('shifts.edit');
    Route::put('/shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
    Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');
    
    // Shift scheduling routes
    Route::post('/shifts/generate-schedule', [ShiftController::class, 'generateSchedule'])->name('shifts.generate.schedule');
    Route::get('/shifts/statistics', [ShiftController::class, 'statistics'])->name('shifts.statistics');
    Route::get('/shifts/{shift}', [ShiftController::class, 'show'])
        ->whereNumber('shift')
        ->name('shifts.show');
    
    // Employee shift assignment routes
    Route::post('/shifts/assign-employee', [ShiftController::class, 'assignEmployee'])->name('shifts.assign.employee');
    Route::post('/shifts/swap', [ShiftController::class, 'swapShift'])->name('shifts.swap');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/shifts/employee', [ShiftController::class, 'employeeSchedule'])->name('shifts.employee');
});
