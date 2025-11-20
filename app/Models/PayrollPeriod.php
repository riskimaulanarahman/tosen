<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'basic_rate',
        'overtime_rate',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'basic_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'status' => 'string',
    ];

    /**
     * Get payroll records for this period.
     */
    public function payrollRecords()
    {
        return $this->hasMany(PayrollRecord::class);
    }

    /**
     * Get formatted date range.
     */
    public function getFormattedDateRangeAttribute()
    {
        if (!$this->start_date) {
            return 'No dates set';
        }

        $startDate = $this->start_date->format('M d, Y');
        $endDate = $this->end_date ? $this->end_date->format('M d, Y') : 'Ongoing';
        
        return "{$startDate} - {$endDate}";
    }

    /**
     * Get duration in days.
     */
    public function getDurationDaysAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'draft' => 'bg-gray-100 text-gray-800',
            'active' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'paid' => 'bg-info-100 text-info-800',
            'archived' => 'bg-yellow-100 text-yellow-800',
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
            'active' => 'Active',
            'completed' => 'Completed',
            'paid' => 'Paid',
            'archived' => 'Archived',
        ];

        return $texts[$this->status] ?? 'Unknown';
    }

    /**
     * Check if period is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        $now = now()->startOfDay();
        
        if (!$this->start_date || !$this->end_date) {
            return false;
        }

        return $this->status === 'active' && 
               $now->gte($this->start_date) && 
               $now->lte($this->end_date);
    }

    /**
     * Get total payroll amount.
     */
    public function getTotalPayrollAmountAttribute()
    {
        return $this->payrollRecords()->sum('total_pay');
    }

    /**
     * Get total employees count.
     */
    public function getTotalEmployeesAttribute()
    {
        return $this->payrollRecords()->count();
    }

    /**
     * Scope to get active periods.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get periods by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($subQuery) use ($startDate, $endDate) {
            $subQuery->whereDate('start_date', '<=', $endDate)
                   ->whereDate('end_date', '>=', $startDate)
                   ->orWhere(function ($innerQuery) use ($startDate, $endDate) {
                       $innerQuery->whereDate('start_date', '>=', $startDate)
                              ->whereDate('end_date', '<=', $endDate);
                   });
        });
    }

    /**
     * Scope to get periods for outlet.
     */
    public function scopeForOutlet($query, $outletId)
    {
        return $query->whereHas('payrollRecords', function ($subQuery) use ($outletId) {
            $subQuery->whereHas('user', function ($innerQuery) use ($outletId) {
                $innerQuery->where('outlet_id', $outletId);
            });
        });
    }
}
