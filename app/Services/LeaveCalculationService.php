<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveCalculationService
{
    /**
     * Calculate leave balance for user and leave type.
     */
    public static function calculateLeaveBalance(int $userId, int $leaveTypeId, int $year): array
    {
        $leaveBalance = LeaveBalance::firstOrCreate(
            [
                'user_id' => $userId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
            ],
            [
                'allocated_days' => 0,
                'used_days' => 0,
                'carried_over_days' => 0,
            ]
        );

        $leaveType = LeaveType::findOrFail($leaveTypeId);
        
        // Get previous year balance for carry-over
        $previousYearBalance = LeaveBalance::where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year - 1)
            ->first();

        $carriedOver = 0;
        if ($previousYearBalance && $leaveType->is_paid) {
            $remainingDays = $previousYearBalance->getRemainingDaysAttribute();
            $carriedOver = min($remainingDays, $leaveType->max_days_per_year * 0.2); // Max 20% carry-over
        }

        // Calculate used days from approved leave requests
        $usedDays = LeaveRequest::where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days_count');

        // Update balance
        $leaveBalance->update([
            'allocated_days' => $leaveType->max_days_per_year,
            'carried_over_days' => $carriedOver,
            'used_days' => $usedDays,
        ]);

        return [
            'allocated_days' => $leaveType->max_days_per_year,
            'used_days' => $usedDays,
            'carried_over_days' => $carriedOver,
            'remaining_days' => $leaveBalance->getRemainingDaysAttribute(),
            'total_available' => $leaveBalance->getTotalAvailableDaysAttribute(),
            'usage_percentage' => $leaveBalance->getUsagePercentageAttribute(),
        ];
    }

    /**
     * Validate leave request.
     */
    public static function validateLeaveRequest(int $userId, int $leaveTypeId, Carbon $startDate, Carbon $endDate): array
    {
        $leaveType = LeaveType::findOrFail($leaveTypeId);
        $daysCount = self::calculateBusinessDays($startDate, $endDate);

        // Check if user has sufficient balance
        $balance = self::calculateLeaveBalance($userId, $leaveTypeId, $startDate->year);
        if (!$leaveType->is_paid && $daysCount > $leaveType->max_days_per_year) {
            return [
                'valid' => false,
                'message' => "Unpaid leave cannot exceed {$leaveType->max_days_per_year} days",
                'error_code' => 'EXCEEDS_MAX_UNPAID_DAYS'
            ];
        }

        if ($leaveType->is_paid && $balance['remaining_days'] < $daysCount) {
            return [
                'valid' => false,
                'message' => "Insufficient leave balance. Available: {$balance['remaining_days']} days, Requested: {$daysCount} days",
                'error_code' => 'INSUFFICIENT_BALANCE'
            ];
        }

        // Check for overlapping leave requests
        $overlappingRequests = LeaveRequest::where('user_id', $userId)
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                          $subQuery->where('start_date', '<=', $startDate)
                                 ->where('end_date', '>=', $endDate);
                      });
            })
            ->exists();

        if ($overlappingRequests) {
            return [
                'valid' => false,
                'message' => 'Leave request overlaps with existing leave period',
                'error_code' => 'OVERLAPPING_LEAVE'
            ];
        }

        // Check advance notice requirement (minimum 3 days for regular leave)
        if (!$leaveType->emergency_leave && $startDate->diffInDays(now()) < 3) {
            return [
                'valid' => false,
                'message' => 'Leave request must be submitted at least 3 days in advance',
                'error_code' => 'INSUFFICIENT_NOTICE'
            ];
        }

        // Check maximum consecutive days
        if ($daysCount > 14) {
            return [
                'valid' => false,
                'message' => 'Leave cannot exceed 14 consecutive days',
                'error_code' => 'EXCEEDS_MAX_CONSECUTIVE_DAYS'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Leave request is valid',
            'days_count' => $daysCount,
            'balance_after' => $leaveType->is_paid ? $balance['remaining_days'] - $daysCount : null,
        ];
    }

    /**
     * Calculate business days between two dates.
     */
    public static function calculateBusinessDays(Carbon $startDate, Carbon $endDate): int
    {
        $days = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            // Exclude weekends (Saturday = 6, Sunday = 7)
            if ($current->dayOfWeekIso < 6) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    /**
     * Get all leave balances for user.
     */
    public static function getAllLeaveBalances(int $userId, int $year = null): array
    {
        $year = $year ?? now()->year;
        $balances = [];

        $leaveTypes = LeaveType::active()->get();
        
        foreach ($leaveTypes as $leaveType) {
            $balance = self::calculateLeaveBalance($userId, $leaveType->id, $year);
            $balances[] = [
                'leave_type' => $leaveType,
                'balance' => $balance,
            ];
        }

        return $balances;
    }

    /**
     * Process leave request approval.
     */
    public static function processLeaveApproval(int $leaveRequestId, int $approverId, string $status, string $comments = null): array
    {
        DB::beginTransaction();
        
        try {
            $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);
            
            if ($leaveRequest->status !== 'pending') {
                throw new \Exception('Leave request is not pending');
            }

            $leaveRequest->update([
                'status' => $status,
                'approved_at' => $status === 'approved' ? now() : null,
                'approved_by' => $status === 'approved' ? $approverId : null,
                'rejected_at' => $status === 'rejected' ? now() : null,
                'rejected_by' => $status === 'rejected' ? $approverId : null,
                'rejection_reason' => $status === 'rejected' ? $comments : null,
                'approver_notes' => $status === 'approved' ? $comments : null,
            ]);

            // Create approval record
            $leaveRequest->approvals()->create([
                'approver_id' => $approverId,
                'status' => $status,
                'comments' => $comments,
                'approval_level' => 1,
                'approved_at' => now(),
            ]);

            // If approved, update leave balance
            if ($status === 'approved') {
                $leaveBalance = LeaveBalance::where('user_id', $leaveRequest->user_id)
                    ->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->where('year', $leaveRequest->start_date->year)
                    ->first();

                if ($leaveBalance) {
                    $leaveBalance->deductDays($leaveRequest->days_count);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Leave request {$status} successfully",
                'leave_request' => $leaveRequest->load(['user', 'leaveType', 'approver']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to process leave approval: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel leave request and restore balance.
     */
    public static function cancelLeaveRequest(int $leaveRequestId, string $reason = null): array
    {
        DB::beginTransaction();
        
        try {
            $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);
            
            if ($leaveRequest->status !== 'pending') {
                throw new \Exception('Only pending leave requests can be cancelled');
            }

            $leaveRequest->update([
                'status' => 'cancelled',
                'rejection_reason' => $reason,
            ]);

            // Restore leave balance if it was deducted
            $leaveBalance = LeaveBalance::where('user_id', $leaveRequest->user_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', $leaveRequest->start_date->year)
                ->first();

            if ($leaveBalance) {
                $leaveBalance->addBackDays($leaveRequest->days_count);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Leave request cancelled successfully',
                'leave_request' => $leaveRequest,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to cancel leave request: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get leave statistics for user.
     */
    public static function getLeaveStatistics(int $userId, int $year = null): array
    {
        $year = $year ?? now()->year;
        
        $stats = [
            'total_requests' => 0,
            'approved_requests' => 0,
            'rejected_requests' => 0,
            'pending_requests' => 0,
            'total_days_taken' => 0,
            'by_type' => [],
        ];

        $requests = LeaveRequest::where('user_id', $userId)
            ->whereYear('start_date', $year)
            ->with('leaveType')
            ->get();

        $stats['total_requests'] = $requests->count();
        $stats['approved_requests'] = $requests->where('status', 'approved')->count();
        $stats['rejected_requests'] = $requests->where('status', 'rejected')->count();
        $stats['pending_requests'] = $requests->where('status', 'pending')->count();
        $stats['total_days_taken'] = $requests->where('status', 'approved')->sum('days_count');

        // Group by leave type
        $byType = $requests->where('status', 'approved')->groupBy('leave_type_id');
        foreach ($byType as $typeId => $typeRequests) {
            $leaveType = $typeRequests->first()->leaveType;
            $stats['by_type'][] = [
                'leave_type' => $leaveType->name,
                'days_taken' => $typeRequests->sum('days_count'),
                'requests_count' => $typeRequests->count(),
            ];
        }

        return $stats;
    }
}
