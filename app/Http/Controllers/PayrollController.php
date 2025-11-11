<?php

namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\User;
use App\Models\Outlet;
use App\Services\PayrollCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PayrollController extends Controller
{
    /**
     * Display payroll management dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $outletId = $request->get('outlet_id');
        $status = $request->get('status', 'active');
        $dateFrom = $request->get('date_from') ? \Carbon\Carbon::parse($request->get('date_from')) : now()->startOfMonth();
        $dateTo = $request->get('date_to') ? \Carbon\Carbon::parse($request->get('date_to')) : now()->endOfMonth();

        // Get payroll periods
        $payrollPeriods = PayrollPeriod::with(['payrollRecords'])
            ->when($outletId, function ($query) use ($outletId) {
                return $query->whereHas('payrollRecords', function ($subQuery) use ($outletId) {
                    $subQuery->whereHas('user', function ($innerQuery) use ($outletId) {
                        $innerQuery->where('outlet_id', $outletId);
                    });
                });
            })
            ->where('status', $status)
            ->whereBetween('start_date', [$dateFrom, $dateTo])
            ->orderBy('start_date', 'desc')
            ->paginate(20);

        return inertia('Payroll/Index', [
            'payrollPeriods' => $payrollPeriods,
            'filters' => [
                'outlet_id' => $outletId,
                'status' => $status,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Display create/generate payroll page.
     */
    public function createPeriodPage(Request $request)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $employees = User::where('role', 'employee')
            ->with('outlet:id,name')
            ->select('id', 'name', 'email', 'outlet_id')
            ->orderBy('name')
            ->get();

        $outlets = Outlet::select('id', 'name')->orderBy('name')->get();

        $payrollPeriods = PayrollPeriod::orderBy('start_date', 'desc')
            ->limit(12)
            ->get([
                'id',
                'name',
                'start_date',
                'end_date',
                'status',
            ]);

        return inertia('Payroll/CreatePeriod', [
            'employees' => $employees,
            'outlets' => $outlets,
            'payrollPeriods' => $payrollPeriods,
        ]);
    }

    /**
     * Create new payroll period.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'basic_rate' => 'required|numeric|min:0',
            'overtime_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $result = PayrollCalculationService::createPayrollPeriod(
            $request->name,
            \Carbon\Carbon::parse($request->start_date),
            \Carbon\Carbon::parse($request->end_date),
            $request->user_ids ?? [],
            $request->basic_rate,
            $request->overtime_rate
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors([
            'payroll_period' => $result['message']
        ]);
    }

    /**
     * Generate payroll for period.
     */
    public function generate(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'payroll_period_id' => 'required|exists:payroll_periods,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $result = PayrollCalculationService::generateBulkPayroll(
            $request->user_ids,
            \Carbon\Carbon::parse($request->start_date),
            \Carbon\Carbon::parse($request->end_date)
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors([
            'payroll' => $result['message']
        ]);
    }

    /**
     * Show payroll period details.
     */
    public function show(PayrollPeriod $payrollPeriod)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $payrollPeriod->load(['payrollRecords.user', 'payrollRecords.overtimeRecords']);

        return inertia('Payroll/Show', [
            'payrollPeriod' => $payrollPeriod,
        ]);
    }

    /**
     * Edit payroll period.
     */
    public function edit(Request $request, PayrollPeriod $payrollPeriod)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'basic_rate' => 'required|numeric|min:0',
            'overtime_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $payrollPeriod->update([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'basic_rate' => $request->basic_rate,
            'overtime_rate' => $request->overtime_rate,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Payroll period updated successfully');
    }

    /**
     * Approve payroll period.
     */
    public function approve(PayrollPeriod $payrollPeriod, Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $result = PayrollCalculationService::approvePayrollPeriod(
            $payrollPeriod->id,
            $user->id
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors([
            'approval' => $result['message']
        ]);
    }

    /**
     * Process payroll payments.
     */
    public function processPayments(PayrollPeriod $payrollPeriod, Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $result = PayrollCalculationService::processPayrollPayments($payrollPeriod->id);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors([
            'payments' => $result['message']
        ]);
    }

    /**
     * Export payroll data.
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $payrollPeriodId = $request->get('payroll_period_id');
        $format = $request->get('format', 'csv');

        $result = PayrollCalculationService::exportPayrollData($payrollPeriodId, $format);

        if (!$result['success']) {
            return back()->withErrors([
                'export' => $result['message']
            ]);
        }

        $filename = $result['filename'];
        $fileContent = $result['content'];

        return response($fileContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Get payroll statistics.
     */
    public function statistics(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $outletId = $request->get('outlet_id');
        $startDate = $request->get('start_date') ? \Carbon\Carbon::parse($request->get('start_date')) : now()->startOfYear();
        $endDate = $request->get('end_date') ? \Carbon\Carbon::parse($request->get('end_date')) : now()->endOfYear();

        $result = PayrollCalculationService::getPayrollStatistics($outletId, $startDate, $endDate);

        return inertia('Payroll/Statistics', [
            'statistics' => $result['statistics'],
            'filters' => [
                'outlet_id' => $outletId,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Delete payroll period.
     */
    public function destroy(PayrollPeriod $payrollPeriod)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        // Check if period can be deleted
        if ($payrollPeriod->status === 'paid') {
            return back()->withErrors([
                'payroll_period' => 'Cannot delete payroll period that has been paid'
            ]);
        }

        $payrollPeriod->delete();

        return back()->with('success', 'Payroll period deleted successfully');
    }
}
