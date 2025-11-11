<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outlet extends Model
{
    use HasFactory;

    protected $appends = [
        'operational_start_time_formatted',
        'operational_end_time_formatted',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'radius',
        'owner_id',
        'operational_start_time',
        'operational_end_time',
        'late_tolerance_minutes',
        'early_checkout_tolerance',
        'work_days',
        'timezone',
        'grace_period_minutes',
        'overtime_threshold_minutes',
    ];

    /**
     * Get the owner that owns the outlet.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the employees that work at the outlet.
     */
    public function employees()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the attendances for the outlet.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'operational_start_time' => 'datetime',
            'operational_end_time' => 'datetime',
            'late_tolerance_minutes' => 'integer',
            'early_checkout_tolerance' => 'integer',
            'grace_period_minutes' => 'integer',
            'overtime_threshold_minutes' => 'integer',
            'work_days' => 'array',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'radius' => 'integer',
        ];
    }

    /**
     * Get formatted operational hours.
     */
    public function getFormattedOperationalHoursAttribute()
    {
        $start = $this->operational_start_time ? $this->operational_start_time->format('H:i') : 'N/A';
        $end = $this->operational_end_time ? $this->operational_end_time->format('H:i') : 'N/A';
        
        return "{$start} - {$end}";
    }

    public function getOperationalStartTimeFormattedAttribute(): ?string
    {
        if (!$this->operational_start_time) {
            return null;
        }

        // Don't convert timezone - display as stored local time
        return $this->operational_start_time->format('H:i');
    }

    public function getOperationalEndTimeFormattedAttribute(): ?string
    {
        if (!$this->operational_end_time) {
            return null;
        }

        // Don't convert timezone - display as stored local time
        return $this->operational_end_time->format('H:i');
    }

    /**
     * Get formatted work days.
     */
    public function getFormattedWorkDaysAttribute()
    {
        if (!$this->work_days || empty($this->work_days)) {
            return 'Not set';
        }

        $dayNames = [
            1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 
            4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'
        ];

        $days = array_map(function ($day) use ($dayNames) {
            return $dayNames[$day] ?? 'Unknown';
        }, $this->work_days);

        return implode(', ', $days);
    }

    /**
     * Check if outlet is currently operational.
     */
    public function isCurrentlyOperational(): bool
    {
        $timezone = $this->timezone ?? config('app.timezone', 'Asia/Jakarta');
        $now = now()->setTimezone($timezone);
        
        // Check if it's a work day
        if ($this->work_days && !in_array($now->dayOfWeekIso, $this->work_days)) {
            return false;
        }

        $window = $this->resolveOperationalWindow($now);

        if (!$window) {
            return false;
        }

        [$startTime, $endTime] = $window;

        return $now->between($startTime, $endTime);
    }

    /**
     * Get next operational time.
     */
    public function getNextOperationalTime(): ?\Carbon\Carbon
    {
        $now = now()->setTimezone($this->timezone ?? 'Asia/Jakarta');
        
        // If currently operational, return current start time
        if ($this->isCurrentlyOperational()) {
            $window = $this->resolveOperationalWindow($now);

            return $window ? $window[0] : null;
        }

        // Find next operational day
        for ($i = 0; $i < 7; $i++) {
            $checkDate = $now->copy()->addDays($i);
            
            if ($this->work_days && in_array($checkDate->dayOfWeekIso, $this->work_days)) {
                return $checkDate->copy()->setTimeFromTimeString($this->operational_start_time->format('H:i'));
            }
        }

        return null;
    }

    /**
     * Get time until next operational time.
     */
    public function getTimeUntilNextOperational(): string
    {
        $nextOperational = $this->getNextOperationalTime();

        if (!$nextOperational) {
            return 'No operational hours set';
        }

        $now = now()->setTimezone($this->timezone ?? 'Asia/Jakarta');
        $secondsUntilNext = $now->diffInSeconds($nextOperational);

        if ($secondsUntilNext <= 0) {
            return 'Now';
        }

        $days = intdiv($secondsUntilNext, 86400);
        $secondsUntilNext -= $days * 86400;
        $hours = intdiv($secondsUntilNext, 3600);
        $secondsUntilNext -= $hours * 3600;
        $minutes = intdiv($secondsUntilNext, 60);

        $parts = [];

        if ($days > 0) {
            $parts[] = "{$days} day" . ($days > 1 ? 's' : '');
        }

        if ($hours > 0) {
            $parts[] = "{$hours} hour" . ($hours > 1 ? 's' : '');
        }

        if ($minutes > 0) {
            $parts[] = "{$minutes} minute" . ($minutes > 1 ? 's' : '');
        }

        if (empty($parts)) {
            $parts[] = 'less than a minute';
        }

        return implode(' ', $parts);
    }

    /**
     * Get operational status with color.
     */
    public function getOperationalStatusAttribute()
    {
        if ($this->isCurrentlyOperational()) {
            return [
                'status' => 'open',
                'text' => 'Open Now',
                'color' => 'success',
                'time' => $this->getFormattedOperationalHoursAttribute()
            ];
        }

        $nextTime = $this->getTimeUntilNextOperational();
        
        return [
            'status' => 'closed',
            'text' => "Opens in {$nextTime}",
            'color' => 'warning',
            'time' => $this->getFormattedOperationalHoursAttribute()
        ];
    }

    /**
     * Get tolerance settings summary.
     */
    public function getToleranceSettingsAttribute()
    {
        return [
            'grace_period' => $this->grace_period_minutes ?? 5,
            'late_tolerance' => $this->late_tolerance_minutes ?? 15,
            'early_checkout_tolerance' => $this->early_checkout_tolerance ?? 10,
            'overtime_threshold' => $this->overtime_threshold_minutes ?? 60,
        ];
    }

    /**
     * Resolve operational window for a given reference time.
     */
    private function resolveOperationalWindow(?Carbon $reference = null): ?array
    {
        if (!$this->operational_start_time || !$this->operational_end_time) {
            return null;
        }

        $timezone = $this->timezone ?? config('app.timezone', 'Asia/Jakarta');
        $reference = ($reference ?? now())->copy()->setTimezone($timezone);

        $startTime = $reference->copy()->setTimeFromTimeString($this->operational_start_time->format('H:i'));
        $endTime = $reference->copy()->setTimeFromTimeString($this->operational_end_time->format('H:i'));

        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endTime->addDay();

            if ($reference->lt($startTime)) {
                $startTime->subDay();
                $endTime->subDay();
            }
        }

        return [$startTime, $endTime];
    }

    /**
     * Scope to get outlets that are currently operational.
     */
    public function scopeCurrentlyOperational($query)
    {
        return $query->whereHas('attendances', function ($q) {
            $q->whereDate('check_in_time', now());
        });
    }

    /**
     * Scope to get outlets by timezone.
     */
    public function scopeByTimezone($query, $timezone)
    {
        return $query->where('timezone', $timezone);
    }

    /**
     * Check if user can check in at this outlet.
     */
    public function canUserCheckIn($user): bool
    {
        // Check if user is assigned to this outlet
        if (!$user || $user->outlet_id !== $this->id) {
            return false;
        }

        // Check if within operational hours
        return $this->isCurrentlyOperational();
    }

    /**
     * Get distance from a given point.
     */
    public function getDistanceFrom($latitude, $longitude): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $latDiff = deg2rad($latitude - $this->latitude);
        $lngDiff = deg2rad($longitude - $this->longitude);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c; // Distance in meters
    }

    /**
     * Check if a point is within the outlet radius.
     */
    public function isPointWithinRadius($latitude, $longitude): bool
    {
        $distance = $this->getDistanceFrom($latitude, $longitude);
        return $distance <= ($this->radius ?? 50);
    }
}
