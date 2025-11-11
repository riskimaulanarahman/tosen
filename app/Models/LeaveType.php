<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'max_days_per_year',
        'requires_approval',
        'is_paid',
        'description',
        'color_code',
        'is_active',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the leave balances for this leave type.
     */
    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * Get the leave requests for this leave type.
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Scope to get only active leave types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only paid leave types.
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Get formatted max days per year.
     */
    public function getFormattedMaxDaysAttribute()
    {
        return $this->max_days_per_year . ' days';
    }

    /**
     * Check if this leave type requires approval.
     */
    public function requiresApproval(): bool
    {
        return $this->requires_approval;
    }

    /**
     * Get badge color class based on leave type.
     */
    public function getBadgeColorClassAttribute()
    {
        return 'bg-' . str_replace('#', '', $this->color_code) . '-100 text-' . str_replace('#', '', $this->color_code) . '-800';
    }
}
