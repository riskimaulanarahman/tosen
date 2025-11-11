<?php

use App\Http\Controllers\PayrollController;
use Illuminate\Support\Facades\Route;

// Payroll management routes
Route::middleware(['auth', 'owner'])->group(function () {
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/create-period', [PayrollController::class, 'createPeriodPage'])->name('payroll.create.page');
    Route::post('/payroll/create-period', [PayrollController::class, 'create'])->name('payroll.create.period');
    Route::get('/payroll/{payrollPeriod}/edit', [PayrollController::class, 'edit'])->name('payroll.edit');
    Route::put('/payroll/{payrollPeriod}', [PayrollController::class, 'update'])->name('payroll.update');
    Route::delete('/payroll/{payrollPeriod}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
    
    // Payroll generation routes
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
    Route::get('/payroll/{payrollPeriod}/show', [PayrollController::class, 'show'])->name('payroll.show');
    
    // Payroll approval routes
    Route::post('/payroll/{payrollPeriod}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
    Route::post('/payroll/{payrollPeriod}/process-payments', [PayrollController::class, 'processPayments'])->name('payroll.process.payments');
    
    // Payroll statistics routes
    Route::get('/payroll/statistics', [PayrollController::class, 'statistics'])->name('payroll.statistics');
    Route::get('/payroll/{payrollPeriod}/export', [PayrollController::class, 'export'])->name('payroll.export');
});
