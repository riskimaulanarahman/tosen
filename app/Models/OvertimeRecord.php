<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_record_id',
        'attendance_id',
        'overtime_minutes',
        'rate_multiplier',
        'overtime_amount',
        'overtime_type',
        'notes',
    ];

    protected $casts = [
        'overtime_minutes' => 'integer',
        'rate_multiplier' => 'decimal:3',
        'overtime_amount' => 'decimal:2',
        'overtime_type' => 'string',
    ];

    /**
     * Get payroll record for this overtime.
     */
    public function payrollRecord()
    {
        return $this->belongsTo(PayrollRecord::class);
    }

    /**
     * Get attendance for this overtime.
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Get formatted overtime duration.
     */
    public function getFormattedDurationAttribute()
    {
        $hours = floor($this->overtime_minutes / 60);
        $minutes = $this->overtime_minutes % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes}m";
    }

    /**
     * Get formatted overtime amount.
     */
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->overtime_amount, 2, ',', '.');
    }

    /**
     * Get overtime type badge class.
     */
    public function getTypeBadgeClassAttribute()
    {
        $classes = [
            'regular' => 'bg-blue-100 text-blue-800',
            'weekend' => 'bg-orange-100 text-orange-800',
            'holiday' => 'bg-red-100 text-red-800',
        ];

        return $classes[$this->overtime_type] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get overtime type text.
     */
    public function getTypeTextAttribute()
    {
        $texts = [
            'regular' => 'Regular',
            'weekend' => 'Weekend',
            'holiday' => 'Holiday',
        ];

        return $texts[$this->overtime_type] ?? 'Unknown';
    }

    /**
     * Get rate multiplier text.
     */
    public function getRateMultiplierTextAttribute()
    {
        return $this->rate_multiplier . 'x';
    }

    /**
     * Scope to get records by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('overtime_type', $type);
    }

    /**
     * Scope to get records by payroll period.
     */
    public function scopeByPayrollPeriod($query, $payrollPeriodId)
    {
        return $query->where('payroll_period_id', $payrollPeriodId);
    }

    /**
     * Scope to get records by attendance.
     */
    public function scopeByAttendance($query, $attendanceId)
    {
        return $query->where('attendance_id', $attendanceId);
    }

    /**
     * Scope to get records above threshold.
     */
    public function scopeAboveThreshold($query, $thresholdMinutes = 60)
    {
        return $query->where('overtime_minutes', '>', $thresholdMinutes);
    }
}
