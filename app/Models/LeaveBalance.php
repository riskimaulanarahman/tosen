<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'allocated_days',
        'used_days',
        'carried_over_days',
        'year',
    ];

    protected $casts = [
        'allocated_days' => 'integer',
        'used_days' => 'integer',
        'carried_over_days' => 'integer',
    ];

    /**
     * Get the user that owns the leave balance.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the leave type for this balance.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get remaining days (calculated property).
     */
    public function getRemainingDaysAttribute()
    {
        return max(0, $this->allocated_days + $this->carried_over_days - $this->used_days);
    }

    /**
     * Get total available days including carried over.
     */
    public function getTotalAvailableDaysAttribute()
    {
        return $this->allocated_days + $this->carried_over_days;
    }

    /**
     * Get usage percentage.
     */
    public function getUsagePercentageAttribute()
    {
        $total = $this->getTotalAvailableDaysAttribute();
        if ($total == 0) return 0;
        
        return round(($this->used_days / $total) * 100, 1);
    }

    /**
     * Check if user has sufficient leave balance.
     */
    public function hasSufficientBalance(int $requestedDays): bool
    {
        return $this->getRemainingDaysAttribute() >= $requestedDays;
    }

    /**
     * Deduct days from leave balance.
     */
    public function deductDays(int $days): bool
    {
        if (!$this->hasSufficientBalance($days)) {
            return false;
        }

        $this->used_days += $days;
        return $this->save();
    }

    /**
     * Add back days to leave balance (for cancelled leave).
     */
    public function addBackDays(int $days): bool
    {
        $this->used_days = max(0, $this->used_days - $days);
        return $this->save();
    }

    /**
     * Get balance status color.
     */
    public function getStatusColorAttribute()
    {
        $percentage = $this->getUsagePercentageAttribute();
        
        if ($percentage >= 90) return 'danger';
        if ($percentage >= 70) return 'warning';
        return 'success';
    }

    /**
     * Get formatted remaining days.
     */
    public function getFormattedRemainingDaysAttribute()
    {
        return $this->getRemainingDaysAttribute() . ' days';
    }

    /**
     * Scope to get balances for specific year.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope to get low balances.
     */
    public function scopeLow($query, int $threshold = 3)
    {
        return $query->whereRaw('(allocated_days + carried_over_days - used_days) <= ?', [$threshold]);
    }
}
