<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCheckinRequest;
use App\Models\Attendance;
use App\Services\GpsValidationService;
use App\Services\AuditService;
use App\Services\OfflineAttendanceService;
use App\Services\ImageOptimizationService;
use App\Services\AttendanceCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    /**
     * Display attendance page for employee.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get attendance history for employee
        $attendances = Attendance::where('user_id', $user->id)
            ->with('outlet')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get today's attendance status
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->with('outlet')
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->first();

        // Get attendance statistics
        $stats = [
            'total_attendances' => Attendance::where('user_id', $user->id)->count(),
            'this_month' => Attendance::where('user_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'on_time' => Attendance::where('user_id', $user->id)
                ->whereNotNull('check_in_time')
                ->whereTime('check_in_time', '<=', '09:00:00')
                ->count(),
        ];

        return Inertia::render('Attendance/Index', [
            'attendances' => $attendances,
            'todayAttendance' => $todayAttendance,
            'stats' => $stats,
            'outlet' => $user->outlet,
        ]);
    }

    /**
     * Calculate distance between two points using Haversine formula.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
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
     * Handle check-in request.
     */
    public function checkin(AttendanceCheckinRequest $request)
    {
        $user = Auth::user();
        $outlet = $user->outlet;

        if (!$outlet) {
            return response()->json([
                'message' => 'You are not assigned to any outlet.',
                'success' => false,
            ], 400);
        }

        // Use database transaction to prevent race conditions
        return DB::transaction(function () use ($request, $user, $outlet) {
            // Check for existing check-in today with pessimistic locking
            $existingAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('check_in_time', Carbon::today())
                ->whereNull('check_out_time')
                ->lockForUpdate()
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'message' => 'You already have an active check-in today.',
                    'success' => false,
                    'attendance' => $existingAttendance->load('user', 'outlet'),
                ], 400);
            }

            // GPS Validation for spoofing detection
            $gpsValidation = GpsValidationService::validateLocation(
                $request->latitude,
                $request->longitude,
                $user->id,
                $outlet,
                $request->accuracy
            );

            if (!$gpsValidation['valid']) {
                // Log GPS validation failure
                AuditService::logGpsSpoofing(
                    $user->id,
                    $request->latitude,
                    $request->longitude,
                    $gpsValidation['warnings'],
                    $gpsValidation['risk_score']
                );

                return response()->json([
                    'message' => $gpsValidation['user_message'] ?: 'Tidak dapat melakukan check-in. Silakan coba lagi.',
                    'success' => false,
                    'retry_suggested' => $gpsValidation['retry_suggested'] ?? false,
                    'error_type' => 'gps_validation',
                    'warnings' => $gpsValidation['warnings'],
                ], 400);
            }

            // Calculate distance from outlet
            $distance = $this->calculateDistance(
                $request->latitude,
                $request->longitude,
                $outlet->latitude,
                $outlet->longitude
            );

        // Check if within geofence radius - more lenient check
        if ($distance > ($outlet->radius * 1.5)) { // Allow 50% tolerance
            return response()->json([
                'message' => "Anda terlalu jauh dari outlet. Jarak: " . round($distance) . "m, Diperlukan: Dalam radius {$outlet->radius}m",
                'success' => false,
                'error_type' => 'distance',
                'distance' => round($distance),
                'required_distance' => $outlet->radius,
                'retry_suggested' => false,
            ], 400);
        }

        // Check if outlet is currently operational - simplified message
        if (!$outlet->isCurrentlyOperational()) {
            $nextTime = $outlet->getNextOperationalTime();
            $message = 'Check-in hanya diperbolehkan dalam jam operasional outlet: ' . $outlet->formatted_operational_hours;
            
            if ($nextTime) {
                $message .= '. Check-in dapat dilakukan kembali pukul ' . $nextTime->format('H:i');
            }
            
            return response()->json([
                'message' => $message,
                'success' => false,
                'error_type' => 'operational_hours',
                'current_status' => $outlet->operational_status,
                'operational_hours' => $outlet->formatted_operational_hours,
                'next_operational_time' => $nextTime?->format('H:i'),
                'retry_suggested' => false,
            ], 400);
        }

            try {
                // Process selfie upload
                $selfieData = null;
                if ($request->hasFile('selfie')) {
                    // Validate selfie before processing
                    $validation = ImageOptimizationService::validateSelfie($request->file('selfie'));
                    if (!$validation['valid']) {
                        return response()->json([
                            'message' => 'Foto selfie tidak valid: ' . implode(', ', $validation['errors']),
                            'success' => false,
                            'error_type' => 'selfie_validation',
                            'errors' => $validation['errors'],
                            'retry_suggested' => true,
                        ], 400);
                    }

                    // Store optimized selfie
                    try {
                        $selfieData = ImageOptimizationService::storeSelfie($request->file('selfie'), 'checkin');
                    } catch (\Exception $e) {
                        \Log::error('Selfie upload failed: ' . $e->getMessage());
                        return response()->json([
                            'message' => 'Gagal memproses foto. Silakan coba lagi.',
                            'success' => false,
                            'error_type' => 'selfie_upload',
                            'retry_suggested' => true,
                        ], 500);
                    }
                }

                // Create attendance record with selfie data
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'outlet_id' => $outlet->id,
                    'check_in_time' => now(),
                    'check_in_date' => now()->toDateString(),
                    'check_in_latitude' => $request->latitude,
                    'check_in_longitude' => $request->longitude,
                    'check_in_accuracy' => $request->accuracy,
                    'status' => 'checked_in',
                    'check_in_selfie_path' => $selfieData['path'] ?? null,
                    'check_in_thumbnail_path' => $selfieData['thumbnail_path'] ?? null,
                    'check_in_file_size' => $selfieData['file_size'] ?? null,
                    'has_check_in_selfie' => !empty($selfieData),
                ]);

                // Calculate attendance metrics
                $attendance->calculateAndUpdateMetrics();

                // Log successful check-in with selfie info
                AuditService::logCheckin($attendance, $distance, [
                    'has_selfie' => !empty($selfieData),
                    'selfie_file_size' => $selfieData['file_size'] ?? null,
                    'attendance_status' => $attendance->attendance_status,
                    'attendance_score' => $attendance->attendance_score
                ]);

                return response()->json([
                    'message' => 'Check-in successful!',
                    'success' => true,
                    'attendance' => $attendance->load('user', 'outlet'),
                    'distance' => round($distance),
                    'outlet_name' => $outlet->name,
                    'selfie' => [
                        'url' => $selfieData['url'] ?? null,
                        'thumbnail_url' => $selfieData['thumbnail_url'] ?? null,
                        'file_size' => $selfieData['file_size'] ?? null,
                    ],
                    'metrics' => [
                        'status' => $attendance->attendance_status,
                        'score' => $attendance->attendance_score,
                        'late_minutes' => $attendance->late_minutes,
                    ]
                ]);

            } catch (\Illuminate\Database\QueryException $e) {
                // Handle unique constraint violation
                if ($e->getCode() == 23000) {
                    return response()->json([
                        'message' => 'You have already checked in today. Please check out first before checking in again.',
                        'success' => false,
                    ], 400);
                }
                throw $e;
            }
        });
    }

    /**
     * Handle check-out request.
     */
    public function checkout(Request $request)
    {
        $user = Auth::user();
        $outlet = $user->outlet;

        if (!$outlet) {
            return response()->json([
                'message' => 'Anda belum ditugaskan ke outlet mana pun. Silakan hubungi administrator.',
                'success' => false,
            ], 400);
        }

        // Find active check-in
        $attendance = Attendance::where('user_id', $user->id)
            ->whereNull('check_out_time')
            ->first();

        if (!$attendance) {
            return response()->json([
                'message' => 'Tidak ada check-in aktif yang ditemukan. Silakan lakukan check-in terlebih dahulu.',
                'success' => false
            ], 400);
        }

        // Calculate work duration and overtime
        $checkinTime = $attendance->check_in_time;
        $checkoutTime = now();
        $workDurationMinutes = $checkinTime->diffInMinutes($checkoutTime);
        $overtimeMinutes = $outlet->calculateOvertime($checkoutTime);

        // Determine if remarks are required
        $requiresEarlyCheckoutRemarks = $outlet->requiresEarlyCheckoutRemarks($workDurationMinutes);
        $requiresOvertimeRemarks = $outlet->requiresOvertimeRemarks($overtimeMinutes);

        // Build dynamic validation rules
        $rules = [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'selfie' => 'nullable|file|image|mimes:jpeg,jpg,png|max:2048|dimensions:min_width=300,min_height=300',
        ];

        // Add remarks validation if required
        if ($requiresEarlyCheckoutRemarks || $requiresOvertimeRemarks) {
            if ($requiresEarlyCheckoutRemarks) {
                $rulesConfig = $outlet->getEarlyCheckoutRemarksRules();
                $remarksLabel = 'justifikasi early checkout';
            } else {
                $rulesConfig = $outlet->getOvertimeRemarksRules();
                $remarksLabel = 'catatan overtime';
            }

            $rules['checkout_remarks'] = [
                'required',
                'string',
                'min:' . $rulesConfig['min'],
                'max:' . $rulesConfig['max']
            ];

            // Custom error messages
            $request->validate($rules, [
                'checkout_remarks.required' => "{$remarksLabel} wajib diisi",
                'checkout_remarks.min' => "{$remarksLabel} minimal {$rulesConfig['min']} karakter",
                'checkout_remarks.max' => "{$remarksLabel} maksimal {$rulesConfig['max']} karakter"
            ]);
        } else {
            $request->validate($rules);
        }

        // Calculate distance from outlet for checkout
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $outlet->latitude,
            $outlet->longitude
        );

        // Optional GPS validation for checkout accuracy
        $gpsValidation = GpsValidationService::validateLocation(
            $request->latitude,
            $request->longitude,
            $user->id,
            $outlet,
            $request->accuracy
        );

        if (!$gpsValidation['valid']) {
            return response()->json([
                'message' => $gpsValidation['user_message'] ?: 'Tidak dapat melakukan check-out. Silakan coba lagi.',
                'success' => false,
                'error_type' => 'gps_validation',
                'retry_suggested' => $gpsValidation['retry_suggested'] ?? false,
                'warnings' => $gpsValidation['warnings'],
            ], 400);
        }

        // Check if within geofence radius for checkout - more lenient
        if ($distance > ($outlet->radius * 1.5)) { // Allow 50% tolerance
            return response()->json([
                'message' => "Anda terlalu jauh dari outlet untuk check-out. Jarak: " . round($distance) . "m, Diperlukan: Dalam radius {$outlet->radius}m",
                'success' => false,
                'error_type' => 'distance',
                'distance' => round($distance),
                'required_distance' => $outlet->radius,
                'retry_suggested' => false,
            ], 400);
        }

        // Process checkout selfie if provided
        $checkoutSelfieData = null;
        if ($request->hasFile('selfie')) {
            // Validate checkout selfie
            $validation = ImageOptimizationService::validateSelfie($request->file('selfie'));
            if (!$validation['valid']) {
                return response()->json([
                    'message' => 'Foto selfie tidak valid: ' . implode(', ', $validation['errors']),
                    'success' => false,
                    'error_type' => 'selfie_validation',
                    'errors' => $validation['errors'],
                    'retry_suggested' => true,
                ], 400);
            }

            // Store optimized checkout selfie
            try {
                $checkoutSelfieData = ImageOptimizationService::storeSelfie($request->file('selfie'), 'checkout');
            } catch (\Exception $e) {
                \Log::error('Checkout selfie upload failed: ' . $e->getMessage());
                return response()->json([
                    'message' => 'Gagal memproses foto check-out. Silakan coba lagi.',
                    'success' => false,
                    'error_type' => 'selfie_upload',
                    'retry_suggested' => true,
                ], 500);
            }
        }

        // Update checkout time, location, selfie data, and remarks
        $attendance->update([
            'check_out_time' => now(),
            'check_out_latitude' => $request->latitude,
            'check_out_longitude' => $request->longitude,
            'check_out_accuracy' => $request->accuracy,
            'status' => 'checked_out',
            'check_out_selfie_path' => $checkoutSelfieData['path'] ?? null,
            'check_out_thumbnail_path' => $checkoutSelfieData['thumbnail_path'] ?? null,
            'check_out_file_size' => $checkoutSelfieData['file_size'] ?? null,
            'has_check_out_selfie' => !empty($checkoutSelfieData),
            'checkout_remarks' => $request->checkout_remarks,
            'is_overtime' => $overtimeMinutes > 0,
            'overtime_minutes' => $overtimeMinutes,
        ]);

        // Calculate work duration
        $checkinTime = $attendance->check_in_time;
        $checkoutTime = now();
        $duration = $checkinTime->diff($checkoutTime);

        // Recalculate attendance metrics after checkout
        $attendance->calculateAndUpdateMetrics();

        // Log successful check-out with selfie info
        AuditService::logCheckout($attendance, $distance, [
            'hours' => $duration->h,
            'minutes' => $duration->i,
            'formatted' => $duration->format('%H:%I'),
            'has_checkout_selfie' => !empty($checkoutSelfieData),
            'checkout_selfie_file_size' => $checkoutSelfieData['file_size'] ?? null,
            'final_attendance_status' => $attendance->attendance_status,
            'final_attendance_score' => $attendance->attendance_score,
            'work_duration_minutes' => $attendance->work_duration_minutes,
            'overtime_minutes' => $attendance->overtime_minutes,
        ]);

        return response()->json([
            'message' => 'Check-out successful!',
            'success' => true,
            'attendance' => $attendance->load('user', 'outlet'),
            'work_duration' => [
                'hours' => $duration->h,
                'minutes' => $duration->i,
                'formatted' => $duration->format('%H:%I'),
                'total_minutes' => $attendance->work_duration_minutes,
            ],
            'distance' => round($distance),
            'outlet_name' => $outlet->name,
            'selfie' => $checkoutSelfieData ? [
                'url' => $checkoutSelfieData['url'],
                'thumbnail_url' => $checkoutSelfieData['thumbnail_url'],
                'file_size' => $checkoutSelfieData['file_size'],
            ] : null,
            'metrics' => [
                'status' => $attendance->attendance_status,
                'score' => $attendance->attendance_score,
                'late_minutes' => $attendance->late_minutes,
                'early_checkout_minutes' => $attendance->early_checkout_minutes,
                'overtime_minutes' => $attendance->overtime_minutes,
            ]
        ]);
    }

    /**
     * Get today's attendance status for the authenticated user.
     */
    public function status()
    {
        \Log::info('Attendance status endpoint called');
        $user = Auth::user();
        \Log::info('User authenticated: ' . $user->id);
        
        try {
            $todayAttendance = Attendance::where('user_id', $user->id)
                ->with('outlet')
                ->whereDate('created_at', today())
                ->orderBy('created_at', 'desc')
                ->first();

            $recentAttendances = Attendance::where('user_id', $user->id)
                ->with('outlet')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            // Get user's outlet with operational status
            $outlet = $user->outlet;
            \Log::info('Outlet retrieved: ' . ($outlet ? $outlet->id : 'null'));
            
            // Add operational status to outlet data
            if ($outlet) {
                $outlet->append([
                    'operational_start_time_formatted',
                    'operational_end_time_formatted',
                    'formatted_operational_hours',
                    'operational_status',
                    'formatted_work_days'
                ]);
                
                // Calculate current overtime for checkout validation
                $currentOvertimeMinutes = $outlet->calculateOvertime(now());
                $outlet->current_overtime_minutes = $currentOvertimeMinutes;
            }

            $response = [
                'today_attendance' => $todayAttendance,
                'recent_attendances' => $recentAttendances,
                'outlet' => $outlet
            ];
            
            \Log::info('Returning response', $response);
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('Error in attendance status: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to fetch attendance status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store offline attendance data.
     */
    public function storeOffline(Request $request)
    {
        $request->validate([
            'action' => 'required|in:checkin,checkout',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'outlet_id' => 'required|integer|exists:outlets,id',
            'timestamp' => 'nullable|string',
            'device_info' => 'nullable|array',
            'battery_level' => 'nullable|integer|between:0,100',
        ]);

        if (!OfflineAttendanceService::isOfflineModeAvailable()) {
            return response()->json([
                'message' => 'Offline mode is not available',
                'success' => false,
            ], 403);
        }

        $result = OfflineAttendanceService::storeOfflineAttendance($request->all());
        
        return response()->json($result);
    }

    /**
     * Sync offline attendance data.
     */
    public function syncOffline()
    {
        if (!OfflineAttendanceService::isOfflineModeAvailable()) {
            return response()->json([
                'message' => 'Offline mode is not available',
                'success' => false,
            ], 403);
        }

        $result = OfflineAttendanceService::syncOfflineAttendance();
        
        return response()->json($result);
    }

    /**
     * Get offline attendance statistics.
     */
    public function offlineStats()
    {
        if (!OfflineAttendanceService::isOfflineModeAvailable()) {
            return response()->json([
                'message' => 'Offline mode is not available',
                'success' => false,
            ], 403);
        }

        $stats = OfflineAttendanceService::getOfflineStats();
        
        return response()->json([
            'success' => true,
            'stats' => $stats,
            'offline_mode_available' => true,
        ]);
    }

    /**
     * Clear offline attendance data.
     */
    public function clearOffline()
    {
        if (!OfflineAttendanceService::isOfflineModeAvailable()) {
            return response()->json([
                'message' => 'Offline mode is not available',
                'success' => false,
            ], 403);
        }

        $result = OfflineAttendanceService::clearOfflineAttendanceData();
        
        AuditService::log([
            'action' => 'offline_data_cleared',
            'description' => 'User cleared offline attendance data',
            'resource_type' => 'attendance',
            'resource_id' => null,
            'risk_level' => 'low',
            'metadata' => [
                'cleared_at' => now()->toISOString(),
                'user_id' => Auth::id(),
            ]
        ]);
        
        return response()->json([
            'success' => $result,
            'message' => 'Offline attendance data cleared successfully',
        ]);
    }
}
