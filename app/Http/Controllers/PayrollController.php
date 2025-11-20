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

        $ownedOutletIds = Outlet::where('owner_id', $user->id)->pluck('id');
        $outletId = $request->get('outlet_id');
        if ($outletId && !$ownedOutletIds->contains($outletId)) {
            abort(403, 'Unauthorized');
        }
        $status = $request->get('status');
        $dateFrom = $request->get('date_from') ? \Carbon\Carbon::parse($request->get('date_from')) : now()->startOfMonth();
        $dateTo = $request->get('date_to') ? \Carbon\Carbon::parse($request->get('date_to')) : now()->endOfMonth();

        // Get payroll periods
        $payrollPeriods = PayrollPeriod::with(['payrollRecords' => function ($query) use ($user) {
                $query->whereHas('user', function ($subQuery) use ($user) {
                    $subQuery->whereHas('outlet', function ($innerQuery) use ($user) {
                        $innerQuery->where('owner_id', $user->id);
                    });
                });
            }])
            ->whereHas('payrollRecords', function ($query) use ($user) {
                $query->whereHas('user', function ($subQuery) use ($user) {
                    $subQuery->whereHas('outlet', function ($innerQuery) use ($user) {
                        $innerQuery->where('owner_id', $user->id);
                    });
                });
            })
            ->when($outletId, function ($query) use ($outletId) {
                return $query->whereHas('payrollRecords', function ($subQuery) use ($outletId) {
                    $subQuery->whereHas('user', function ($innerQuery) use ($outletId) {
                        $innerQuery->where('outlet_id', $outletId);
                    });
                });
            })
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->whereBetween('start_date', [$dateFrom, $dateTo])
            ->orderBy('start_date', 'desc')
            ->paginate(20);

        return inertia('Payroll/Index', [
            'payrollPeriods' => $payrollPeriods,
            'filters' => [
                'outlet_id' => $outletId,
                'status' => $status ?: '',
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
            ->whereHas('outlet', function ($query) use ($user) {
                $query->where('owner_id', $user->id);
            })
            ->with('outlet:id,name')
            ->select('id', 'name', 'email', 'outlet_id')
            ->orderBy('name')
            ->get();

        $outlets = Outlet::select('id', 'name')
            ->where('owner_id', $user->id)
            ->orderBy('name')
            ->get();

        $payrollPeriods = PayrollPeriod::whereHas('payrollRecords', function ($query) use ($user) {
                $query->whereHas('user', function ($subQuery) use ($user) {
                    $subQuery->whereHas('outlet', function ($innerQuery) use ($user) {
                        $innerQuery->where('owner_id', $user->id);
                    });
                });
            })
            ->orderBy('start_date', 'desc')
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
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after:start_date',
            'basic_rate' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $ownedEmployeeIds = User::where('role', 'employee')
            ->whereHas('outlet', function ($query) use ($user) {
                $query->where('owner_id', $user->id);
            })
            ->pluck('id');

        $requestedUserIds = collect($request->user_ids ?? [])->map(fn ($id) => (int) $id);

        // If no selection provided, include all owned employees to simplify UMKM flow
        if ($requestedUserIds->isEmpty()) {
            $requestedUserIds = $ownedEmployeeIds->values();
        }

        $unauthorizedIds = $requestedUserIds->diff($ownedEmployeeIds);

        if ($unauthorizedIds->isNotEmpty()) {
            abort(403, 'Unauthorized');
        }

        $result = PayrollCalculationService::createPayrollPeriod(
            $request->name,
            \Carbon\Carbon::parse($request->start_date),
            \Carbon\Carbon::parse($request->end_date),
            $requestedUserIds->intersect($ownedEmployeeIds)->values()->toArray(),
            $request->basic_rate,
            $request->overtime_rate
        );

        if ($result['success']) {
            $redirect = back()->with('success', $result['message']);

            if (!empty($result['errors'])) {
                $redirect->with('payroll_partial_errors', $result['errors']);
            }

            return $redirect;
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
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after:start_date',
        ]);

        $payrollPeriod = PayrollPeriod::findOrFail($request->payroll_period_id);
        $this->ensurePayrollPeriodOwned($payrollPeriod, $user->id);

        $ownedEmployeeIds = User::where('role', 'employee')
            ->whereHas('outlet', function ($query) use ($user) {
                $query->where('owner_id', $user->id);
            })
            ->pluck('id');

        $requestedUserIds = collect($request->user_ids ?? [])->map(fn ($id) => (int) $id);

        // If no selection provided, auto-target all owned employees in the period
        if ($requestedUserIds->isEmpty()) {
            $requestedUserIds = $ownedEmployeeIds->values();
        }
        $unauthorizedIds = $requestedUserIds->diff($ownedEmployeeIds);

        if ($unauthorizedIds->isNotEmpty()) {
            abort(403, 'Unauthorized');
        }

        $startDate = $request->start_date
            ? \Carbon\Carbon::parse($request->start_date)
            : $payrollPeriod->start_date;

        $endDate = $request->end_date
            ? \Carbon\Carbon::parse($request->end_date)
            : $payrollPeriod->end_date;

        $result = PayrollCalculationService::generatePayrollForExistingPeriod(
            $payrollPeriod->id,
            $requestedUserIds->intersect($ownedEmployeeIds)->values()->toArray(),
            $startDate,
            $endDate
        );

        if ($result['success']) {
            $redirect = back()->with('success', $result['message']);

            if (!empty($result['errors'])) {
                $redirect->with('payroll_partial_errors', $result['errors']);
            }

            return $redirect;
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

        $this->ensurePayrollPeriodOwned($payrollPeriod, $user->id);

        $payrollPeriod->load([
            'payrollRecords' => function ($query) use ($user) {
                $query->whereHas('user', function ($subQuery) use ($user) {
                    $subQuery->whereHas('outlet', function ($innerQuery) use ($user) {
                        $innerQuery->where('owner_id', $user->id);
                    });
                })->with(['user', 'overtimeRecords']);
            },
        ]);

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

        $this->ensurePayrollPeriodOwned($payrollPeriod, $user->id);

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

        $this->ensurePayrollPeriodOwned($payrollPeriod, $user->id);

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        if ($request->filled('notes')) {
            $payrollPeriod->update(['notes' => $request->notes]);
        }

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

        $this->ensurePayrollPeriodOwned($payrollPeriod, $user->id);

        $request->validate([
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:100',
            'paid_at' => 'nullable|date',
        ]);

        $result = PayrollCalculationService::processPayrollPayments(
            $payrollPeriod->id,
            $request->payment_method,
            $request->payment_reference,
            $request->paid_at ? \Carbon\Carbon::parse($request->paid_at) : null
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors([
            'payments' => $result['message']
        ]);
    }

    /**
     * Adjust payroll record (bonus/potongan/notes) before payment.
     */
    public function adjustRecord(PayrollRecord $payrollRecord, Request $request)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $payrollPeriod = $payrollRecord->payrollPeriod;
        $this->ensurePayrollPeriodOwned($payrollPeriod, $user->id);

        if ($payrollPeriod->status === 'paid') {
            return back()->withErrors([
                'adjustment' => 'Cannot adjust payroll that has been paid',
            ]);
        }

        $request->validate([
            'bonus' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $bonus = $request->bonus ?? 0;
        $otherDeductions = $request->other_deductions ?? 0;

        $gross = $payrollRecord->base_salary + $payrollRecord->overtime_pay + $bonus - $payrollRecord->leave_deduction;
        $existingTaxRate = $payrollRecord->total_pay > 0
            ? ($payrollRecord->tax_deduction / $payrollRecord->total_pay)
            : 0.05;
        $taxDeduction = $gross * $existingTaxRate;

        $payrollRecord->update([
            'bonus' => $bonus,
            'other_deductions' => $otherDeductions,
            'tax_deduction' => $taxDeduction,
            'total_pay' => $gross,
            'notes' => $request->notes,
            'status' => $payrollRecord->status === 'calculated' ? 'calculated' : $payrollRecord->status,
        ]);

        return back()->with('success', 'Payroll record updated');
    }

    /**
     * Export payroll data.
     */
    public function export(PayrollPeriod $payrollPeriod, Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $format = $request->get('format', 'csv');

        $this->ensurePayrollPeriodOwned($payrollPeriod, $user->id);

        $result = PayrollCalculationService::exportPayrollData($payrollPeriod->id, $format);

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
     * Export payroll summary (cash out recap) as CSV.
     */
    public function exportSummary(PayrollPeriod $payrollPeriod, Request $request)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $this->ensurePayrollPeriodOwned($payrollPeriod, $user->id);

        $result = PayrollCalculationService::exportPayrollSummary($payrollPeriod->id);

        if (!$result['success']) {
            return back()->withErrors([
                'export_summary' => $result['message']
            ]);
        }

        return response($result['content'])
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Export single payroll slip (CSV) for a record.
     */
    public function exportSlip(PayrollRecord $payrollRecord, Request $request)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $payrollPeriod = $payrollRecord->payrollPeriod;
        $this->ensurePayrollPeriodOwned($payrollPeriod, $user->id);

        // Optional HTML preview when `?view=html`
        if ($request->query('view') === 'html') {
            $payrollRecord->load(['user', 'payrollPeriod', 'overtimeRecords']);
            return view('payroll.slip', ['record' => $payrollRecord]);
        }

        $result = PayrollCalculationService::exportPayrollSlip($payrollRecord->id);

        if (!$result['success']) {
            return back()->withErrors([
                'export_slip' => $result['message']
            ]);
        }

        return response($result['content'])
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Printable slip view.
     */
    public function printSlip(PayrollRecord $payrollRecord)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $payrollPeriod = $payrollRecord->payrollPeriod;
        $this->ensurePayrollPeriodOwned($payrollPeriod, $user->id);

        $payrollRecord->load(['user', 'payrollPeriod', 'overtimeRecords']);

        return view('payroll.slip', [
            'record' => $payrollRecord,
        ]);
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
        $ownedOutletIds = Outlet::where('owner_id', $user->id)->pluck('id');
        if ($outletId && !$ownedOutletIds->contains($outletId)) {
            abort(403, 'Unauthorized');
        }
        $startDate = $request->get('start_date') ? \Carbon\Carbon::parse($request->get('start_date')) : now()->startOfYear();
        $endDate = $request->get('end_date') ? \Carbon\Carbon::parse($request->get('end_date')) : now()->endOfYear();

        $result = PayrollCalculationService::getPayrollStatistics($outletId, $startDate, $endDate, $user->id);

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

        $this->ensurePayrollPeriodOwned($payrollPeriod, $user->id);

        // Check if period can be deleted
        if ($payrollPeriod->status === 'paid') {
            return back()->withErrors([
                'payroll_period' => 'Cannot delete payroll period that has been paid'
            ]);
        }

        $payrollPeriod->delete();

        return back()->with('success', 'Payroll period deleted successfully');
    }

    /**
     * Ensure a payroll period is accessible to the authenticated owner.
     */
    private function ensurePayrollPeriodOwned(PayrollPeriod $payrollPeriod, int $ownerId): void
    {
        $hasOwnedRecords = $payrollPeriod->payrollRecords()
            ->whereHas('user', function ($query) use ($ownerId) {
                $query->whereHas('outlet', function ($outletQuery) use ($ownerId) {
                    $outletQuery->where('owner_id', $ownerId);
                });
            })
            ->exists();

        if (!$hasOwnedRecords) {
            abort(403, 'Unauthorized');
        }

        $hasForeignRecords = $payrollPeriod->payrollRecords()
            ->whereHas('user', function ($query) use ($ownerId) {
                $query->whereHas('outlet', function ($outletQuery) use ($ownerId) {
                    $outletQuery->where('owner_id', '!=', $ownerId);
                });
            })
            ->exists();

        if ($hasForeignRecords) {
            abort(403, 'Unauthorized');
        }
    }
}
