<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'status',
        'approved_at',
        'approved_by',
        'approver_notes',
        'rejection_reason',
        'rejected_at',
        'rejected_by',
        'attachments',
        'is_half_day',
        'half_day_type',
        'emergency_leave',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'attachments' => 'array',
        'is_half_day' => 'boolean',
        'emergency_leave' => 'boolean',
    ];

    /**
     * Get the user that owns the leave request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the leave type for this request.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the approver (user who approved).
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the rejecter (user who rejected).
     */
    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the approvals for this leave request.
     */
    public function approvals()
    {
        return $this->hasMany(LeaveApproval::class);
    }

    /**
     * Check if leave request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if leave request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if leave request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if leave request is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get formatted date range.
     */
    public function getFormattedDateRangeAttribute()
    {
        if ($this->start_date->eq($this->end_date)) {
            return $this->start_date->format('M d, Y');
        }

        return $this->start_date->format('M d') . ' - ' . $this->end_date->format('M d, Y');
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'approved' => 'bg-success-100 text-success-800',
            'rejected' => 'bg-danger-100 text-danger-800',
            'pending' => 'bg-warning-100 text-warning-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status icon.
     */
    public function getStatusIconAttribute()
    {
        return match($this->status) {
            'approved' => '✅',
            'rejected' => '❌',
            'pending' => '⏳',
            'cancelled' => '🚫',
            default => '❓',
        };
    }

    /**
     * Get duration text.
     */
    public function getDurationTextAttribute()
    {
        if ($this->is_half_day) {
            $halfDayText = $this->half_day_type === 'first_half' ? 'First Half' : 'Second Half';
            return "Half Day ({$halfDayText})";
        }

        if ($this->days_count == 1) {
            return '1 Day';
        }

        return "{$this->days_count} Days";
    }

    /**
     * Check if leave request overlaps with given date range.
     */
    public function overlapsWith(Carbon $startDate, Carbon $endDate): bool
    {
        return $this->start_date <= $endDate && $this->end_date >= $startDate;
    }

    /**
     * Check if leave request includes today.
     */
    public function includesToday(): bool
    {
        $today = now()->startOfDay();
        return $this->start_date <= $today && $this->end_date >= $today;
    }

    /**
     * Check if leave request is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        $today = now()->startOfDay();
        return $this->isApproved() && $this->includesToday();
    }

    /**
     * Scope to get pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to get requests for date range.
     */
    public function scopeInDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                  $subQuery->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Scope to get current user's requests.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get emergency leave requests.
     */
    public function scopeEmergency($query)
    {
        return $query->where('emergency_leave', true);
    }
}
