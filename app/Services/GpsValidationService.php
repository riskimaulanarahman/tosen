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
            'risk_score' => 0
        ];

        // 1. Check coordinate validity
        $coordValidation = self::validateCoordinates($latitude, $longitude);
        if (!$coordValidation['valid']) {
            $validationResult['valid'] = false;
            $validationResult['warnings'][] = $coordValidation['message'];
            $validationResult['risk_score'] += 50;
        }

        // 2. Check for impossible movement speed
        $speedValidation = self::checkMovementSpeed($latitude, $longitude, $userId);
        if (!$speedValidation['valid']) {
            $validationResult['warnings'][] = $speedValidation['message'];
            $validationResult['risk_score'] += 30;
        }

        // 3. Check for location consistency
        $consistencyValidation = self::checkLocationConsistency($latitude, $longitude, $outlet);
        if (!$consistencyValidation['valid']) {
            $validationResult['warnings'][] = $consistencyValidation['message'];
            $validationResult['risk_score'] += 20;
        }

        // 4. Check for GPS accuracy indicators
        $accuracyValidation = self::checkGpsAccuracy($latitude, $longitude, $accuracy);
        if (!$accuracyValidation['valid']) {
            $validationResult['warnings'][] = $accuracyValidation['message'];
            $validationResult['risk_score'] += 15;
        }

        // Determine if location is suspicious
        if ($validationResult['risk_score'] > 40) {
            $validationResult['valid'] = false;
            Log::warning('GPS Spoofing Detected', [
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
     * Check for GPS accuracy indicators.
     */
    private static function checkGpsAccuracy($latitude, $longitude, $accuracy = null)
    {
        // Removed overly strict checks that were causing false positives
        // Modern GPS devices and services can provide very accurate coordinates
        // These checks were rejecting legitimate GPS readings

        // Only check for obviously fake coordinates (exact same pattern)
        $latStr = (string)$latitude;
        $lonStr = (string)$longitude;
        
        // Only reject if coordinates end with many zeros (like 0.0000000000)
        if (substr($latStr, -8) === '00000000' || substr($lonStr, -8) === '00000000') {
            return [
                'valid' => false,
                'message' => 'GPS coordinates appear to be rounded. Please ensure your location services are working properly.'
            ];
        }

        if ($accuracy !== null) {
            // Accuracy is measured in meters: anything higher than 500m is unreliable
            if ($accuracy > 500) {
                return [
                    'valid' => false,
                    'message' => 'GPS accuracy terlalu rendah (' . round($accuracy) . "m). Aktifkan mode akurasi tinggi sebelum melanjutkan."
                ];
            }

            if ($accuracy > 150) {
                return [
                    'valid' => false,
                    'message' => 'GPS accuracy kurang presisi (' . round($accuracy) . "m). Silakan tunggu hingga lokasi lebih akurat."
                ];
            }
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
