<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Outlet;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;

class PivotTableService
{
    /**
     * Generate pivot table data for attendance report
     */
    public function generatePivotTable($ownerId, $startDate, $endDate, $outletId = null)
    {
        // Generate cache key
        $cacheKey = "pivot_table_{$ownerId}_{$startDate}_{$endDate}_" . ($outletId ?? 'all');
        
        return Cache::remember($cacheKey, 300, function () use ($ownerId, $startDate, $endDate, $outletId) {
            // Get date range
            $datePeriod = CarbonPeriod::create($startDate, $endDate);
            $dates = [];
            foreach ($datePeriod as $date) {
                $dates[] = $date->format('Y-m-d');
            }

            // Get employees
            $employeesQuery = User::where('role', 'employee')
                ->whereHas('outlet', function($query) use ($ownerId) {
                    $query->where('owner_id', $ownerId);
                });

            if ($outletId) {
                $employeesQuery->where('outlet_id', $outletId);
            }

            $employees = $employeesQuery->with(['outlet'])->get();

            // Get attendances for all employees in date range
            $attendances = Attendance::with(['user', 'outlet'])
                ->whereHas('user', function($query) use ($ownerId, $outletId) {
                    $query->where('role', 'employee')
                        ->whereHas('outlet', function($subQuery) use ($ownerId) {
                            $subQuery->where('owner_id', $ownerId);
                        });
                    
                    if ($outletId) {
                        $query->where('outlet_id', $outletId);
                    }
                })
                ->whereBetween('check_in_date', [$startDate, $endDate])
                ->get();

            // Transform to pivot format
            $pivotData = [];
            $summaryStats = [
                'on_time' => 0,
                'late' => 0,
                'early_checkout' => 0,
                'overtime' => 0,
                'absent' => 0,
                'holiday' => 0,
                'leave' => 0,
            ];

            foreach ($employees as $employee) {
                $employeeAttendances = $attendances->where('user_id', $employee->id);
                $attendanceMap = [];

                foreach ($dates as $date) {
                    $attendance = $employeeAttendances->firstWhere('check_in_date', $date);
                    
                    if ($attendance) {
                        $attendanceMap[$date] = [
                            'status' => $attendance->attendance_status ?? 'unknown',
                            'check_in_time' => $attendance->check_in_time?->format('H:i'),
                            'check_out_time' => $attendance->check_out_time?->format('H:i'),
                            'work_duration' => $attendance->getDuration(),
                            'late_minutes' => $attendance->late_minutes ?? 0,
                            'early_checkout_minutes' => $attendance->early_checkout_minutes ?? 0,
                            'overtime_minutes' => $attendance->overtime_minutes ?? 0,
                            'attendance_id' => $attendance->id,
                        ];
                        
                        // Update summary stats
                        $status = $attendance->attendance_status ?? 'unknown';
                        if (isset($summaryStats[$status])) {
                            $summaryStats[$status]++;
                        }
                    } else {
                        // Check if it's a weekend or holiday
                        $dateObj = Carbon::parse($date);
                        if ($dateObj->isWeekend()) {
                            $attendanceMap[$date] = [
                                'status' => 'weekend',
                                'check_in_time' => null,
                                'check_out_time' => null,
                                'work_duration' => null,
                                'late_minutes' => 0,
                                'early_checkout_minutes' => 0,
                                'overtime_minutes' => 0,
                                'attendance_id' => null,
                            ];
                        } else {
                            $attendanceMap[$date] = [
                                'status' => 'absent',
                                'check_in_time' => null,
                                'check_out_time' => null,
                                'work_duration' => null,
                                'late_minutes' => 0,
                                'early_checkout_minutes' => 0,
                                'overtime_minutes' => 0,
                                'attendance_id' => null,
                            ];
                            $summaryStats['absent']++;
                        }
                    }
                }

                $pivotData[] = [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'outlet' => $employee->outlet?->name,
                    'attendances' => $attendanceMap,
                ];
            }

            return [
                'employees' => $pivotData,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'dates' => $dates,
                    'total_days' => count($dates),
                ],
                'summary' => [
                    'total_employees' => count($employees),
                    'attendance_summary' => $summaryStats,
                ],
            ];
        });
    }

    /**
     * Get status configuration for UI
     */
    public function getStatusConfig()
    {
        return [
            'on_time' => [
                'text' => 'Hadir',
                'color' => '#10b981', // green
                'bg_color' => '#d1fae5',
                'icon' => '✓',
            ],
            'late' => [
                'text' => 'Terlambat',
                'color' => '#f59e0b', // amber
                'bg_color' => '#fef3c7',
                'icon' => '⏰',
            ],
            'early_checkout' => [
                'text' => 'Pulang Awal',
                'color' => '#f59e0b', // amber
                'bg_color' => '#fef3c7',
                'icon' => '⏱️',
            ],
            'overtime' => [
                'text' => 'Lembur',
                'color' => '#3b82f6', // blue
                'bg_color' => '#dbeafe',
                'icon' => '🕐',
            ],
            'absent' => [
                'text' => 'Tidak Hadir',
                'color' => '#ef4444', // red
                'bg_color' => '#fee2e2',
                'icon' => '✗',
            ],
            'leave' => [
                'text' => 'Izin/Cuti',
                'color' => '#8b5cf6', // purple
                'bg_color' => '#ede9fe',
                'icon' => '📄',
            ],
            'holiday' => [
                'text' => 'Libur',
                'color' => '#6b7280', // gray
                'bg_color' => '#f3f4f6',
                'icon' => '🏖️',
            ],
            'weekend' => [
                'text' => 'Akhir Pekan',
                'color' => '#6b7280', // gray
                'bg_color' => '#f3f4f6',
                'icon' => '🏠',
            ],
        ];
    }

    /**
     * Export pivot table to CSV
     */
    public function exportToCsv($pivotData, $statusConfig)
    {
        $filename = "pivot_absensi_" . date('Y-m-d_H-i-s') . ".csv";
        $handle = fopen('php://memory', 'r+');

        // Header
        $header = ['Nama Karyawan', 'Email', 'Outlet'];
        foreach ($pivotData['date_range']['dates'] as $date) {
            $header[] = Carbon::parse($date)->format('d/m');
        }
        fputcsv($handle, $header);

        // Data rows
        foreach ($pivotData['employees'] as $employee) {
            $row = [
                $employee['name'],
                $employee['email'],
                $employee['outlet'] ?? '',
            ];

            foreach ($pivotData['date_range']['dates'] as $date) {
                $attendance = $employee['attendances'][$date] ?? null;
                if ($attendance) {
                    $statusText = $statusConfig[$attendance['status']]['text'] ?? 'Unknown';
                    $row[] = $statusText;
                } else {
                    $row[] = '-';
                }
            }

            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return [
            'filename' => $filename,
            'content' => $csv,
        ];
    }

    /**
     * Clear cache for specific owner
     */
    public function clearCache($ownerId)
    {
        $pattern = "pivot_table_{$ownerId}_*";
        $keys = Cache::getRedis()->keys($pattern);
        
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }
    }
}
