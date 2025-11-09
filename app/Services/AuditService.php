<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an audit event.
     */
    public static function log(array $data): AuditLog
    {
        $auditData = array_merge([
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'risk_level' => 'low',
            'metadata' => [],
        ], $data);

        return AuditLog::create($auditData);
    }

    /**
     * Log successful login.
     */
    public static function logLogin($user): AuditLog
    {
        return self::log([
            'action' => 'login',
            'description' => "User {$user->email} logged in successfully",
            'resource_type' => 'user',
            'resource_id' => $user->id,
            'risk_level' => 'low',
            'metadata' => [
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }

    /**
     * Log failed login attempt.
     */
    public static function logFailedLogin($email, $reason = null): AuditLog
    {
        return self::log([
            'user_id' => null,
            'action' => 'login_failed',
            'description' => "Failed login attempt for email: {$email}" . ($reason ? " ({$reason})" : ""),
            'resource_type' => 'user',
            'resource_id' => null,
            'risk_level' => 'medium',
            'metadata' => [
                'email' => $email,
                'reason' => $reason,
                'failed_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Log attendance check-in.
     */
    public static function logCheckin($attendance, $distance = null): AuditLog
    {
        $riskLevel = 'low';
        if ($distance > 100) {
            $riskLevel = 'medium';
        }

        return self::log([
            'action' => 'checkin',
            'description' => "User checked in at {$attendance->outlet->name}",
            'resource_type' => 'attendance',
            'resource_id' => $attendance->id,
            'risk_level' => $riskLevel,
            'new_values' => [
                'check_in_time' => $attendance->check_in_time,
                'check_in_latitude' => $attendance->check_in_latitude,
                'check_in_longitude' => $attendance->check_in_longitude,
                'outlet_id' => $attendance->outlet_id,
            ],
            'metadata' => [
                'distance_from_outlet' => $distance,
                'outlet_name' => $attendance->outlet->name,
                'checkin_time' => $attendance->check_in_time->toISOString(),
            ]
        ]);
    }

    /**
     * Log attendance check-out.
     */
    public static function logCheckout($attendance, $distance = null, $duration = null): AuditLog
    {
        return self::log([
            'action' => 'checkout',
            'description' => "User checked out from {$attendance->outlet->name}",
            'resource_type' => 'attendance',
            'resource_id' => $attendance->id,
            'risk_level' => 'low',
            'new_values' => [
                'check_out_time' => $attendance->check_out_time,
                'check_out_latitude' => $attendance->check_out_latitude,
                'check_out_longitude' => $attendance->check_out_longitude,
            ],
            'metadata' => [
                'distance_from_outlet' => $distance,
                'work_duration' => $duration,
                'outlet_name' => $attendance->outlet->name,
                'checkout_time' => $attendance->check_out_time->toISOString(),
            ]
        ]);
    }

    /**
     * Log GPS spoofing detection.
     */
    public static function logGpsSpoofing($userId, $latitude, $longitude, $warnings, $riskScore): AuditLog
    {
        return self::log([
            'action' => 'gps_spoofing_detected',
            'description' => "GPS spoofing detected with risk score: {$riskScore}",
            'resource_type' => 'attendance',
            'resource_id' => null,
            'risk_level' => $riskScore > 60 ? 'critical' : 'high',
            'metadata' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'warnings' => $warnings,
                'risk_score' => $riskScore,
                'detected_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Log employee creation.
     */
    public static function logEmployeeCreated($employee, $creator = null): AuditLog
    {
        return self::log([
            'action' => 'employee_created',
            'description' => "New employee {$employee->name} was created",
            'resource_type' => 'user',
            'resource_id' => $employee->id,
            'risk_level' => 'medium',
            'new_values' => [
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => $employee->role,
                'outlet_id' => $employee->outlet_id,
            ],
            'metadata' => [
                'creator_id' => $creator?->id,
                'created_at' => $employee->created_at->toISOString(),
            ]
        ]);
    }

    /**
     * Log employee update.
     */
    public static function logEmployeeUpdated($employee, $oldValues, $newValues): AuditLog
    {
        return self::log([
            'action' => 'employee_updated',
            'description' => "Employee {$employee->name} was updated",
            'resource_type' => 'user',
            'resource_id' => $employee->id,
            'risk_level' => 'medium',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'updated_fields' => array_keys(array_diff_assoc($newValues, $oldValues)),
                'updated_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Log employee deletion.
     */
    public static function logEmployeeDeleted($employee): AuditLog
    {
        return self::log([
            'action' => 'employee_deleted',
            'description' => "Employee {$employee->name} was deleted",
            'resource_type' => 'user',
            'resource_id' => $employee->id,
            'risk_level' => 'high',
            'old_values' => [
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => $employee->role,
                'outlet_id' => $employee->outlet_id,
            ],
            'metadata' => [
                'deleted_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Log outlet creation.
     */
    public static function logOutletCreated($outlet): AuditLog
    {
        return self::log([
            'action' => 'outlet_created',
            'description' => "New outlet {$outlet->name} was created",
            'resource_type' => 'outlet',
            'resource_id' => $outlet->id,
            'risk_level' => 'low',
            'new_values' => [
                'name' => $outlet->name,
                'latitude' => $outlet->latitude,
                'longitude' => $outlet->longitude,
                'radius' => $outlet->radius,
            ],
            'metadata' => [
                'created_at' => $outlet->created_at->toISOString(),
            ]
        ]);
    }

    /**
     * Log OTP verification request.
     */
    public static function logOtpRequested($email): AuditLog
    {
        return self::log([
            'user_id' => null,
            'action' => 'otp_requested',
            'description' => "OTP verification requested for {$email}",
            'resource_type' => 'email_verification',
            'resource_id' => null,
            'risk_level' => 'medium',
            'metadata' => [
                'email' => $email,
                'requested_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Log OTP verification success.
     */
    public static function logOtpVerified($email): AuditLog
    {
        return self::log([
            'action' => 'otp_verified',
            'description' => "OTP verification successful for {$email}",
            'resource_type' => 'email_verification',
            'resource_id' => null,
            'risk_level' => 'low',
            'metadata' => [
                'email' => $email,
                'verified_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Log security-related events.
     */
    public static function logSecurityEvent($event, $description, $riskLevel = 'high', $metadata = []): AuditLog
    {
        return self::log([
            'action' => $event,
            'description' => $description,
            'resource_type' => 'security',
            'resource_id' => null,
            'risk_level' => $riskLevel,
            'metadata' => array_merge($metadata, [
                'event_time' => now()->toISOString(),
            ])
        ]);
    }

    /**
     * Get recent high-risk activities.
     */
    public static function getRecentHighRiskActivities(int $days = 7): array
    {
        return AuditLog::highRisk()
            ->recent($days)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('risk_level')
            ->toArray();
    }
}
