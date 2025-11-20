<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\OvertimeRecord;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Outlet;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollCalculationService
{
    /**
     * Calculate payroll for user in period.
     */
    public static function calculatePayroll(
        int $userId,
        Carbon $startDate,
        Carbon $endDate,
        ?float $periodBaseRate = null,
        ?float $periodOvertimeRate = null
    ): array
    {
        // Normalize range to cover full days
        $startDate = (clone $startDate)->startOfDay();
        $endDate = (clone $endDate)->endOfDay();

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

        // Calculate base metrics
        $periodDays = max(1, $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay()) + 1);
        $workedDays = $attendances->count();
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
            
            // Get outlet operational hours as plain time strings to avoid Carbon stringifying with a date
            $operationalStart = self::resolveOperationalTimeString(
                $attendance->outlet->operational_start_time ?? null,
                '09:00'
            );
            $operationalEnd = self::resolveOperationalTimeString(
                $attendance->outlet->operational_end_time ?? null,
                '18:00'
            );
            
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

        // Normalize optional overrides
        $normalizedPeriodBase = ($periodBaseRate === '' || $periodBaseRate === null) ? null : $periodBaseRate;
        $normalizedPeriodOvertime = ($periodOvertimeRate === '' || $periodOvertimeRate === null) ? null : $periodOvertimeRate;

        // Calculate base salary (monthly) and overtime rate, honoring period overrides
        $baseSalary = self::getBaseSalary($user, $outlet, $normalizedPeriodBase);
        $overtimeRate = self::getOvertimeRate($outlet, $normalizedPeriodOvertime);

        // Pro-rate base pay by working days instead of multiplying by total hours
        $targetWorkDays = self::calculateWorkingDays($startDate, $endDate);
        $workedRatio = $targetWorkDays > 0 ? min($workedDays, $targetWorkDays) / $targetWorkDays : 0;
        $totalBasePay = $baseSalary * $workedRatio;

        // Calculate payroll
        $totalOvertimePay = 0;
        foreach ($overtimeRecords as $overtime) {
            $amount = ($overtime['overtime_minutes'] / 60) * $baseSalary * $overtimeRate * $overtime['rate_multiplier'];
            $totalOvertimePay += $amount;
        }

        $grossPay = $totalBasePay + $totalOvertimePay;

        // Calculate deductions (simplified)
        $applyTax = $outlet->use_tax ?? true;
        $taxDeduction = $applyTax ? $grossPay * 0.05 : 0; // 5% tax if enabled
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
                    'total_days' => $periodDays,
                ],
                'attendance_summary' => [
                    'total_days' => $workedDays,
                    'total_work_hours' => round($totalWorkHours, 2),
                    'average_work_hours_per_day' => $workedDays > 0 ? round($totalWorkHours / $workedDays, 2) : 0,
                    'total_overtime_hours' => round(array_sum(array_column($overtimeRecords, 'overtime_minutes')) / 60, 2),
                ],
                'salary_calculation' => [
                    'base_salary' => $baseSalary,
                    'total_base_pay' => $totalBasePay,
                    'worked_days' => $workedDays,
                    'workday_target' => $targetWorkDays,
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
        ?float $basicRate = null,
        ?float $overtimeRate = null
    ): array {
        DB::beginTransaction();
        
        try {
            $normalizedBasicRate = ($basicRate === '' || $basicRate === null) ? 0 : $basicRate;
            $normalizedOvertimeRate = ($overtimeRate === '' || $overtimeRate === null) ? 1.5 : $overtimeRate;

            // Create payroll period
            $payrollPeriod = PayrollPeriod::create([
                'name' => $name,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'basic_rate' => $normalizedBasicRate, // Default 0
                'overtime_rate' => $normalizedOvertimeRate, // Default 1.5x
                'status' => 'active',
            ]);

            // Generate payroll for each user
            $payrollRecords = [];
            $errors = [];
            foreach ($userIds as $userId) {
                $result = self::calculatePayroll(
                    $userId,
                    $startDate,
                    $endDate,
                    $basicRate,
                    $overtimeRate
                );
                
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
                        'total_pay' => $payrollData['salary_calculation']['gross_pay'],
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
                } else {
                    $errors[] = [
                        'user_id' => $userId,
                        'error' => $result['message'],
                    ];
                }
            }

            if (empty($payrollRecords)) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Failed to generate any payroll record for this period',
                    'errors' => $errors,
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => empty($errors)
                    ? 'Payroll period created successfully'
                    : 'Payroll period created with some issues',
                'payroll_period' => $payrollPeriod,
                'payroll_records' => $payrollRecords,
                'total_records' => count($payrollRecords),
                'errors' => $errors,
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
     * Generate payroll records for an existing period.
     */
    public static function generatePayrollForExistingPeriod(
        int $payrollPeriodId,
        array $userIds,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        DB::beginTransaction();

        try {
            $payrollPeriod = PayrollPeriod::findOrFail($payrollPeriodId);
            $createdRecords = [];
            $errors = [];

            foreach ($userIds as $userId) {
                $alreadyExists = PayrollRecord::where('payroll_period_id', $payrollPeriodId)
                    ->where('user_id', $userId)
                    ->exists();

                if ($alreadyExists) {
                    $errors[] = [
                        'user_id' => $userId,
                        'error' => 'Payroll already exists for this user in the selected period',
                    ];
                    continue;
                }

                $result = self::calculatePayroll(
                    $userId,
                    $startDate,
                    $endDate,
                    $payrollPeriod->basic_rate,
                    $payrollPeriod->overtime_rate
                );

                if (!$result['success']) {
                    $errors[] = [
                        'user_id' => $userId,
                        'error' => $result['message'],
                    ];
                    continue;
                }

                $payrollData = $result['data'];

                $payrollRecord = PayrollRecord::create([
                    'payroll_period_id' => $payrollPeriod->id,
                    'user_id' => $userId,
                    'base_salary' => $payrollData['salary_calculation']['base_salary'],
                    'overtime_pay' => $payrollData['salary_calculation']['total_overtime_pay'],
                    'leave_deduction' => 0,
                    'bonus' => 0,
                    'tax_deduction' => $payrollData['salary_calculation']['tax_deduction'],
                    'other_deductions' => $payrollData['salary_calculation']['other_deductions'],
                    'total_pay' => $payrollData['salary_calculation']['gross_pay'],
                    'status' => 'calculated',
                ]);

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

                $createdRecords[] = $payrollRecord;
            }

            if (empty($createdRecords) && !empty($errors)) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'No payroll generated for the selected users',
                    'errors' => $errors,
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => empty($errors) ? 'Payroll generated successfully' : 'Payroll generated with some issues',
                'payroll_period' => $payrollPeriod,
                'payroll_records' => $createdRecords,
                'total_records' => count($createdRecords),
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Failed to generate payroll: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize operational time to an HH:MM:SS string.
     */
    private static function resolveOperationalTimeString($timeValue, string $fallback): string
    {
        if ($timeValue instanceof Carbon || $timeValue instanceof \DateTimeInterface) {
            return Carbon::instance($timeValue)->format('H:i:s');
        }

        if (is_string($timeValue) && trim($timeValue) !== '') {
            return trim($timeValue);
        }

        return $fallback;
    }

    /**
     * Get base salary for user.
     */
    private static function getBaseSalary(User $user, ?Outlet $outlet, ?float $periodBaseRate = null): float
    {
        // Priority order: User profile > Period override > Outlet settings > Default
        if ($user->base_salary) {
            return $user->base_salary;
        }

        if ($periodBaseRate !== null) {
            return $periodBaseRate;
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
    private static function getOvertimeRate(?Outlet $outlet, ?float $periodOvertimeRate = null): float
    {
        // Priority order: Period override > Outlet settings > Default
        if ($periodOvertimeRate !== null) {
            return $periodOvertimeRate;
        }

        if ($outlet && $outlet->overtime_rate) {
            return $outlet->overtime_rate;
        }
        
        return 1.5; // Default 1.5x
    }

    /**
    * Estimate working days (Mon–Fri) in range; never returns zero to avoid division by zero.
    */
    private static function calculateWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        $period = CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay());
        $days = 0;

        foreach ($period as $date) {
            if (! $date->isWeekend()) {
                $days++;
            }
        }

        return max(1, $days);
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
    public static function getPayrollStatistics(?int $outletId, Carbon $startDate, Carbon $endDate, ?int $ownerId = null): array
    {
        $payrollRecords = PayrollRecord::when($ownerId, function ($query) use ($ownerId) {
                $query->whereHas('user', function ($subQuery) use ($ownerId) {
                    $subQuery->whereHas('outlet', function ($outletQuery) use ($ownerId) {
                        $outletQuery->where('owner_id', $ownerId);
                    });
                });
            })
            ->when($outletId, function ($query) use ($outletId) {
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

        $totalGrossPay = $payrollRecords->sum('total_pay');
        $totalTaxDeduction = $payrollRecords->sum('tax_deduction');
        $totalOvertimePay = $payrollRecords->sum('overtime_pay');
        $totalNetPay = $payrollRecords->sum(function ($record) {
            return $record->total_pay - $record->tax_deduction - $record->other_deductions;
        });
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

            if ($payrollPeriod->status !== 'active') {
                return [
                    'success' => false,
                    'message' => 'Only active payroll periods can be approved',
                ];
            }
            
            // Update period status
            $payrollPeriod->update([
                'status' => 'completed',
            ]);

            // Update all records to approved
            $payrollRecords = PayrollRecord::where('payroll_period_id', $payrollPeriodId)
                ->where('status', 'calculated')
                ->get();

            if ($payrollRecords->isEmpty()) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'No calculated payroll records found to approve',
                ];
            }

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
    public static function processPayrollPayments(
        int $payrollPeriodId,
        ?string $paymentMethod = null,
        ?string $paymentReference = null,
        ?Carbon $paidAt = null
    ): array
    {
        DB::beginTransaction();
        
        try {
            $payrollPeriod = PayrollPeriod::findOrFail($payrollPeriodId);

            if ($payrollPeriod->status !== 'completed') {
                return [
                    'success' => false,
                    'message' => 'Only completed payroll periods can be paid',
                ];
            }
            
            // Update period status
            $payrollPeriod->update([
                'status' => 'paid',
            ]);

            // Update all records to paid
            $payrollRecords = PayrollRecord::where('payroll_period_id', $payrollPeriodId)
                ->where('status', 'approved')
                ->get();

            if ($payrollRecords->isEmpty()) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'No approved payroll records to process payments',
                ];
            }

            $paidTimestamp = $paidAt ?? now();
            foreach ($payrollRecords as $record) {
                $record->update([
                    'status' => 'paid',
                    'paid_at' => $paidTimestamp,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentReference,
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Payroll payments processed successfully',
                'payroll_period' => $payrollPeriod->fresh(),
                'total_records' => $payrollRecords->count(),
                'total_amount' => $payrollRecords->sum(function ($record) {
                    return ($record->total_pay - $record->tax_deduction - $record->other_deductions);
                }),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to process payments: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Export payroll data for a period as CSV.
     */
    public static function exportPayrollData(int $payrollPeriodId, string $format = 'csv'): array
    {
        if ($format !== 'csv') {
            return [
                'success' => false,
                'message' => 'Unsupported export format',
            ];
        }

        $payrollPeriod = PayrollPeriod::with(['payrollRecords.user', 'payrollRecords.overtimeRecords'])
            ->findOrFail($payrollPeriodId);

        $rows = [];
        $header = [
            'Nama',
            'Email',
            'Base Salary',
            'Overtime Pay',
            'Leave Deduction',
            'Bonus',
            'Tax Deduction',
            'Other Deductions',
            'Gross Pay',
            'Net Pay',
            'Status',
            'Paid At',
            'Payment Method',
            'Payment Reference',
        ];

        foreach ($payrollPeriod->payrollRecords as $record) {
            $gross = $record->total_pay;
            $net = $gross - $record->tax_deduction - $record->other_deductions;

            $rows[] = [
                $record->user->name ?? 'Karyawan #' . $record->user_id,
                $record->user->email ?? '-',
                $record->base_salary,
                $record->overtime_pay,
                $record->leave_deduction,
                $record->bonus,
                $record->tax_deduction,
                $record->other_deductions,
                $gross,
                $net,
                $record->status,
                $record->paid_at,
                $record->payment_method,
                $record->payment_reference,
            ];
        }

        $csvLines = [];
        $csvLines[] = implode(',', $header);

        foreach ($rows as $row) {
            $escaped = array_map(function ($value) {
                $stringValue = (string) $value;
                if (str_contains($stringValue, ',') || str_contains($stringValue, '"')) {
                    $stringValue = '"' . str_replace('"', '""', $stringValue) . '"';
                }
                return $stringValue;
            }, $row);

            $csvLines[] = implode(',', $escaped);
        }

        $filename = 'payroll_' . Str::slug($payrollPeriod->name) . '_' . now()->format('Ymd_His') . '.csv';

        return [
            'success' => true,
            'filename' => $filename,
            'content' => implode("\n", $csvLines),
        ];
    }

    /**
     * Export payroll summary (cash-out recap) as CSV.
     */
    public static function exportPayrollSummary(int $payrollPeriodId): array
    {
        $payrollPeriod = PayrollPeriod::with(['payrollRecords.user'])->findOrFail($payrollPeriodId);
        $records = $payrollPeriod->payrollRecords;

        if ($records->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No payroll records found for summary export',
            ];
        }

        $header = [
            'Period',
            'Total Employees',
            'Total Gross',
            'Total Tax',
            'Total Other Deductions',
            'Total Net',
            'Paid Records',
            'Unpaid Records',
        ];

        $totalGross = $records->sum('total_pay');
        $totalTax = $records->sum('tax_deduction');
        $totalOther = $records->sum('other_deductions');
        $totalNet = $records->sum(function ($record) {
            return $record->total_pay - $record->tax_deduction - $record->other_deductions;
        });
        $paidCount = $records->where('status', 'paid')->count();
        $unpaidCount = $records->count() - $paidCount;

        $row = [
            $payrollPeriod->name,
            $records->count(),
            $totalGross,
            $totalTax,
            $totalOther,
            $totalNet,
            $paidCount,
            $unpaidCount,
        ];

        $lines = [];
        $lines[] = implode(',', $header);
        $lines[] = implode(',', array_map(fn($v) => is_numeric($v) ? $v : '"' . str_replace('"', '""', $v) . '"', $row));

        $filename = 'payroll_summary_' . Str::slug($payrollPeriod->name) . '_' . now()->format('Ymd_His') . '.csv';

        return [
            'success' => true,
            'filename' => $filename,
            'content' => implode("\n", $lines),
        ];
    }

    /**
     * Export single payroll slip as CSV.
     */
    public static function exportPayrollSlip(int $payrollRecordId): array
    {
        $record = PayrollRecord::with(['user', 'payrollPeriod', 'overtimeRecords'])->findOrFail($payrollRecordId);
        $gross = $record->total_pay;
        $net = $gross - $record->tax_deduction - $record->other_deductions;

        $header = [
            'Field',
            'Value',
        ];

        $rows = [
            ['Nama', $record->user->name ?? 'Karyawan #' . $record->user_id],
            ['Email', $record->user->email ?? '-'],
            ['Periode', $record->payrollPeriod->name ?? '-'],
            ['Status', $record->status],
            ['Base Salary', $record->base_salary],
            ['Overtime Pay', $record->overtime_pay],
            ['Bonus', $record->bonus],
            ['Leave Deduction', $record->leave_deduction],
            ['Other Deductions', $record->other_deductions],
            ['Tax Deduction', $record->tax_deduction],
            ['Gross Pay', $gross],
            ['Net Pay', $net],
            ['Paid At', $record->paid_at],
            ['Payment Method', $record->payment_method],
            ['Payment Reference', $record->payment_reference],
            ['Notes', $record->notes],
        ];

        // Append overtime breakdown
        foreach ($record->overtimeRecords as $overtime) {
            $rows[] = ['Overtime ' . $overtime->date, $overtime->overtime_minutes . ' minutes'];
        }

        $lines = [];
        $lines[] = implode(',', $header);
        foreach ($rows as $row) {
            $escaped = array_map(function ($value) {
                $stringValue = (string) $value;
                if (str_contains($stringValue, ',') || str_contains($stringValue, '"')) {
                    $stringValue = '"' . str_replace('"', '""', $stringValue) . '"';
                }
                return $stringValue;
            }, $row);
            $lines[] = implode(',', $escaped);
        }

        $filename = 'payroll_slip_' . Str::slug($record->user->name ?? 'karyawan') . '_' . now()->format('Ymd_His') . '.csv';

        return [
            'success' => true,
            'filename' => $filename,
            'content' => implode("\n", $lines),
        ];
    }
}
