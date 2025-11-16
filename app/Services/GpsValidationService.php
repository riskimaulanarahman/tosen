<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GpsValidationService
{
    /**
     * Validate GPS coordinates for spoofing detection.
     */
    public static function validateLocation($latitude, $longitude, $userId, $outlet, $accuracy = null)
    {
        $validationResult = [
            'valid' => true,
            'warnings' => [],
            'risk_score' => 0,
            'user_message' => '',
            'retry_suggested' => false
        ];

        // 1. Basic coordinate validity check - only reject for obviously invalid coordinates
        $coordValidation = self::validateCoordinates($latitude, $longitude);
        if (!$coordValidation['valid']) {
            $validationResult['valid'] = false;
            $validationResult['warnings'][] = $coordValidation['message'];
            $validationResult['risk_score'] += 50;
            $validationResult['user_message'] = 'GPS tidak terdeteksi dengan benar. Pastikan lokasi Anda aktif dan coba lagi.';
            $validationResult['retry_suggested'] = true;
        }

        // 2. Check if within reasonable distance from outlet - more lenient
        $distanceValidation = self::checkLocationDistance($latitude, $longitude, $outlet);
        if (!$distanceValidation['valid']) {
            $validationResult['warnings'][] = $distanceValidation['message'];
            $validationResult['risk_score'] += 20;
            $validationResult['user_message'] = 'Lokasi Anda terlalu jauh dari outlet. Pastikan Anda berada di area kerja yang ditentukan.';
            $validationResult['retry_suggested'] = false;
        }

        // 3. Basic accuracy check - more lenient threshold
        if ($accuracy !== null && $accuracy > 1000) { // Increased from 500 to 1000
            $validationResult['warnings'][] = 'GPS accuracy is low';
            $validationResult['risk_score'] += 5; // Reduced from 10 to 5
            $validationResult['user_message'] = 'Akurasi GPS rendah. Coba lagi di lokasi dengan sinyal lebih baik.';
            $validationResult['retry_suggested'] = true;
        }

        // Only reject for critical issues (increased threshold from 50 to 70)
        if ($validationResult['risk_score'] > 70) {
            $validationResult['valid'] = false;
            Log::warning('GPS Validation Failed', [
                'user_id' => $userId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'risk_score' => $validationResult['risk_score'],
                'warnings' => $validationResult['warnings']
            ]);
        }

        return $validationResult;
    }

    /**
     * Validate basic coordinate format and range.
     */
    private static function validateCoordinates($latitude, $longitude)
    {
        // Check for exact coordinates (0,0) - often indicates GPS issues
        // But be more lenient, only reject if exactly 0,0
        if ($latitude == 0 && $longitude == 0) {
            return [
                'valid' => false,
                'message' => 'GPS coordinates are invalid (0,0). Please try again or move to a different location.'
            ];
        }

        // Check for extreme coordinates that might indicate spoofing
        if ($latitude > 90 || $latitude < -90 || $longitude > 180 || $longitude < -180) {
            return [
                'valid' => false,
                'message' => 'GPS coordinates are out of valid range. Please ensure your GPS is working properly.'
            ];
        }

        // Removed excessive precision check - modern devices can provide very precise coordinates
        // This was causing false positives for legitimate high-precision GPS

        return ['valid' => true];
    }

    /**
     * Check for impossible movement speed between locations.
     */
    private static function checkMovementSpeed($latitude, $longitude, $userId)
    {
        // Get last known location for this user
        $lastAttendance = Attendance::where('user_id', $userId)
            ->whereNotNull('check_in_latitude')
            ->whereNotNull('check_in_longitude')
            ->orderBy('check_in_time', 'desc')
            ->first();

        if (!$lastAttendance) {
            return ['valid' => true]; // No previous location to compare
        }

        // Calculate distance and time difference
        $distance = self::calculateDistance(
            $lastAttendance->check_in_latitude,
            $lastAttendance->check_in_longitude,
            $latitude,
            $longitude
        );

        $timeDiff = now()->diffInSeconds($lastAttendance->check_in_time);
        
        // Skip if time difference is too small (might be duplicate check-in)
        if ($timeDiff < 30) {
            return ['valid' => true];
        }

        // Calculate speed in km/h
        $speedKmh = ($distance / 1000) / ($timeDiff / 3600);

        // Maximum realistic human movement speed: 200 km/h (fast train/plane)
        if ($speedKmh > 200) {
            return [
                'valid' => false,
                'message' => "Impossible movement speed detected: {$speedKmh} km/h"
            ];
        }

        // Warning for high speed (car travel): 120 km/h
        if ($speedKmh > 120) {
            return [
                'valid' => false,
                'message' => "Suspicious high speed movement: {$speedKmh} km/h"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check location consistency with outlet area.
     */
    private static function checkLocationConsistency($latitude, $longitude, $outlet)
    {
        $distance = self::calculateDistance(
            $latitude,
            $longitude,
            $outlet->latitude,
            $outlet->longitude
        );

        // Check if location is extremely far from outlet (beyond reasonable error)
        if ($distance > ($outlet->radius * 10)) {
            return [
                'valid' => false,
                'message' => "Location too far from outlet: " . round($distance) . "m"
            ];
        }

        // Warning for very far but still possible locations
        if ($distance > ($outlet->radius * 3)) {
            return [
                'valid' => false,
                'message' => "Location significantly far from outlet: " . round($distance) . "m"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Simplified location distance check.
     */
    private static function checkLocationDistance($latitude, $longitude, $outlet)
    {
        $distance = self::calculateDistance(
            $latitude,
            $longitude,
            $outlet->latitude,
            $outlet->longitude
        );

        // Only reject if extremely far (more than 10x radius) - more lenient
        if ($distance > ($outlet->radius * 10)) {
            return [
                'valid' => false,
                'message' => "Lokasi terlalu jauh dari outlet: " . round($distance) . "m (dalam radius {$outlet->radius}m diperlukan)"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check for GPS accuracy indicators.
     */
    private static function checkGpsAccuracy($latitude, $longitude, $accuracy = null)
    {
        // Simplified accuracy checks - only reject for obviously invalid coordinates
        $latStr = (string)$latitude;
        $lonStr = (string)$longitude;
        
        // Only reject if exactly 0,0 (obviously invalid)
        if ($latitude == 0 && $longitude == 0) {
            return [
                'valid' => false,
                'message' => 'Koordinat GPS tidak valid (0,0). Silakan coba lagi atau pindah ke lokasi yang berbeda.'
            ];
        }

        // Much more lenient accuracy checks
        if ($accuracy !== null && $accuracy > 2000) { // Increased from 500 to 2000
            return [
                'valid' => false,
                'message' => 'Akurasi GPS sangat rendah (' . round($accuracy) . "m). Coba lagi di lokasi dengan sinyal GPS lebih baik."
            ];
        }

        return ['valid' => true];
    }

    /**
     * Calculate distance between two points using Haversine formula.
     */
    private static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
               cos($latFrom) * cos($latTo) *
               sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // Distance in meters
    }
}
