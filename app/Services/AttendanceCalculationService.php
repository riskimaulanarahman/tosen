<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Outlet;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceCalculationService
{
    private const DEFAULT_START_TIME = '09:00';
    private const DEFAULT_END_TIME = '18:00';
    
    /**
     * Calculate attendance status and related metrics
     */
    public static function calculateAttendanceStatus(Attendance $attendance): array
    {
        $outlet = $attendance->outlet;
        $timezone = $outlet?->timezone ?? config('app.timezone', 'Asia/Jakarta');
        $checkInTime = Carbon::parse($attendance->check_in_time)->setTimezone($timezone);
        $checkOutTime = $attendance->check_out_time
            ? Carbon::parse($attendance->check_out_time)->setTimezone($timezone)
            : null;
        
        // Get operational hours
        $operationalStart = self::parseOperationalTime(
            $outlet?->operational_start_time,
            $checkInTime,
            $timezone,
            self::DEFAULT_START_TIME
        );
        $operationalEnd = self::parseOperationalTime(
            $outlet?->operational_end_time,
            $checkInTime,
            $timezone,
            self::DEFAULT_END_TIME
        );
        [$operationalStart, $operationalEnd] = self::adjustForOvernightShift(
            $operationalStart,
            $operationalEnd,
            $checkInTime
        );
        
        $gracePeriod = $outlet?->grace_period_minutes ?? 5;
        $lateTolerance = $outlet?->late_tolerance_minutes ?? 15;
        $earlyCheckoutTolerance = $outlet?->early_checkout_tolerance ?? 10;
        $overtimeThreshold = $outlet?->overtime_threshold_minutes ?? 60;
        
        $result = [
            'attendance_status' => 'on_time',
            'late_minutes' => 0,
            'early_checkout_minutes' => 0,
            'overtime_minutes' => 0,
            'work_duration_minutes' => 0,
            'attendance_score' => 100.00,
            'is_valid_work_day' => true,
            'operational_start' => $operationalStart,
            'operational_end' => $operationalEnd,
        ];
        
        // Check if it's a work day
        if ($outlet?->work_days) {
            $workDays = is_array($outlet->work_days) ? $outlet->work_days : json_decode($outlet->work_days, true);
            if (!in_array($checkInTime->dayOfWeekIso, $workDays)) {
                $result['is_valid_work_day'] = false;
                $result['attendance_status'] = 'holiday';
                $result['attendance_score'] = 0;
                return $result;
            }
        }
        
        // Calculate lateness
        $effectiveStartTime = $operationalStart->copy()->addMinutes($gracePeriod);
        if ($checkInTime->gt($effectiveStartTime)) {
            $result['late_minutes'] = $checkInTime->diffInMinutes($effectiveStartTime);
            
            if ($result['late_minutes'] > $lateTolerance) {
                $result['attendance_status'] = 'late';
                // Deduct score based on lateness
                $scoreDeduction = min(($result['late_minutes'] - $lateTolerance) * 0.5, 50);
                $result['attendance_score'] = max(100 - $scoreDeduction, 50);
            }
        }
        
        // Calculate work duration and early checkout
        if ($checkOutTime) {
            $result['work_duration_minutes'] = $checkInTime->diffInMinutes($checkOutTime);
            
            // Check early checkout
            if ($checkOutTime->lt($operationalEnd)) {
                $result['early_checkout_minutes'] = $operationalEnd->diffInMinutes($checkOutTime);
                
                if ($result['early_checkout_minutes'] > $earlyCheckoutTolerance) {
                    $result['attendance_status'] = 'early_checkout';
                    // Additional score deduction for early checkout
                    $scoreDeduction = min(($result['early_checkout_minutes'] - $earlyCheckoutTolerance) * 0.3, 30);
                    $result['attendance_score'] -= $scoreDeduction;
                    $result['attendance_score'] = max($result['attendance_score'], 50);
                }
            }
            
            // Calculate overtime
            if ($checkOutTime->gt($operationalEnd)) {
                $result['overtime_minutes'] = $checkOutTime->diffInMinutes($operationalEnd);
                
                if ($result['overtime_minutes'] >= $overtimeThreshold) {
                    if ($result['attendance_status'] === 'on_time') {
                        $result['attendance_status'] = 'overtime';
                    }
                    // Bonus score for overtime
                    $overtimeBonus = min($result['overtime_minutes'] * 0.2, 20);
                    $result['attendance_score'] = min(100, $result['attendance_score'] + $overtimeBonus);
                }
            }
        }
        
        // Final score validation
        $result['attendance_score'] = round($result['attendance_score'], 2);
        
        return $result;
    }
    
    /**
     * Parse operational time with timezone
     */
    private static function parseOperationalTime($timeValue, Carbon $referenceDate, string $timezone, string $fallback): Carbon
    {
        $timeString = null;
        
        if ($timeValue instanceof Carbon) {
            $timeString = $timeValue->format('H:i');
        } elseif ($timeValue instanceof \DateTimeInterface) {
            $timeString = Carbon::instance($timeValue)->format('H:i');
        } elseif (is_string($timeValue) && trim($timeValue) !== '') {
            $timeString = $timeValue;
        }
        
        if (!$timeString) {
            $timeString = $fallback;
        }
        
        $timeParts = explode(':', $timeString);
        $hour = (int) $timeParts[0];
        $minute = (int) ($timeParts[1] ?? 0);
        
        return $referenceDate->copy()->setTimezone($timezone)->setTime($hour, $minute, 0);
    }

    /**
     * Normalize operational window for schedules that cross midnight.
     */
    private static function adjustForOvernightShift(Carbon $start, Carbon $end, Carbon $reference): array
    {
        $normalizedStart = $start->copy();
        $normalizedEnd = $end->copy();
        $referencePoint = $reference->copy();

        if ($normalizedEnd->lessThanOrEqualTo($normalizedStart)) {
            $normalizedEnd->addDay();

            if ($referencePoint->lt($normalizedStart)) {
                $normalizedStart->subDay();
                $normalizedEnd->subDay();
            }
        }

        return [$normalizedStart, $normalizedEnd];
    }
    
    /**
     * Calculate monthly attendance statistics
     */
    public static function calculateMonthlyStats(int $userId, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();
        
        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('check_in_time', [$startDate, $endDate])
            ->with('outlet')
            ->get();
        
        $stats = [
            'total_days' => $startDate->daysInMonth,
            'worked_days' => 0,
            'present_days' => 0,
            'late_days' => 0,
            'early_checkout_days' => 0,
            'overtime_days' => 0,
            'absent_days' => 0,
            'leave_days' => 0,
            'holidays' => 0,
            'average_score' => 0,
            'total_late_minutes' => 0,
            'total_early_checkout_minutes' => 0,
            'total_overtime_minutes' => 0,
            'total_work_hours' => 0,
            'attendance_rate' => 0,
        ];
        
        if ($attendances->isEmpty()) {
            return $stats;
        }
        
        $totalScore = 0;
        $scoredDays = 0;
        
        foreach ($attendances as $attendance) {
            $calculation = self::calculateAttendanceStatus($attendance);
            
            switch ($calculation['attendance_status']) {
                case 'on_time':
                case 'overtime':
                    $stats['present_days']++;
                    break;
                case 'late':
                    $stats['present_days']++;
                    $stats['late_days']++;
                    break;
                case 'early_checkout':
                    $stats['present_days']++;
                    $stats['early_checkout_days']++;
                    break;
                case 'absent':
                    $stats['absent_days']++;
                    break;
                case 'leave':
                    $stats['leave_days']++;
                    break;
                case 'holiday':
                    $stats['holidays']++;
                    break;
            }
            
            if ($calculation['is_valid_work_day']) {
                $stats['worked_days']++;
                $totalScore += $calculation['attendance_score'];
                $scoredDays++;
            }
            
            $stats['total_late_minutes'] += $calculation['late_minutes'];
            $stats['total_early_checkout_minutes'] += $calculation['early_checkout_minutes'];
            $stats['total_overtime_minutes'] += $calculation['overtime_minutes'];
            $stats['total_work_hours'] += $calculation['work_duration_minutes'] / 60;
        }
        
        // Calculate derived metrics
        $stats['average_score'] = $scoredDays > 0 ? round($totalScore / $scoredDays, 2) : 0;
        $stats['attendance_rate'] = $stats['worked_days'] > 0 ? 
            round(($stats['present_days'] / $stats['worked_days']) * 100, 2) : 0;
        
        return $stats;
    }
    
    /**
     * Check if user should be marked as absent
     */
    public static function checkAbsenteeism(int $userId, Carbon $date): ?bool
    {
        // Check if there's any attendance record for this date
        $hasAttendance = Attendance::where('user_id', $userId)
            ->whereDate('check_in_time', $date)
            ->exists();
        
        if ($hasAttendance) {
            return false; // User already has attendance record
        }
        
        // Get user's outlet and check if it's a work day
        $user = \App\Models\User::find($userId);
        if (!$user || !$user->outlet) {
            return null; // Cannot determine
        }
        
        $outlet = $user->outlet;
        $workDays = is_array($outlet->work_days) ? $outlet->work_days : json_decode($outlet->work_days, true);
        
        if (!in_array($date->dayOfWeekIso, $workDays)) {
            return false; // Not a work day, not absent
        }
        
        // Check if current time is past end of operational hours
        $timezone = $outlet->timezone ?? config('app.timezone', 'Asia/Jakarta');
        $referenceDate = $date->copy()->setTimezone($timezone);

        $operationalStart = self::parseOperationalTime(
            $outlet->operational_start_time,
            $referenceDate,
            $timezone,
            self::DEFAULT_START_TIME
        );
        $operationalEnd = self::parseOperationalTime(
            $outlet->operational_end_time, 
            $referenceDate, 
            $timezone,
            self::DEFAULT_END_TIME
        );

        $normalizedWindow = self::adjustForOvernightShift(
            $operationalStart,
            $operationalEnd,
            $operationalStart->copy()
        );
        $operationalEnd = $normalizedWindow[1];

        $currentTime = now()->setTimezone($timezone);
        
        return $currentTime->gt($operationalEnd);
    }
    
    /**
     * Get attendance pattern analysis
     */
    public static function getPatternAnalysis(int $userId, int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        $attendances = Attendance::where('user_id', $userId)
            ->where('check_in_time', '>=', $startDate)
            ->with('outlet')
            ->orderBy('check_in_time')
            ->get();
        
        if ($attendances->isEmpty()) {
            return [
                'pattern' => 'no_data',
                'confidence' => 0,
                'analysis' => 'Insufficient data for pattern analysis',
            ];
        }
        
        $patterns = [
            'consistent' => 0,
            'late_trend' => 0,
            'early_checkout_trend' => 0,
            'overtime_trend' => 0,
        ];
        
        $recentDays = $attendances->take(-7); // Last 7 days for trend
        $previousDays = $attendances->slice(-14, -7); // Previous 7 days for comparison
        
        foreach ($recentDays as $attendance) {
            $calculation = self::calculateAttendanceStatus($attendance);
            
            if ($calculation['attendance_status'] === 'on_time') {
                $patterns['consistent']++;
            } elseif ($calculation['attendance_status'] === 'late') {
                $patterns['late_trend']++;
            } elseif ($calculation['attendance_status'] === 'early_checkout') {
                $patterns['early_checkout_trend']++;
            } elseif ($calculation['attendance_status'] === 'overtime') {
                $patterns['overtime_trend']++;
            }
        }
        
        // Determine dominant pattern
        arsort($patterns);
        $dominantPattern = array_key_first($patterns);
        $confidence = ($patterns[$dominantPattern] / max($recentDays->count(), 1)) * 100;
        
        $analysis = self::generatePatternAnalysis($dominantPattern, $patterns, $confidence);
        
        return [
            'pattern' => $dominantPattern,
            'confidence' => round($confidence, 2),
            'analysis' => $analysis,
            'patterns' => $patterns,
        ];
    }
    
    /**
     * Generate human-readable pattern analysis
     */
    private static function generatePatternAnalysis(string $pattern, array $patterns, float $confidence): string
    {
        switch ($pattern) {
            case 'consistent':
                return $confidence > 70 ? 
                    "Excellent attendance pattern! User is consistently on time." :
                    "Generally consistent attendance with room for improvement.";
            
            case 'late_trend':
                $latePercentage = ($patterns['late_trend'] / array_sum($patterns)) * 100;
                return "Trend shows lateness in {$latePercentage}% of recent attendance. Consider addressing time management.";
            
            case 'early_checkout_trend':
                $earlyPercentage = ($patterns['early_checkout_trend'] / array_sum($patterns)) * 100;
                return "Pattern shows early checkout in {$earlyPercentage}% of recent attendance. May indicate workload or scheduling issues.";
            
            case 'overtime_trend':
                $overtimePercentage = ($patterns['overtime_trend'] / array_sum($patterns)) * 100;
                return "Strong work ethic with {$overtimePercentage}% overtime. Monitor for burnout risk.";
            
            default:
                return "Mixed attendance pattern requiring further analysis.";
        }
    }
}
