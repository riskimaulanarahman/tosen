<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Services\LeaveCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LeaveController extends Controller
{
    /**
     * Display leave dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $year = $request->get('year', now()->year);
        
        // Get leave balances
        $leaveBalances = LeaveCalculationService::getAllLeaveBalances($user->id, $year);
        
        // Get recent leave requests
        $leaveRequests = LeaveRequest::where('user_id', $user->id)
            ->with(['leaveType', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Get leave statistics
        $statistics = LeaveCalculationService::getLeaveStatistics($user->id, $year);
        
        // Get leave types for new request
        $leaveTypes = LeaveType::active()->get();

        return inertia('Leave/Index', [
            'leaveBalances' => $leaveBalances,
            'leaveRequests' => $leaveRequests,
            'statistics' => $statistics,
            'leaveTypes' => $leaveTypes,
            'selectedYear' => $year,
            'availableYears' => $this->getAvailableYears($user->id),
        ]);
    }

    /**
     * Store new leave request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'is_half_day' => 'boolean',
            'half_day_type' => 'required_if:is_half_day,true|in:first_half,second_half',
            'emergency_leave' => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ]);

        $user = Auth::user();
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        
        // Handle half day logic
        if ($request->is_half_day) {
            $endDate = $startDate->copy();
        }

        // Validate leave request
        $validation = LeaveCalculationService::validateLeaveRequest(
            $user->id,
            $request->leave_type_id,
            $startDate,
            $endDate
        );

        if (!$validation['valid']) {
            return back()->withErrors([
                'leave_request' => $validation['message']
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            // Handle file attachments
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('leave_attachments', 'public');
                    $attachments[] = $path;
                }
            }

            // Create leave request
            $leaveRequest = LeaveRequest::create([
                'user_id' => $user->id,
                'leave_type_id' => $request->leave_type_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_count' => $validation['days_count'],
                'reason' => $request->reason,
                'is_half_day' => $request->is_half_day,
                'half_day_type' => $request->half_day_type,
                'emergency_leave' => $request->emergency_leave,
                'attachments' => $attachments,
            ]);

            // Send notification to approver (if required)
            $leaveType = LeaveType::find($request->leave_type_id);
            if ($leaveType->requires_approval) {
                $this->sendApprovalNotification($leaveRequest);
            } else {
                // Auto-approve if no approval required
                LeaveCalculationService::processLeaveApproval(
                    $leaveRequest->id,
                    $user->id, // Self-approval
                    'approved',
                    'Auto-approved - no approval required'
                );
            }

            DB::commit();

            return redirect()->route('leave.index')
                ->with('success', 'Leave request submitted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withErrors([
                'leave_request' => 'Failed to submit leave request: ' . $e->getMessage()
            ])->withInput();
        }
    }

    /**
     * Display leave request details.
     */
    public function show(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();
        
        // Check authorization
        if ($leaveRequest->user_id !== $user->id && !$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $leaveRequest->load(['user', 'leaveType', 'approver', 'approvals.approver']);

        return inertia('Leave/Show', [
            'leaveRequest' => $leaveRequest,
        ]);
    }

    /**
     * Cancel leave request.
     */
    public function cancel(LeaveRequest $leaveRequest, Request $request)
    {
        $user = Auth::user();
        
        // Check authorization
        if ($leaveRequest->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        if ($leaveRequest->status !== 'pending') {
            return back()->withErrors([
                'leave_request' => 'Only pending leave requests can be cancelled'
            ]);
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $result = LeaveCalculationService::cancelLeaveRequest(
            $leaveRequest->id,
            $request->cancellation_reason
        );

        if ($result['success']) {
            return redirect()->route('leave.index')
                ->with('success', 'Leave request cancelled successfully');
        }

        return back()->withErrors([
            'leave_request' => $result['message']
        ]);
    }

    /**
     * Display approval dashboard (for owners/managers).
     */
    public function approvals(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $status = $request->get('status', 'pending');
        $year = $request->get('year', now()->year);

        $leaveRequests = LeaveRequest::with(['user', 'user.outlet', 'leaveType'])
            ->where('status', $status)
            ->whereYear('start_date', $year)
            ->whereHas('user', function ($query) use ($user) {
                $query->whereHas('outlet', function ($subQuery) use ($user) {
                    $subQuery->where('owner_id', $user->id);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return inertia('Leave/Approvals', [
            'leaveRequests' => $leaveRequests,
            'selectedStatus' => $status,
            'selectedYear' => $year,
            'availableYears' => $this->getAvailableYearsForApprovals(),
        ]);
    }

    /**
     * Process leave approval.
     */
    public function approve(LeaveRequest $leaveRequest, Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:500',
        ]);

        $status = $request->action === 'approve' ? 'approved' : 'rejected';
        
        $result = LeaveCalculationService::processLeaveApproval(
            $leaveRequest->id,
            $user->id,
            $status,
            $request->comments
        );

        if ($result['success']) {
            $this->sendApprovalResultNotification($result['leave_request'], $status);
            
            return redirect()->route('leave.approvals')
                ->with('success', "Leave request {$status} successfully");
        }

        return back()->withErrors([
            'approval' => $result['message']
        ]);
    }

    /**
     * Get available years for leave requests.
     */
    private function getAvailableYears(int $userId): array
    {
        $years = LeaveRequest::where('user_id', $userId)
            ->selectRaw('YEAR(start_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Add current year if not present
        $currentYear = now()->year;
        if (!in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }

        return $years;
    }

    /**
     * Get available years for approvals.
     */
    private function getAvailableYearsForApprovals(): array
    {
        $years = LeaveRequest::selectRaw('YEAR(start_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Add current year if not present
        $currentYear = now()->year;
        if (!in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }

        return $years;
    }

    /**
     * Send approval notification.
     */
    private function sendApprovalNotification(LeaveRequest $leaveRequest): void
    {
        // TODO: Implement notification system
        // Could be email, push notification, or in-app notification
    }

    /**
     * Send approval result notification.
     */
    private function sendApprovalResultNotification(LeaveRequest $leaveRequest, string $status): void
    {
        // TODO: Implement notification system
        // Notify employee about approval/rejection
    }
}
