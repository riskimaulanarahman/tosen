<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ShiftSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'shift_id',
        'effective_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the outlet for this shift schedule.
     */
    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Get the shift for this schedule.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get employee shifts for this schedule.
     */
    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }

    /**
     * Check if schedule is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        $now = now()->startOfDay();
        
        if (!$this->is_active) {
            return false;
        }

        if ($this->effective_date && $now->lt($this->effective_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if schedule is valid for given date.
     */
    public function isValidForDate(Carbon $date): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->effective_date && $date->lt($this->effective_date)) {
            return false;
        }

        if ($this->end_date && $date->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Get formatted date range.
     */
    public function getFormattedDateRangeAttribute()
    {
        if (!$this->effective_date) {
            return 'No effective date';
        }

        $startDate = $this->effective_date->format('M d, Y');
        
        if ($this->end_date) {
            $endDate = $this->end_date->format('M d, Y');
            return "{$startDate} - {$endDate}";
        }

        return "{$startDate} onwards";
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute()
    {
        if (!$this->is_active) {
            return 'bg-gray-100 text-gray-800';
        }

        if ($this->isCurrentlyActive()) {
            return 'bg-success-100 text-success-800';
        }

        return 'bg-warning-100 text-warning-800';
    }

    /**
     * Get status text.
     */
    public function getStatusTextAttribute()
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        if ($this->isCurrentlyActive()) {
            return 'Active';
        }

        return 'Scheduled';
    }

    /**
     * Scope to get active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get schedules for outlet.
     */
    public function scopeForOutlet($query, $outletId)
    {
        return $query->where('outlet_id', $outletId);
    }

    /**
     * Scope to get schedules valid for date.
     */
    public function scopeValidForDate($query, Carbon $date)
    {
        return $query->where(function ($subQuery) use ($date) {
            $subQuery->whereNull('effective_date')
                   ->orWhere('effective_date', '<=', $date)
                   ->where(function ($innerQuery) use ($date) {
                       $innerQuery->whereNull('end_date')
                              ->orWhere('end_date', '>=', $date);
                   });
        });
    }

    /**
     * Scope to get schedules expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('end_date')
                    ->where('end_date', '<=', now()->addDays($days))
                    ->where('end_date', '>=', now());
    }
}
