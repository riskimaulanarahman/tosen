<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'break_duration',
        'is_overnight',
        'color_code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_overnight' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get shift schedules for this shift.
     */
    public function shiftSchedules()
    {
        return $this->hasMany(ShiftSchedule::class);
    }

    /**
     * Get employee shifts for this shift.
     */
    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }

    /**
     * Get formatted time range.
     */
    public function getFormattedTimeRangeAttribute()
    {
        $startTime = $this->start_time ? $this->start_time->format('H:i') : 'N/A';
        $endTime = $this->end_time ? $this->end_time->format('H:i') : 'N/A';
        
        return "{$startTime} - {$endTime}";
    }

    /**
     * Get duration in hours.
     */
    public function getDurationHoursAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = $this->start_time->copy();
        $end = $this->end_time->copy();
        
        // Handle overnight shifts
        if ($this->is_overnight && $end->lt($start)) {
            $end->addDay();
        }

        return $start->diffInHours($end);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute()
    {
        $hours = $this->getDurationHoursAttribute();
        $breakHours = $this->break_duration / 60;
        
        $totalHours = $hours - $breakHours;
        
        if ($totalHours == (int)$totalHours) {
            return "{$totalHours} hours";
        }
        
        return number_format($totalHours, 1) . " hours";
    }

    /**
     * Get badge color class.
     */
    public function getBadgeColorClassAttribute()
    {
        return 'bg-' . str_replace('#', '', $this->color_code) . '-100 text-' . str_replace('#', '', $this->color_code) . '-800';
    }

    /**
     * Check if shift is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        $now = now();
        $startTime = $this->start_time->copy();
        $endTime = $this->end_time->copy();
        
        // Handle overnight shifts
        if ($this->is_overnight && $endTime->lt($startTime)) {
            $endTime->addDay();
        }

        return $now->between($startTime, $endTime);
    }

    /**
     * Scope to get only active shifts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get overnight shifts.
     */
    public function scopeOvernight($query)
    {
        return $query->where('is_overnight', true);
    }

    /**
     * Scope to get shifts by time range.
     */
    public function scopeByTimeRange($query, $startTime, $endTime)
    {
        return $query->whereTime('start_time', '>=', $startTime)
                    ->whereTime('end_time', '<=', $endTime);
    }
}
