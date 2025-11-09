<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OfflineAttendanceService
{
    /**
     * Store attendance data for offline processing.
     */
    public static function storeOfflineAttendance($data): array
    {
        $offlineData = [
            'id' => uniqid('offline_', true),
            'user_id' => Auth::id(),
            'action' => $data['action'], // 'checkin' or 'checkout'
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'] ?? null,
            'timestamp' => $data['timestamp'] ?? now()->toISOString(),
            'outlet_id' => $data['outlet_id'],
            'device_info' => $data['device_info'] ?? [],
            'battery_level' => $data['battery_level'] ?? null,
            'network_status' => 'offline',
            'created_at' => now()->toISOString(),
        ];

        // Get existing offline data
        $existingData = self::getOfflineAttendanceData();
        $existingData[] = $offlineData;

        // Store in cache (temporary storage)
        Cache::put('offline_attendance_' . Auth::id(), $existingData, now()->addDays(7));

        Log::info('Offline attendance stored', [
            'user_id' => Auth::id(),
            'action' => $data['action'],
            'offline_id' => $offlineData['id'],
            'timestamp' => $offlineData['timestamp'],
        ]);

        return [
            'success' => true,
            'offline_id' => $offlineData['id'],
            'message' => 'Attendance data stored for offline synchronization',
            'pending_count' => count($existingData)
        ];
    }

    /**
     * Get all offline attendance data for current user.
     */
    public static function getOfflineAttendanceData(): array
    {
        return Cache::get('offline_attendance_' . Auth::id(), []);
    }

    /**
     * Sync offline attendance data to server.
     */
    public static function syncOfflineAttendance(): array
    {
        $offlineData = self::getOfflineAttendanceData();
        
        if (empty($offlineData)) {
            return [
                'success' => true,
                'message' => 'No offline data to sync',
                'synced_count' => 0,
                'failed_count' => 0,
            ];
        }

        $synced = [];
        $failed = [];
        $user = Auth::user();
        $outlet = $user->outlet;

        foreach ($offlineData as $data) {
            try {
                // Validate outlet still exists and user still assigned
                if (!$outlet || $outlet->id != $data['outlet_id']) {
                    $failed[] = [
                        'offline_id' => $data['id'],
                        'reason' => 'Outlet assignment changed',
                        'data' => $data
                    ];
                    continue;
                }

                // Check for GPS validation (optional for offline mode)
                $gpsValidation = \App\Services\GpsValidationService::validateLocation(
                    $data['latitude'],
                    $data['longitude'],
                    $data['user_id'],
                    $outlet,
                    $data['accuracy'] ?? null
                );

                if (!$gpsValidation['valid']) {
                    $failed[] = [
                        'offline_id' => $data['id'],
                        'reason' => 'GPS validation failed: ' . implode(', ', $gpsValidation['warnings']),
                        'data' => $data
                    ];
                    continue;
                }

                // Calculate distance from outlet
                $distance = self::calculateDistance(
                    $data['latitude'],
                    $data['longitude'],
                    $outlet->latitude,
                    $outlet->longitude
                );

                // Check if within geofence radius (with some tolerance for offline mode)
                $tolerance = config('attendance.offline_tolerance', 50); // 50m tolerance
                if ($distance > ($outlet->radius + $tolerance)) {
                    $failed[] = [
                        'offline_id' => $data['id'],
                        'reason' => "Too far from outlet. Distance: " . round($distance) . "m, Required: Within " . ($outlet->radius + $tolerance) . "m",
                        'data' => $data
                    ];
                    continue;
                }

                // Create attendance record
                $attendanceData = [
                    'user_id' => $data['user_id'],
                    'outlet_id' => $data['outlet_id'],
                    'check_in_latitude' => $data['latitude'],
                    'check_in_longitude' => $data['longitude'],
                    'status' => $data['action'] === 'checkin' ? 'checked_in' : 'checked_out',
                ];

                // Set appropriate timestamp
                $timestamp = Carbon::parse($data['timestamp']);
                if ($data['action'] === 'checkin') {
                    $attendanceData['check_in_time'] = $timestamp;
                    $attendanceData['check_in_date'] = $timestamp->toDateString();
                    $attendanceData['check_in_accuracy'] = $data['accuracy'];
                } else {
                    // Find existing check-in for this day
                    $existingAttendance = Attendance::where('user_id', $data['user_id'])
                        ->whereDate('check_in_time', $timestamp->toDateString())
                        ->whereNull('check_out_time')
                        ->first();
                    
                    if ($existingAttendance) {
                        $existingAttendance->update([
                            'check_out_time' => $timestamp,
                            'check_out_latitude' => $data['latitude'],
                            'check_out_longitude' => $data['longitude'],
                            'check_out_accuracy' => $data['accuracy'],
                            'status' => 'checked_out',
                        ]);
                        $attendance = $existingAttendance;
                    } else {
                        $failed[] = [
                            'offline_id' => $data['id'],
                            'reason' => 'No matching check-in found for checkout',
                            'data' => $data
                        ];
                        continue;
                    }
                }

                if ($data['action'] === 'checkin') {
                    $attendance = Attendance::create($attendanceData);
                }

                // Log successful sync
                \App\Services\AuditService::log([
                    'action' => 'offline_sync_' . $data['action'],
                    'description' => "Offline {$data['action']} synced successfully",
                    'resource_type' => 'attendance',
                    'resource_id' => $attendance->id,
                    'risk_level' => 'low',
                    'metadata' => [
                        'offline_id' => $data['id'],
                        'original_timestamp' => $data['timestamp'],
                        'sync_timestamp' => now()->toISOString(),
                        'distance_from_outlet' => $distance,
                        'device_info' => $data['device_info'],
                        'battery_level' => $data['battery_level'],
                    ]
                ]);

                $synced[] = [
                    'offline_id' => $data['id'],
                    'attendance_id' => $attendance->id,
                    'action' => $data['action'],
                    'timestamp' => $data['timestamp'],
                ];

            } catch (\Exception $e) {
                Log::error('Failed to sync offline attendance', [
                    'offline_id' => $data['id'],
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);

                $failed[] = [
                    'offline_id' => $data['id'],
                    'reason' => 'Sync error: ' . $e->getMessage(),
                    'data' => $data
                ];
            }
        }

        // Clear offline data after sync attempt
        if (!empty($synced)) {
            self::clearOfflineAttendanceData();
        }

        return [
            'success' => true,
            'message' => 'Offline attendance sync completed',
            'synced_count' => count($synced),
            'failed_count' => count($failed),
            'synced' => $synced,
            'failed' => $failed,
        ];
    }

    /**
     * Clear offline attendance data.
     */
    public static function clearOfflineAttendanceData(): bool
    {
        Cache::forget('offline_attendance_' . Auth::id());
        return true;
    }

    /**
     * Get offline attendance statistics.
     */
    public static function getOfflineStats(): array
    {
        $offlineData = self::getOfflineAttendanceData();
        
        $checkinCount = 0;
        $checkoutCount = 0;
        $oldestRecord = null;
        $newestRecord = null;

        foreach ($offlineData as $data) {
            if ($data['action'] === 'checkin') {
                $checkinCount++;
            } else {
                $checkoutCount++;
            }

            $timestamp = Carbon::parse($data['timestamp']);
            if (!$oldestRecord || $timestamp < $oldestRecord) {
                $oldestRecord = $timestamp;
            }
            if (!$newestRecord || $timestamp > $newestRecord) {
                $newestRecord = $timestamp;
            }
        }

        return [
            'total_count' => count($offlineData),
            'checkin_count' => $checkinCount,
            'checkout_count' => $checkoutCount,
            'oldest_record' => $oldestRecord ? $oldestRecord->toISOString() : null,
            'newest_record' => $newestRecord ? $newestRecord->toISOString() : null,
            'storage_days_remaining' => self::getStorageDaysRemaining(),
        ];
    }

    /**
     * Check if offline mode is available.
     */
    public static function isOfflineModeAvailable(): bool
    {
        return config('attendance.offline_mode_enabled', true);
    }

    /**
     * Get storage days remaining for offline data.
     */
    private static function getStorageDaysRemaining(): int
    {
        $lastActivity = Cache::get('offline_attendance_last_activity_' . Auth::id());
        if (!$lastActivity) {
            return 7; // 7 days default
        }

        $daysPassed = now()->diffInDays(Carbon::parse($lastActivity));
        return max(0, 7 - $daysPassed);
    }

    /**
     * Calculate distance between two points using Haversine formula.
     */
    private static function calculateDistance($lat1, $lon1, $lat2, $lon2): float
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

    /**
     * Update last activity timestamp.
     */
    public static function updateLastActivity(): void
    {
        Cache::put('offline_attendance_last_activity_' . Auth::id(), now()->toISOString(), now()->addDays(7));
    }
}
