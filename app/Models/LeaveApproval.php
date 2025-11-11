<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'approver_id',
        'status',
        'comments',
        'approval_level',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Get the leave request for this approval.
     */
    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * Get the approver (user who approved/rejected).
     */
    public function approver()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if approval is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if approval is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if approval is forwarded.
     */
    public function isForwarded(): bool
    {
        return $this->status === 'forwarded';
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'approved' => 'bg-success-100 text-success-800',
            'rejected' => 'bg-danger-100 text-danger-800',
            'forwarded' => 'bg-info-100 text-info-800',
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
            'forwarded' => '➡️',
            default => '❓',
        };
    }

    /**
     * Get formatted approval date.
     */
    public function getFormattedApprovalDateAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('M d, Y H:i') : 'N/A';
    }

    /**
     * Scope to get approvals by level.
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('approval_level', $level);
    }

    /**
     * Scope to get approved approvals.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected approvals.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to get forwarded approvals.
     */
    public function scopeForwarded($query)
    {
        return $query->where('status', 'forwarded');
    }

    /**
     * Scope to get approvals by approver.
     */
    public function scopeByApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }
}
