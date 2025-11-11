<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\OvertimeRecord;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Outlet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    /**
     * Calculate payroll for user in period.
     */
    public static function calculatePayroll(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        // Get user and outlet
        $user = User::findOrFail($userId);
        $outlet = $user->outlet;
        
        if (!$outlet) {
            return [
                'success' => false,
                'message' => 'User is not assigned to any outlet',
            ];
        }

        // Get attendances for period
        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('check_in_time', [$startDate, $endDate])
            ->whereNotNull('check_out_time')
            ->with(['outlet'])
            ->get();

        if ($attendances->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No completed attendances found for this period',
            ];
        }

        // Calculate base metrics
        $totalDays = $attendances->count();
        $totalWorkHours = $attendances->sum(function ($attendance) {
            $checkIn = Carbon::parse($attendance->check_in_time);
            $checkOut = Carbon::parse($attendance->check_out_time);
            return $checkIn->diffInMinutes($checkOut) / 60;
        });

        // Calculate overtime
        $overtimeRecords = [];
        foreach ($attendances as $attendance) {
            $checkIn = Carbon::parse($attendance->check_in_time);
            $checkOut = Carbon::parse($attendance->check_out_time);
            
            // Get outlet operational hours
            $operationalStart = $attendance->outlet->operational_start_time ?? '09:00';
            $operationalEnd = $attendance->outlet->operational_end_time ?? '18:00';
            
            $startTime = Carbon::parse($checkIn->format('Y-m-d') . ' ' . $operationalStart);
            $endTime = Carbon::parse($checkOut->format('Y-m-d') . ' ' . $operationalEnd);
            
            // Calculate overtime (time beyond operational hours)
            if ($checkOut->gt($endTime)) {
                $overtimeMinutes = $checkOut->diffInMinutes($endTime);
                
                if ($overtimeMinutes > 0) {
                    $overtimeRecords[] = [
                        'attendance_id' => $attendance->id,
                        'date' => $checkIn->format('Y-m-d'),
                        'overtime_minutes' => $overtimeMinutes,
                        'rate_multiplier' => self::getOvertimeRateMultiplier($overtimeMinutes),
                        'overtime_type' => self::getOvertimeType($checkIn),
                    ];
                }
            }
        }

        // Calculate base salary (could be from user profile or outlet settings)
        $baseSalary = self::getBaseSalary($user, $outlet);
        $overtimeRate = self::getOvertimeRate($outlet);

        // Calculate payroll
        $totalOvertimePay = 0;
        foreach ($overtimeRecords as $overtime) {
            $amount = ($overtime['overtime_minutes'] / 60) * $baseSalary * $overtimeRate * $overtime['rate_multiplier'];
            $totalOvertimePay += $amount;
        }

        $totalBasePay = ($totalWorkHours / $totalDays) * $baseSalary * $totalDays;
        $grossPay = $totalBasePay + $totalOvertimePay;

        // Calculate deductions (simplified)
        $taxDeduction = $grossPay * 0.05; // 5% tax
        $otherDeductions = 0; // Could include BPJS, insurance, etc.

        $netPay = $grossPay - $taxDeduction - $otherDeductions;

        return [
            'success' => true,
            'message' => 'Payroll calculated successfully',
            'data' => [
                'user' => $user,
                'outlet' => $outlet,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'total_days' => $totalDays,
                ],
                'attendance_summary' => [
                    'total_days' => $totalDays,
                    'total_work_hours' => round($totalWorkHours, 2),
                    'average_work_hours_per_day' => round($totalWorkHours / $totalDays, 2),
                    'total_overtime_hours' => round(array_sum(array_column($overtimeRecords, 'overtime_minutes')) / 60, 2),
                ],
                'salary_calculation' => [
                    'base_salary' => $baseSalary,
                    'total_base_pay' => $totalBasePay,
                    'total_overtime_pay' => $totalOvertimePay,
                    'gross_pay' => $grossPay,
                    'tax_deduction' => $taxDeduction,
                    'other_deductions' => $otherDeductions,
                    'net_pay' => $netPay,
                ],
                'overtime_records' => $overtimeRecords,
            ],
        ];
    }

    /**
     * Generate payroll records for multiple users.
     */
    public static function generateBulkPayroll(array $userIds, Carbon $startDate, Carbon $endDate): array
    {
        $results = [];
        $errors = [];

        foreach ($userIds as $userId) {
            $result = self::calculatePayroll($userId, $startDate, $endDate);
            
            if ($result['success']) {
                $results[] = $result;
            } else {
                $errors[] = [
                    'user_id' => $userId,
                    'error' => $result['message'],
                ];
            }
        }

        return [
            'success' => empty($errors),
            'message' => empty($errors) ? 'Payroll generated successfully' : 'Some payrolls failed',
            'results' => $results,
            'errors' => $errors,
            'total_processed' => count($results),
            'total_failed' => count($errors),
        ];
    }

    /**
     * Create payroll period and records.
     */
    public static function createPayrollPeriod(
        string $name,
        Carbon $startDate,
        Carbon $endDate,
        array $userIds,
        float $basicRate = null,
        float $overtimeRate = null
    ): array {
        DB::beginTransaction();
        
        try {
            // Create payroll period
            $payrollPeriod = PayrollPeriod::create([
                'name' => $name,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'basic_rate' => $basicRate ?? 5000000, // Default 5 juta
                'overtime_rate' => $overtimeRate ?? 1.5, // Default 1.5x
                'status' => 'active',
            ]);

            // Generate payroll for each user
            $payrollRecords = [];
            foreach ($userIds as $userId) {
                $result = self::calculatePayroll($userId, $startDate, $endDate);
                
                if ($result['success']) {
                    $payrollData = $result['data'];
                    
                    // Create payroll record
                    $payrollRecord = PayrollRecord::create([
                        'payroll_period_id' => $payrollPeriod->id,
                        'user_id' => $userId,
                        'base_salary' => $payrollData['salary_calculation']['base_salary'],
                        'overtime_pay' => $payrollData['salary_calculation']['total_overtime_pay'],
                        'leave_deduction' => 0, // Calculate from leave system
                        'bonus' => 0, // Could be from performance system
                        'tax_deduction' => $payrollData['salary_calculation']['tax_deduction'],
                        'other_deductions' => $payrollData['salary_calculation']['other_deductions'],
                        'total_pay' => $payrollData['salary_calculation']['net_pay'],
                        'status' => 'calculated',
                    ]);

                    // Create overtime records
                    foreach ($payrollData['overtime_records'] as $overtimeData) {
                        OvertimeRecord::create([
                            'payroll_record_id' => $payrollRecord->id,
                            'attendance_id' => $overtimeData['attendance_id'],
                            'overtime_minutes' => $overtimeData['overtime_minutes'],
                            'rate_multiplier' => $overtimeData['rate_multiplier'],
                            'overtime_amount' => $overtimeData['overtime_minutes'] / 60 * $payrollData['salary_calculation']['base_salary'] * $payrollPeriod->overtime_rate * $overtimeData['rate_multiplier'],
                            'overtime_type' => $overtimeData['overtime_type'],
                        ]);
                    }
                    
                    $payrollRecords[] = $payrollRecord;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Payroll period created successfully',
                'payroll_period' => $payrollPeriod,
                'payroll_records' => $payrollRecords,
                'total_records' => count($payrollRecords),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to create payroll period: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get base salary for user.
     */
    private static function getBaseSalary(User $user, ?Outlet $outlet): float
    {
        // Priority order: User profile > Outlet settings > Default
        if ($user->base_salary) {
            return $user->base_salary;
        }
        
        if ($outlet && $outlet->default_salary) {
            return $outlet->default_salary;
        }
        
        // Default minimum wage (could be from government regulations)
        return 2000000; // 2 juta per month
    }

    /**
     * Get overtime rate for outlet.
     */
    private static function getOvertimeRate(?Outlet $outlet): float
    {
        // Priority order: Outlet settings > Default
        if ($outlet && $outlet->overtime_rate) {
            return $outlet->overtime_rate;
        }
        
        return 1.5; // Default 1.5x
    }

    /**
     * Get overtime rate multiplier based on minutes.
     */
    private static function getOvertimeRateMultiplier(int $minutes): float
    {
        if ($minutes <= 60) {
            return 1.0; // Regular rate
        } elseif ($minutes <= 120) {
            return 1.5; // 1.5x for first hour
        } else {
            return 2.0; // 2x for beyond 2 hours
        }
    }

    /**
     * Get overtime type based on date.
     */
    private static function getOvertimeType(Carbon $date): string
    {
        $dayOfWeek = $date->dayOfWeekIso;
        
        // Weekend overtime
        if ($dayOfWeek >= 6) {
            return 'weekend';
        }
        
        // Check if it's a holiday (simplified - could integrate with holiday system)
        $holidays = [
            '01-01', // New Year
            '05-01', // Labor Day
            '08-17', // Independence Day
            '12-25', // Christmas
        ];
        
        $dateString = $date->format('m-d');
        if (in_array($dateString, $holidays)) {
            return 'holiday';
        }
        
        return 'regular';
    }

    /**
     * Get payroll statistics for outlet.
     */
    public static function getPayrollStatistics(?int $outletId, Carbon $startDate, Carbon $endDate): array
    {
        $payrollRecords = PayrollRecord::when($outletId, function ($query) use ($outletId) {
            $query->whereHas('user', function ($subQuery) use ($outletId) {
                $subQuery->where('outlet_id', $outletId);
            });
        })
        ->whereHas('payrollPeriod', function ($query) use ($startDate, $endDate) {
            $query->whereDate('start_date', '>=', $startDate)
                  ->whereDate('end_date', '<=', $endDate);
        })
        ->with(['user', 'payrollPeriod'])
        ->get();

        $totalNetPay = $payrollRecords->sum('net_pay');
        $totalGrossPay = $payrollRecords->sum('gross_pay');
        $totalOvertimePay = $payrollRecords->sum('overtime_pay');
        $totalTaxDeduction = $payrollRecords->sum('tax_deduction');
        $totalEmployees = $payrollRecords->pluck('user_id')->unique()->count();

        return [
            'success' => true,
            'statistics' => [
                'total_employees' => $totalEmployees,
                'total_records' => $payrollRecords->count(),
                'total_net_pay' => $totalNetPay,
                'total_gross_pay' => $totalGrossPay,
                'total_overtime_pay' => $totalOvertimePay,
                'total_tax_deduction' => $totalTaxDeduction,
                'average_net_pay' => $totalEmployees > 0 ? $totalNetPay / $totalEmployees : 0,
                'payroll_period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
            ],
        ];
    }

    /**
     * Approve payroll records.
     */
    public static function approvePayrollPeriod(int $payrollPeriodId, int $approverId): array
    {
        DB::beginTransaction();
        
        try {
            $payrollPeriod = PayrollPeriod::findOrFail($payrollPeriodId);
            
            // Update period status
            $payrollPeriod->update([
                'status' => 'completed',
            ]);

            // Update all records to approved
            $payrollRecords = PayrollRecord::where('payroll_period_id', $payrollPeriodId)
                ->where('status', 'calculated')
                ->get();

            foreach ($payrollRecords as $record) {
                $record->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Payroll period approved successfully',
                'payroll_period' => $payrollPeriod->fresh(),
                'total_records' => $payrollRecords->count(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to approve payroll: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process payroll payments.
     */
    public static function processPayrollPayments(int $payrollPeriodId): array
    {
        DB::beginTransaction();
        
        try {
            $payrollPeriod = PayrollPeriod::findOrFail($payrollPeriodId);
            
            // Update period status
            $payrollPeriod->update([
                'status' => 'paid',
            ]);

            // Update all records to paid
            $payrollRecords = PayrollRecord::where('payroll_period_id', $payrollPeriodId)
                ->where('status', 'approved')
                ->get();

            foreach ($payrollRecords as $record) {
                $record->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Payroll payments processed successfully',
                'payroll_period' => $payrollPeriod->fresh(),
                'total_records' => $payrollRecords->count(),
                'total_amount' => $payrollRecords->sum('net_pay'),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to process payments: ' . $e->getMessage(),
            ];
        }
    }
}
