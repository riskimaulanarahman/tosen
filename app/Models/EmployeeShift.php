<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_id',
        'start_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get user for this employee shift.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get shift for this employee shift.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Check if shift is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        $now = now()->startOfDay();
        
        if (!$this->is_active) {
            return false;
        }

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if shift is active for given date.
     */
    public function isActiveForDate(Carbon $date): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->start_date && $date->lt($this->start_date)) {
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
        if (!$this->start_date) {
            return 'No start date';
        }

        $startDate = $this->start_date->format('M d, Y');
        
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
     * Get status icon.
     */
    public function getStatusIconAttribute()
    {
        if (!$this->is_active) {
            return '❌';
        }

        if ($this->isCurrentlyActive()) {
            return '✅';
        }

        return '⏳';
    }

    /**
     * Scope to get active shifts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get shifts for user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get shifts for date range.
     */
    public function scopeInDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->where(function ($subQuery) use ($startDate, $endDate) {
            $subQuery->whereDate('start_date', '<=', $endDate)
                   ->whereDate('end_date', '>=', $startDate)
                   ->orWhere(function ($innerQuery) use ($startDate, $endDate) {
                       $innerQuery->whereNull('end_date')
                              ->whereDate('start_date', '<=', $endDate)
                              ->whereDate('start_date', '>=', $startDate);
                   });
        });
    }

    /**
     * Scope to get shifts expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereNotNull('end_date')
                    ->where('end_date', '<=', now()->addDays($days))
                    ->where('end_date', '>=', now());
    }
}
