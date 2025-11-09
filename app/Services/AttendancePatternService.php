<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendancePatternService
{
    /**
     * Detect suspicious attendance patterns
     */
    public static function detectSuspiciousPatterns(int $userId, int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        $attendances = Attendance::where('user_id', $userId)
            ->where('check_in_time', '>=', $startDate)
            ->orderBy('check_in_time')
            ->get();
        
        if ($attendances->count() < 5) {
            return [
                'risk_level' => 'low',
                'patterns' => [],
                'analysis' => 'Insufficient data for pattern analysis'
            ];
        }
        
        $patterns = [];
        
        // Check for exact timing patterns
        $patterns['exact_timing'] = self::detectExactTimingPatterns($attendances);
        
        // Check for GPS location anomalies
        $patterns['location_anomalies'] = self::detectLocationAnomalies($attendances);
        
        // Check for rapid checkout patterns
        $patterns['rapid_checkout'] = self::detectRapidCheckoutPatterns($attendances);
        
        // Check for photo analysis patterns
        $patterns['photo_patterns'] = self::detectPhotoPatterns($attendances);
        
        // Check for time-based anomalies
        $patterns['time_anomalies'] = self::detectTimeAnomalies($attendances);
        
        // Calculate overall risk level
        $riskLevel = self::calculateRiskLevel($patterns);
        
        return [
            'risk_level' => $riskLevel,
            'patterns' => $patterns,
            'analysis' => self::generateSuspicionAnalysis($patterns, $riskLevel),
            'recommendations' => self::generateRecommendations($patterns, $riskLevel)
        ];
    }
    
    /**
     * Detect exact timing patterns (possible automated check-ins)
     */
    private static function detectExactTimingPatterns($attendances): array
    {
        $patternData = [
            'suspicious' => false,
            'details' => [],
            'score' => 0
        ];
        
        $checkInTimes = $attendances->pluck('check_in_time')->map(function ($time) {
            return Carbon::parse($time)->format('H:i');
        })->toArray();
        
        $checkOutTimes = $attendances->pluck('check_out_time')
            ->filter()
            ->map(function ($time) {
                return Carbon::parse($time)->format('H:i');
            })->toArray();
        
        // Check for exact same check-in times
        $checkInFrequency = array_count_values($checkInTimes);
        foreach ($checkInFrequency as $time => $count) {
            if ($count >= 3) { // Same time at least 3 times
                $patternData['suspicious'] = true;
                $patternData['details'][] = "Exact check-in time {$time} repeated {$count} times";
                $patternData['score'] += $count * 10;
            }
        }
        
        // Check for exact same check-out times
        $checkOutFrequency = array_count_values($checkOutTimes);
        foreach ($checkOutFrequency as $time => $count) {
            if ($count >= 3) {
                $patternData['suspicious'] = true;
                $patternData['details'][] = "Exact check-out time {$time} repeated {$count} times";
                $patternData['score'] += $count * 10;
            }
        }
        
        // Check for consistent work duration
        $durations = $attendances->filter(function ($attendance) {
            return $attendance->check_out_time;
        })->map(function ($attendance) {
            return Carbon::parse($attendance->check_in_time)->diffInMinutes(
                Carbon::parse($attendance->check_out_time)
            );
        })->toArray();
        
        if (count($durations) >= 5) {
            $durationFrequency = array_count_values($durations);
            foreach ($durationFrequency as $duration => $count) {
                if ($count >= 3) {
                    $patternData['suspicious'] = true;
                    $patternData['details'][] = "Identical work duration {$duration} minutes repeated {$count} times";
                    $patternData['score'] += $count * 15;
                }
            }
        }
        
        return $patternData;
    }
    
    /**
     * Detect GPS location anomalies
     */
    private static function detectLocationAnomalies($attendances): array
    {
        $patternData = [
            'suspicious' => false,
            'details' => [],
            'score' => 0
        ];
        
        $locations = $attendances->map(function ($attendance) {
            return [
                'lat' => (float) $attendance->check_in_latitude,
                'lng' => (float) $attendance->check_in_longitude,
                'date' => Carbon::parse($attendance->check_in_time)->format('Y-m-d')
            ];
        })->toArray();
        
        if (count($locations) < 3) {
            return $patternData;
        }
        
        // Check for identical coordinates
        $coordinateFrequency = [];
        foreach ($locations as $location) {
            $key = round($location['lat'], 6) . ',' . round($location['lng'], 6);
            $coordinateFrequency[$key] = ($coordinateFrequency[$key] ?? 0) + 1;
        }
        
        foreach ($coordinateFrequency as $coord => $count) {
            if ($count >= 3) {
                $patternData['suspicious'] = true;
                $patternData['details'][] = "Identical GPS coordinates repeated {$count} times";
                $patternData['score'] += $count * 8;
            }
        }
        
        // Check for unrealistic distance patterns
        for ($i = 1; $i < count($locations); $i++) {
            $prevLocation = $locations[$i - 1];
            $currentLocation = $locations[$i];
            
            $distance = self::calculateDistance(
                $prevLocation['lat'], $prevLocation['lng'],
                $currentLocation['lat'], $currentLocation['lng']
            );
            
            // If distance is less than 1 meter between consecutive days, suspicious
            if ($distance < 1 && $distance > 0) {
                $patternData['suspicious'] = true;
                $patternData['details'][] = "Unrealistic GPS precision ({$distance}m) between consecutive days";
                $patternData['score'] += 15;
            }
        }
        
        return $patternData;
    }
    
    /**
     * Detect rapid checkout patterns
     */
    private static function detectRapidCheckoutPatterns($attendances): array
    {
        $patternData = [
            'suspicious' => false,
            'details' => [],
            'score' => 0
        ];
        
        $rapidCheckouts = $attendances->filter(function ($attendance) {
            if (!$attendance->check_out_time) return false;
            
            $duration = Carbon::parse($attendance->check_in_time)
                ->diffInMinutes(Carbon::parse($attendance->check_out_time));
            
            return $duration < 30; // Less than 30 minutes
        });
        
        if ($rapidCheckouts->count() > 0) {
            $patternData['suspicious'] = true;
            $patternData['details'][] = "{$rapidCheckouts->count()} rapid checkouts (< 30 minutes)";
            $patternData['score'] = $rapidCheckouts->count() * 20;
        }
        
        // Check for same-day check-in/check-out patterns
        $sameDayPatterns = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->check_in_time)->format('Y-m-d');
        });
        
        foreach ($sameDayPatterns as $date => $dayAttendances) {
            if ($dayAttendances->count() > 1) {
                $patternData['suspicious'] = true;
                $patternData['details'][] = "Multiple attendance records on {$date}";
                $patternData['score'] += $dayAttendances->count() * 10;
            }
        }
        
        return $patternData;
    }
    
    /**
     * Detect photo-related patterns
     */
    private static function detectPhotoPatterns($attendances): array
    {
        $patternData = [
            'suspicious' => false,
            'details' => [],
            'score' => 0
        ];
        
        $attendancesWithPhotos = $attendances->filter(function ($attendance) {
            return !empty($attendance->check_in_selfie_path);
        });
        
        if ($attendancesWithPhotos->isEmpty()) {
            $patternData['suspicious'] = true;
            $patternData['details'][] = "No selfie photos found in attendance records";
            $patternData['score'] = 50;
            return $patternData;
        }
        
        // Check for missing photos
        $missingPhotos = $attendances->count() - $attendancesWithPhotos->count();
        if ($missingPhotos > 0) {
            $patternData['suspicious'] = true;
            $patternData['details'][] = "{$missingPhotos} attendance records without selfie photos";
            $patternData['score'] += $missingPhotos * 15;
        }
        
        // Check for identical photo sizes (possible duplicate photos)
        $photoSizes = $attendancesWithPhotos->pluck('check_in_file_size')->filter()->toArray();
        if (count($photoSizes) >= 3) {
            $sizeFrequency = array_count_values($photoSizes);
            foreach ($sizeFrequency as $size => $count) {
                if ($count >= 3) {
                    $patternData['suspicious'] = true;
                    $patternData['details'][] = "Identical photo sizes ({$size} bytes) repeated {$count} times";
                    $patternData['score'] += $count * 10;
                }
            }
        }
        
        return $patternData;
    }
    
    /**
     * Detect time-based anomalies
     */
    private static function detectTimeAnomalies($attendances): array
    {
        $patternData = [
            'suspicious' => false,
            'details' => [],
            'score' => 0
        ];
        
        // Check for check-ins outside reasonable hours
        $unusualHours = $attendances->filter(function ($attendance) {
            $hour = Carbon::parse($attendance->check_in_time)->hour;
            return $hour < 5 || $hour > 23; // Before 5 AM or after 11 PM
        });
        
        if ($unusualHours->count() > 0) {
            $patternData['suspicious'] = true;
            $patternData['details'][] = "{$unusualHours->count()} check-ins at unusual hours";
            $patternData['score'] += $unusualHours->count() * 10;
        }
        
        // Check for weekend patterns
        $weekendAttendance = $attendances->filter(function ($attendance) {
            $dayOfWeek = Carbon::parse($attendance->check_in_time)->dayOfWeek;
            return $dayOfWeek === 0 || $dayOfWeek === 6; // Sunday or Saturday
        });
        
        if ($weekendAttendance->count() > 0) {
            $patternData['details'][] = "{$weekendAttendance->count()} weekend attendance records";
            $patternData['score'] += $weekendAttendance->count() * 5;
        }
        
        return $patternData;
    }
    
    /**
     * Calculate overall risk level
     */
    private static function calculateRiskLevel(array $patterns): string
    {
        $totalScore = 0;
        $suspiciousPatterns = 0;
        
        foreach ($patterns as $pattern) {
            if ($pattern['suspicious']) {
                $suspiciousPatterns++;
                $totalScore += $pattern['score'];
            }
        }
        
        if ($totalScore >= 100 || $suspiciousPatterns >= 4) {
            return 'high';
        } elseif ($totalScore >= 50 || $suspiciousPatterns >= 2) {
            return 'medium';
        } elseif ($totalScore >= 20 || $suspiciousPatterns >= 1) {
            return 'low';
        }
        
        return 'none';
    }
    
    /**
     * Generate suspicion analysis
     */
    private static function generateSuspicionAnalysis(array $patterns, string $riskLevel): string
    {
        $analyses = [
            'high' => 'Multiple suspicious patterns detected. Manual review strongly recommended.',
            'medium' => 'Some unusual patterns detected. Consider verification.',
            'low' => 'Minor anomalies detected. Monitor for changes.',
            'none' => 'No suspicious patterns detected.'
        ];
        
        return $analyses[$riskLevel] ?? 'Unable to determine risk level.';
    }
    
    /**
     * Generate recommendations
     */
    private static function generateRecommendations(array $patterns, string $riskLevel): array
    {
        $recommendations = [];
        
        if ($patterns['exact_timing']['suspicious']) {
            $recommendations[] = 'Review exact timing patterns - possible automated check-ins';
        }
        
        if ($patterns['location_anomalies']['suspicious']) {
            $recommendations[] = 'Verify GPS coordinates - possible location spoofing';
        }
        
        if ($patterns['rapid_checkout']['suspicious']) {
            $recommendations[] = 'Investigate rapid checkouts - possible system abuse';
        }
        
        if ($patterns['photo_patterns']['suspicious']) {
            $recommendations[] = 'Check selfie photos - possible photo duplication or missing photos';
        }
        
        if ($riskLevel === 'high') {
            $recommendations[] = 'Immediate supervisor review required';
            $recommendations[] = 'Consider temporary attendance monitoring';
        } elseif ($riskLevel === 'medium') {
            $recommendations[] = 'Schedule periodic attendance review';
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate distance between two GPS coordinates
     */
    private static function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c; // Distance in meters
    }
    
    /**
     * Get comprehensive user behavior analysis
     */
    public static function getBehaviorAnalysis(int $userId, int $days = 90): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        $attendances = Attendance::where('user_id', $userId)
            ->where('check_in_time', '>=', $startDate)
            ->orderBy('check_in_time')
            ->get();
        
        if ($attendances->isEmpty()) {
            return [
                'status' => 'no_data',
                'message' => 'No attendance data available for analysis'
            ];
        }
        
        // Basic statistics
        $totalDays = $attendances->count();
        $avgWorkDuration = $attendances->filter(function ($a) { return $a->check_out_time; })
            ->avg(function ($a) {
                return Carbon::parse($a->check_in_time)
                    ->diffInMinutes(Carbon::parse($a->check_out_time));
            });
        
        // Peak hours analysis
        $checkInHours = $attendances->pluck('check_in_time')->map(function ($time) {
            return Carbon::parse($time)->hour;
        })->toArray();
        
        $hourFrequency = array_count_values($checkInHours);
        $peakCheckInHour = array_keys($hourFrequency, max($hourFrequency))[0] ?? 0;
        
        // Consistency score
        $consistencyScore = self::calculateConsistencyScore($attendances);
        
        // Reliability score
        $reliabilityScore = self::calculateReliabilityScore($attendances, $days);
        
        // Pattern detection
        $suspiciousPatterns = self::detectSuspiciousPatterns($userId, $days);
        
        return [
            'status' => 'success',
            'statistics' => [
                'total_attendance_days' => $totalDays,
                'average_work_duration_minutes' => round($avgWorkDuration ?? 0),
                'peak_check_in_hour' => $peakCheckInHour,
                'consistency_score' => $consistencyScore,
                'reliability_score' => $reliabilityScore,
            ],
            'patterns' => $suspiciousPatterns,
            'trends' => self::analyzeTrends($attendances),
            'recommendations' => self::getBehavioralRecommendations(
                $consistencyScore, 
                $reliabilityScore, 
                $suspiciousPatterns['risk_level']
            )
        ];
    }
    
    /**
     * Calculate consistency score based on timing patterns
     */
    private static function calculateConsistencyScore($attendances): float
    {
        if ($attendances->count() < 5) return 50;
        
        $checkInTimes = $attendances->pluck('check_in_time')->map(function ($time) {
            return Carbon::parse($time)->format('H:i:s');
        })->toArray();
        
        // Calculate standard deviation of check-in times
        $timeInSeconds = array_map(function ($time) {
            $parts = explode(':', $time);
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }, $checkInTimes);
        
        $mean = array_sum($timeInSeconds) / count($timeInSeconds);
        $variance = array_sum(array_map(function ($time) use ($mean) {
            return pow($time - $mean, 2);
        }, $timeInSeconds)) / count($timeInSeconds);
        
        $stdDev = sqrt($variance);
        
        // Convert to minutes and score (lower deviation = higher score)
        $stdDevMinutes = $stdDev / 60;
        $score = max(0, 100 - ($stdDevMinutes * 2));
        
        return round($score, 2);
    }
    
    /**
     * Calculate reliability score
     */
    private static function calculateReliabilityScore($attendances, int $days): float
    {
        $expectedDays = 0;
        $actualDays = 0;
        
        // Calculate expected work days
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);
            if ($date->dayOfWeek >= 1 && $date->dayOfWeek <= 5) { // Weekdays
                $expectedDays++;
                
                if ($attendances->contains(function ($attendance) use ($date) {
                    return Carbon::parse($attendance->check_in_time)->isSameDay($date);
                })) {
                    $actualDays++;
                }
            }
        }
        
        return $expectedDays > 0 ? round(($actualDays / $expectedDays) * 100, 2) : 0;
    }
    
    /**
     * Analyze trends over time
     */
    private static function analyzeTrends($attendances): array
    {
        $recent = $attendances->take(-7);
        $previous = $attendances->slice(-14, -7);
        
        if ($recent->count() < 3 || $previous->count() < 3) {
            return ['status' => 'insufficient_data'];
        }
        
        $recentAvgDuration = $recent->filter(function ($a) { return $a->check_out_time; })
            ->avg(function ($a) {
                return Carbon::parse($a->check_in_time)
                    ->diffInMinutes(Carbon::parse($a->check_out_time));
            });
        
        $previousAvgDuration = $previous->filter(function ($a) { return $a->check_out_time; })
            ->avg(function ($a) {
                return Carbon::parse($a->check_in_time)
                    ->diffInMinutes(Carbon::parse($a->check_out_time));
            });
        
        $durationTrend = 'stable';
        if ($recentAvgDuration > $previousAvgDuration * 1.1) {
            $durationTrend = 'increasing';
        } elseif ($recentAvgDuration < $previousAvgDuration * 0.9) {
            $durationTrend = 'decreasing';
        }
        
        return [
            'work_duration_trend' => $durationTrend,
            'recent_avg_duration' => round($recentAvgDuration ?? 0),
            'previous_avg_duration' => round($previousAvgDuration ?? 0),
        ];
    }
    
    /**
     * Get behavioral recommendations
     */
    private static function getBehavioralRecommendations(float $consistency, float $reliability, string $riskLevel): array
    {
        $recommendations = [];
        
        if ($consistency < 60) {
            $recommendations[] = 'Focus on improving check-in time consistency';
        }
        
        if ($reliability < 80) {
            $recommendations[] = 'Improve attendance reliability and regularity';
        }
        
        if ($riskLevel === 'high') {
            $recommendations[] = 'Immediate review of attendance patterns required';
        } elseif ($riskLevel === 'medium') {
            $recommendations[] = 'Monitor attendance patterns closely';
        }
        
        if ($consistency > 80 && $reliability > 90 && $riskLevel === 'none') {
            $recommendations[] = 'Excellent attendance pattern! Keep up the good work.';
        }
        
        return $recommendations;
    }
}
