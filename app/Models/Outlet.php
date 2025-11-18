<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Outlet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'radius',
        'operational_start_time',
        'operational_end_time',
        'work_days',
        'timezone',
        'late_tolerance_minutes',
        'early_checkout_tolerance',
        'grace_period_minutes',
        'overtime_threshold_minutes',
        'owner_id',
        'overtime_config',
    ];

    protected $appends = [
        'operational_start_time_formatted',
        'operational_end_time_formatted',
        'operational_status',
        'formatted_work_days',
    ];

    /**
     * Get formatted work days.
     */
    public function getFormattedWorkDaysAttribute()
    {
        if (empty($this->work_days)) {
            return 'Tidak ada jadwal';
        }

        // Map numeric values (1-7) to Indonesian day names
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu'
        ];

        // Filter out null values and get actual day values
        $days = array_filter($this->work_days, function ($day) {
            return !is_null($day);
        });

        // Map day values to Indonesian names
        $formattedDays = array_map(function ($day) use ($dayNames) {
            return $dayNames[$day] ?? $day;
        }, $days);

        return implode(', ', $formattedDays);
    }

    /**
     * Get overtime configuration attribute.
     */
    public function getOvertimeConfigAttribute($value)
    {
        return $value ? json_decode($value, true) : $this->getDefaultOvertimeConfig();
    }

    /**
     * Set overtime configuration attribute.
     */
    public function setOvertimeConfigAttribute($value)
    {
        $this->attributes['overtime_config'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get default overtime configuration.
     */
    public function getDefaultOvertimeConfig()
    {
        return [
            'enabled' => true,
            'overtime' => [
                'threshold_minutes' => $this->overtime_threshold_minutes ?? 60,
                'mandatory_remarks' => true,
                'remarks_min_length' => 10,
                'remarks_max_length' => 500
            ],
            'early_checkout' => [
                'enabled' => true,
                'threshold_minutes' => $this->early_checkout_tolerance ?? 240,
                'mandatory_remarks' => true,
                'remarks_min_length' => 10,
                'remarks_max_length' => 300
            ],
            'advanced' => [
                // 'max_daily_overtime' => 480,
                'weekend_multiplier' => 1.5,
                'holiday_multiplier' => 2.0,
                'auto_calculate_overtime' => true,
                'overtime_approval_required' => false
            ]
        ];
    }

    /**
     * Check if overtime remarks are required.
     */
    public function requiresOvertimeRemarks($overtimeMinutes)
    {
        $config = $this->overtime_config;
        if (
            empty($config['enabled']) ||
            !isset($config['overtime']) ||
            empty($config['overtime']['mandatory_remarks'])
        ) {
            return false;
        }

        $threshold = $config['overtime']['threshold_minutes']
            ?? $this->overtime_threshold_minutes
            ?? null;

        if ($threshold === null) {
            return false;
        }

        return $overtimeMinutes >= (int) $threshold;
    }

    /**
     * Check if early checkout remarks are required.
     */
    public function requiresEarlyCheckoutRemarks($workDurationMinutes)
    {
        $config = $this->overtime_config;
        if (
            empty($config['enabled']) ||
            !isset($config['early_checkout']) ||
            empty($config['early_checkout']['enabled']) ||
            empty($config['early_checkout']['mandatory_remarks'])
        ) {
            return false;
        }

        $threshold = $config['early_checkout']['threshold_minutes']
            ?? $this->early_checkout_tolerance
            ?? null;

        if ($threshold === null) {
            return false;
        }

        return $workDurationMinutes < (int) $threshold;
    }

    /**
     * Check if should show early checkout warning.
     */
    public function shouldShowEarlyCheckoutWarning($workDurationMinutes)
    {
        $config = $this->overtime_config;
        if (
            empty($config['enabled']) ||
            !isset($config['early_checkout']) ||
            empty($config['early_checkout']['enabled'])
        ) {
            return false;
        }

        $threshold = $config['early_checkout']['threshold_minutes']
            ?? $this->early_checkout_tolerance
            ?? null;

        if ($threshold === null) {
            return false;
        }

        return $workDurationMinutes < (int) $threshold;
    }

    /**
     * Calculate overtime minutes for given checkout time.
     */
    public function calculateOvertime($checkoutTime)
    {
        if (!$this->operational_end_time) {
            return 0;
        }

        // Create end datetime with the same date as checkout time
        $endDateTime = $checkoutTime->copy()->setTimeFromTimeString($this->operational_end_time);
        
        // If checkout is before or at operational end, no overtime
        if ($checkoutTime->lte($endDateTime)) {
            return 0;
        }

        // Calculate overtime (checkout time - end time)
        return $endDateTime->diffInMinutes($checkoutTime);
    }

    /**
     * Get overtime remarks validation rules.
     */
    public function getOvertimeRemarksRules()
    {
        $config = $this->overtime_config;
        if (!isset($config['overtime'])) {
            return [];
        }

        return [
            'min' => $config['overtime']['remarks_min_length'] ?? 10,
            'max' => $config['overtime']['remarks_max_length'] ?? 500
        ];
    }

    /**
     * Get early checkout remarks validation rules.
     */
    public function getEarlyCheckoutRemarksRules()
    {
        $config = $this->overtime_config;
        if (!isset($config['early_checkout'])) {
            return [];
        }

        return [
            'min' => $config['early_checkout']['remarks_min_length'] ?? 10,
            'max' => $config['early_checkout']['remarks_max_length'] ?? 300
        ];
    }

    /**
     * Get the owner of the outlet.
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
     * Get time until next operational time (legacy method for compatibility).
     */
    public function getTimeUntilNextOperational(): string
    {
        $nextOperational = $this->getNextOperationalTime();

        if (!$nextOperational) {
            return 'No operational hours set';
        }

        $now = now()->setTimezone($this->timezone ?? 'Asia/Jakarta');
        $secondsUntilNext = (int)$now->diffInSeconds($nextOperational);

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
     * Get formatted time until next operational (user-friendly in Indonesian).
     */
    public function getFormattedTimeUntilNext(): string
    {
        $nextOperational = $this->getNextOperationalTime();

        if (!$nextOperational) {
            return 'Tidak ada jam operasional';
        }

        $now = now()->setTimezone($this->timezone ?? 'Asia/Jakarta');
        $secondsUntilNext = (int)$now->diffInSeconds($nextOperational);

        if ($secondsUntilNext <= 0) {
            return 'sekarang';
        }

        $days = intdiv($secondsUntilNext, 86400);
        $secondsUntilNext -= $days * 86400;
        $hours = intdiv($secondsUntilNext, 3600);
        $secondsUntilNext -= $hours * 3600;
        $minutes = intdiv($secondsUntilNext, 60);

        // Format yang lebih user-friendly dalam bahasa Indonesia
        if ($days > 0) {
            return "{$days} hari " . ($hours > 0 ? "{$hours} jam" : '');
        }

        if ($hours > 0) {
            return "{$hours} jam " . ($minutes > 0 ? "{$minutes} menit" : '');
        }

        if ($minutes > 0) {
            return "{$minutes} menit";
        }

        return 'kurang dari 1 menit';
    }

    /**
     * Get day name in Indonesian.
     */
    public function getDayNameInIndonesian(int $dayOfWeekIso): string
    {
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu'
        ];

        return $dayNames[$dayOfWeekIso] ?? 'Unknown';
    }

    /**
     * Get operational status with color.
     */
    public function getOperationalStatusAttribute()
    {
        if ($this->isCurrentlyOperational()) {
            return [
                'status' => 'open',
                'text' => 'Sedang Buka',
                'color' => 'success',
                'time' => $this->getFormattedOperationalHoursAttribute()
            ];
        }

        $nextTime = $this->getFormattedTimeUntilNext();
        $nextOperationalTime = $this->getNextOperationalTime();
        
        return [
            'status' => 'closed',
            'text' => "Buka dalam {$nextTime}",
            'color' => 'warning',
            'time' => $this->getFormattedOperationalHoursAttribute(),
            'time_until_next' => $nextTime,
            'next_open_time' => $nextOperationalTime ? $nextOperationalTime->format('H:i') : null,
            'next_open_day' => $nextOperationalTime ? $this->getDayNameInIndonesian($nextOperationalTime->dayOfWeekIso) : null
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
