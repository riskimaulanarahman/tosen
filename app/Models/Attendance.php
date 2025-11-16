<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'outlet_id',
        'check_in_time',
        'check_out_time',
        'check_in_date',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'check_in_accuracy',
        'check_out_accuracy',
        'status',
        'notes',
        'check_in_selfie_path',
        'check_out_selfie_path',
        'checkout_remarks',
        'is_overtime',
        'check_in_thumbnail_path',
        'check_out_thumbnail_path',
        'has_check_in_selfie',
        'has_check_out_selfie',
        'attendance_status',
        'late_minutes',
        'early_checkout_minutes',
        'overtime_minutes',
        'work_duration_minutes',
        'attendance_score',
        'is_paid_leave',
        'leave_reason',
        'computed_at',
        'selfie_deleted_at',
        'check_in_file_size',
        'check_out_file_size',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'check_in_latitude' => 'decimal:6',
            'check_in_longitude' => 'decimal:6',
            'check_out_latitude' => 'decimal:6',
            'check_out_longitude' => 'decimal:6',
            'check_in_accuracy' => 'decimal:2',
            'check_out_accuracy' => 'decimal:2',
            'late_minutes' => 'integer',
            'early_checkout_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'work_duration_minutes' => 'integer',
            'attendance_score' => 'decimal:2',
            'is_paid_leave' => 'boolean',
            'has_check_in_selfie' => 'boolean',
            'has_check_out_selfie' => 'boolean',
            'computed_at' => 'datetime',
            'selfie_deleted_at' => 'datetime',
            'check_in_file_size' => 'integer',
            'check_out_file_size' => 'integer',
        ];
    }

    /**
     * Get the user that owns the attendance.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the outlet that owns the attendance.
     */
    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Check if attendance is checked out.
     */
    public function isCheckedOut()
    {
        return !is_null($this->check_out_time);
    }

    /**
     * Calculate duration in seconds.
     */
    public function getDurationInSeconds()
    {
        if (!$this->isCheckedOut()) {
            return null;
        }

        return $this->check_out_time->diffInSeconds($this->check_in_time);
    }

    /**
     * Calculate duration in human readable format.
     */
    public function getDuration()
    {
        if (!$this->isCheckedOut()) {
            return 'Still checked in';
        }

        $seconds = $this->getDurationInSeconds();
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return "{$hours}h {$minutes}m";
    }

    /**
     * Get check-in selfie URL.
     */
    public function getCheckInSelfieUrlAttribute()
    {
        if (!$this->check_in_selfie_path) {
            return null;
        }

        return url('storage/' . $this->check_in_selfie_path);
    }

    /**
     * Get check-out selfie URL.
     */
    public function getCheckOutSelfieUrlAttribute()
    {
        if (!$this->check_out_selfie_path) {
            return null;
        }

        return url('storage/' . $this->check_out_selfie_path);
    }

    /**
     * Get check-in thumbnail URL.
     */
    public function getCheckInThumbnailUrlAttribute()
    {
        if (!$this->check_in_thumbnail_path) {
            return null;
        }

        return url('storage/' . $this->check_in_thumbnail_path);
    }

    /**
     * Get check-out thumbnail URL.
     */
    public function getCheckOutThumbnailUrlAttribute()
    {
        if (!$this->check_out_thumbnail_path) {
            return null;
        }

        return url('storage/' . $this->check_out_thumbnail_path);
    }

    /**
     * Get formatted attendance status with badge color.
     */
    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'on_time' => ['text' => 'On Time', 'color' => 'success'],
            'late' => ['text' => 'Late', 'color' => 'warning'],
            'early_checkout' => ['text' => 'Early Checkout', 'color' => 'warning'],
            'overtime' => ['text' => 'Overtime', 'color' => 'info'],
            'absent' => ['text' => 'Absent', 'color' => 'danger'],
            'holiday' => ['text' => 'Holiday', 'color' => 'secondary'],
            'leave' => ['text' => 'Leave', 'color' => 'primary'],
        ];

        return $statuses[$this->attendance_status] ?? ['text' => 'Unknown', 'color' => 'secondary'];
    }

    /**
     * Calculate and update attendance metrics.
     */
    public function calculateAndUpdateMetrics()
    {
        $calculation = \App\Services\AttendanceCalculationService::calculateAttendanceStatus($this);
        
        $this->update([
            'attendance_status' => $calculation['attendance_status'],
            'late_minutes' => $calculation['late_minutes'],
            'early_checkout_minutes' => $calculation['early_checkout_minutes'],
            'overtime_minutes' => $calculation['overtime_minutes'],
            'work_duration_minutes' => $calculation['work_duration_minutes'],
            'attendance_score' => $calculation['attendance_score'],
            'computed_at' => now(),
        ]);

        return $calculation;
    }

    /**
     * Check if user has valid selfie for check-in.
     */
    public function hasValidCheckInSelfie(): bool
    {
        return !empty($this->check_in_selfie_path) && 
               !empty($this->check_in_thumbnail_path) &&
               $this->check_in_file_size > 0;
    }

    /**
     * Check if user has valid selfie for check-out.
     */
    public function hasValidCheckOutSelfie(): bool
    {
        return !empty($this->check_out_selfie_path) && 
               !empty($this->check_out_thumbnail_path) &&
               $this->check_out_file_size > 0;
    }

    /**
     * Get selfie file size in human readable format.
     */
    public function getCheckInFileSizeFormattedAttribute()
    {
        if (!$this->check_in_file_size) {
            return 'N/A';
        }

        $bytes = $this->check_in_file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get check-out file size in human readable format.
     */
    public function getCheckOutFileSizeFormattedAttribute()
    {
        if (!$this->check_out_file_size) {
            return 'N/A';
        }

        $bytes = $this->check_out_file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope to get attendances with selfies.
     */
    public function scopeWithSelfies($query)
    {
        return $query->whereNotNull('check_in_selfie_path');
    }

    /**
     * Scope to get attendances without selfies.
     */
    public function scopeWithoutSelfies($query)
    {
        return $query->whereNull('check_in_selfie_path');
    }

    /**
     * Scope to get attendances by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('attendance_status', $status);
    }

    /**
     * Scope to get attendances in date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('check_in_time', [$startDate, $endDate]);
    }

    /**
     * Get selfie deletion status.
     */
    public function getSelfieDeletionStatusAttribute()
    {
        if (!$this->selfie_deleted_at) {
            return ['status' => 'active', 'text' => 'Active', 'color' => 'success'];
        }

        return [
            'status' => 'deleted', 
            'text' => 'Deleted on ' . $this->selfie_deleted_at->format('Y-m-d H:i'), 
            'color' => 'danger'
        ];
    }
}
