<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id',
        'user_id',
        'base_salary',
        'overtime_pay',
        'leave_deduction',
        'bonus',
        'tax_deduction',
        'other_deductions',
        'total_pay',
        'status',
        'notes',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'leave_deduction' => 'decimal:2',
        'bonus' => 'decimal:2',
        'tax_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_pay' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Get payroll period for this record.
     */
    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    /**
     * Get user for this payroll record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get overtime records for this payroll.
     */
    public function overtimeRecords()
    {
        return $this->hasMany(OvertimeRecord::class);
    }

    /**
     * Get formatted total pay.
     */
    public function getFormattedTotalPayAttribute()
    {
        return 'Rp ' . number_format($this->total_pay, 2, ',', '.');
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'draft' => 'bg-gray-100 text-gray-800',
            'calculated' => 'bg-blue-100 text-blue-800',
            'approved' => 'bg-green-100 text-green-800',
            'paid' => 'bg-success-100 text-success-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];

        return $classes[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get status text.
     */
    public function getStatusTextAttribute()
    {
        $texts = [
            'draft' => 'Draft',
            'calculated' => 'Calculated',
            'approved' => 'Approved',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
        ];

        return $texts[$this->status] ?? 'Unknown';
    }

    /**
     * Get status icon.
     */
    public function getStatusIconAttribute()
    {
        $icons = [
            'draft' => '📝',
            'calculated' => '🧮',
            'approved' => '✅',
            'paid' => '💰',
            'cancelled' => '❌',
        ];

        return $icons[$this->status] ?? '❓';
    }

    /**
     * Check if record is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if record is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if record can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'calculated']);
    }

    /**
     * Get net pay (total - deductions).
     */
    public function getNetPayAttribute()
    {
        return $this->total_pay - $this->tax_deduction - $this->other_deductions;
    }

    /**
     * Get formatted net pay.
     */
    public function getFormattedNetPayAttribute()
    {
        return 'Rp ' . number_format($this->getNetPayAttribute(), 2, ',', '.');
    }

    /**
     * Scope to get records by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get records for user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get records for payroll period.
     */
    public function scopeForPayrollPeriod($query, $payrollPeriodId)
    {
        return $query->where('payroll_period_id', $payrollPeriodId);
    }

    /**
     * Scope to get records for date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get paid records.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to get unpaid records.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', 'paid');
    }
}
