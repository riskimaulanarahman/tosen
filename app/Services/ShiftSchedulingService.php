<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\EmployeeShift;
use App\Models\User;
use App\Models\Outlet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShiftSchedulingService
{
    /**
     * Assign employee to shift.
     */
    public static function assignEmployeeToShift(int $userId, int $shiftId, Carbon $startDate, ?Carbon $endDate = null): array
    {
        // Validate shift exists
        $shift = Shift::findOrFail($shiftId);
        
        // Check for conflicts
        $conflict = self::checkShiftConflict($userId, $shiftId, $startDate, $endDate);
        if ($conflict['has_conflict']) {
            return [
                'success' => false,
                'message' => $conflict['message'],
                'conflict_type' => $conflict['type'],
            ];
        }

        // Create or update employee shift
        $employeeShift = EmployeeShift::updateOrCreate(
            [
                'user_id' => $userId,
                'is_active' => true,
            ],
            [
                'shift_id' => $shiftId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'notes' => 'Assigned by manager',
            ]
        );

        return [
            'success' => true,
            'message' => 'Employee assigned to shift successfully',
            'employee_shift' => $employeeShift->load(['user', 'shift']),
        ];
    }

    /**
     * Get employee shift for specific date.
     */
    public static function getEmployeeShift(int $userId, ?Carbon $date = null): ?EmployeeShift
    {
        $date = $date ?? now();
        
        return EmployeeShift::where('user_id', $userId)
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
            })
            ->where('is_active', true)
            ->with('shift')
            ->first();
    }

    /**
     * Generate optimized shift schedule for outlet.
     */
    public static function generateShiftSchedule(int $outletId, Carbon $startDate, Carbon $endDate): array
    {
        $outlet = Outlet::findOrFail($outletId);
        $employees = User::where('outlet_id', $outletId)
            ->where('role', 'employee')
            ->get();
        
        $shifts = Shift::active()->get();
        
        $schedule = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dayOfWeek = $currentDate->dayOfWeekIso;
            
            // Skip weekends (Saturday=6, Sunday=7)
            if ($dayOfWeek >= 6) {
                $currentDate->addDay();
                continue;
            }
            
            // Assign employees to shifts based on requirements
            $daySchedule = self::optimizeDaySchedule($outlet, $employees, $shifts, $currentDate);
            
            $schedule[] = [
                'date' => $currentDate->toDateString(),
                'day_of_week' => $dayOfWeek,
                'shifts' => $daySchedule,
            ];
            
            $currentDate->addDay();
        }

        return [
            'success' => true,
            'message' => 'Shift schedule generated successfully',
            'schedule' => $schedule,
            'coverage_percentage' => self::calculateScheduleCoverage($outlet, $employees, $startDate, $endDate),
        ];
    }

    /**
     * Optimize day schedule based on employee requirements and outlet needs.
     */
    private static function optimizeDaySchedule(Outlet $outlet, $employees, $shifts, Carbon $date): array
    {
        $daySchedule = [];
        $assignedEmployees = [];
        
        // Get minimum required employees per shift
        $requirements = self::getOutletRequirements($outlet, $date);
        
        foreach ($shifts as $shift) {
            $requiredEmployees = $requirements[$shift->id] ?? 1;
            $availableEmployees = $employees->filter(function ($employee) use ($assignedEmployees) {
                return !in_array($employee->id, $assignedEmployees);
            });
            
            if ($availableEmployees->count() >= $requiredEmployees) {
                // Assign employees to shift
                $assignedToShift = $availableEmployees->take($requiredEmployees);
                $assignedEmployees = array_merge($assignedEmployees, $assignedToShift->pluck('id')->toArray());
                
                $daySchedule[] = [
                    'shift_id' => $shift->id,
                    'shift_name' => $shift->name,
                    'required_employees' => $requiredEmployees,
                    'assigned_employees' => $assignedToShift->map(function ($employee) {
                        return [
                            'id' => $employee->id,
                            'name' => $employee->name,
                            'email' => $employee->email,
                        ];
                    })->toArray(),
                    'coverage_status' => 'fully_covered',
                ];
            } else {
                // Not enough employees
                $daySchedule[] = [
                    'shift_id' => $shift->id,
                    'shift_name' => $shift->name,
                    'required_employees' => $requiredEmployees,
                    'assigned_employees' => [],
                    'coverage_status' => 'understaffed',
                ];
            }
        }
        
        return $daySchedule;
    }

    /**
     * Get outlet requirements based on historical data and day type.
     */
    private static function getOutletRequirements(Outlet $outlet, Carbon $date): array
    {
        // This could be enhanced with ML/AI for better predictions
        // For now, use basic rules based on day type
        
        $dayOfWeek = $date->dayOfWeekIso;
        
        // Weekend requirements (if outlet operates on weekends)
        if ($dayOfWeek >= 6) {
            return [
                1 => 1, // Morning shift
                2 => 1, // Afternoon shift
                3 => 0, // Evening shift
                4 => 0, // Night shift
            ];
        }
        
        // Weekday requirements
        return [
            1 => 2, // Morning shift
            2 => 3, // Afternoon shift
            3 => 2, // Evening shift
            4 => 1, // Night shift
        ];
    }

    /**
     * Check for shift conflicts.
     */
    private static function checkShiftConflict(int $userId, int $shiftId, Carbon $startDate, ?Carbon $endDate): array
    {
        $existingShifts = EmployeeShift::where('user_id', $userId)
            ->where('is_active', true)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereDate('start_date', '<=', $endDate)
                      ->whereDate('end_date', '>=', $startDate)
                      ->where(function ($subQuery) use ($startDate, $endDate) {
                          $subQuery->whereNull('end_date')
                                 ->orWhereDate('end_date', '>=', $startDate)
                                 ->whereDate('start_date', '<=', $endDate);
                      });
            })
            ->get();

        foreach ($existingShifts as $existingShift) {
            // Check for exact same shift
            if ($existingShift->shift_id === $shiftId) {
                return [
                    'has_conflict' => true,
                    'type' => 'duplicate_shift',
                    'message' => 'Employee is already assigned to this shift',
                ];
            }
            
            // Check for overlapping time
            if (self::datesOverlap($startDate, $endDate, $existingShift->start_date, $existingShift->end_date)) {
                return [
                    'has_conflict' => true,
                    'type' => 'overlapping_shift',
                    'message' => 'New shift overlaps with existing shift assignment',
                ];
            }
        }

        return [
            'has_conflict' => false,
            'type' => null,
            'message' => 'No conflicts found',
        ];
    }

    /**
     * Check if two date ranges overlap.
     */
    private static function datesOverlap(Carbon $start1, Carbon $end1, Carbon $start2, Carbon $end2): bool
    {
        return $start1 <= $end2 && $start2 <= $end1;
    }

    /**
     * Calculate schedule coverage percentage.
     */
    private static function calculateScheduleCoverage(Outlet $outlet, $employees, Carbon $startDate, Carbon $endDate): float
    {
        $totalRequiredSlots = 0;
        $totalAssignedSlots = 0;
        
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dayOfWeek = $currentDate->dayOfWeekIso;
            
            // Skip weekends
            if ($dayOfWeek < 6) {
                $requirements = self::getOutletRequirements($outlet, $currentDate);
                foreach ($requirements as $shiftId => $requiredCount) {
                    $totalRequiredSlots += $requiredCount;
                }
            }
            
            $currentDate->addDay();
        }
        
        // Calculate actual assignments
        $assignments = EmployeeShift::whereHas('user', function ($query) use ($outlet) {
            $query->where('outlet_id', $outlet->id);
        })
        ->whereBetween('start_date', [$startDate, $endDate])
        ->where('is_active', true)
        ->count();
        
        $totalAssignedSlots = $assignments;
        
        if ($totalRequiredSlots == 0) {
            return 100.0;
        }
        
        return min(100.0, ($totalAssignedSlots / $totalRequiredSlots) * 100);
    }

    /**
     * Handle shift swap requests between employees.
     */
    public static function handleShiftSwap(int $requesterId, int $targetId, Carbon $date): array
    {
        DB::beginTransaction();
        
        try {
            $requesterShift = self::getEmployeeShift($requesterId, $date);
            $targetShift = self::getEmployeeShift($targetId, $date);
            
            if (!$requesterShift || !$targetShift) {
                throw new \Exception('One or both employees do not have shifts for this date');
            }
            
            // Validate swap rules
            $validation = self::validateShiftSwap($requesterShift, $targetShift);
            if (!$validation['valid']) {
                throw new \Exception($validation['message']);
            }
            
            // Perform the swap
            $requesterShift->update([
                'user_id' => $targetId,
                'notes' => 'Swapped with ' . $targetShift->user->name,
            ]);
            
            $targetShift->update([
                'user_id' => $requesterId,
                'notes' => 'Swapped with ' . $requesterShift->user->name,
            ]);
            
            // Log the swap
            DB::table('shift_swap_logs')->insert([
                'requester_id' => $requesterId,
                'target_id' => $targetId,
                'date' => $date,
                'requester_shift_id' => $requesterShift->shift_id,
                'target_shift_id' => $targetShift->shift_id,
                'approved_by' => auth()->id(),
                'created_at' => now(),
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Shift swap completed successfully',
                'requester_shift' => $requesterShift->fresh(),
                'target_shift' => $targetShift->fresh(),
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Shift swap failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate shift swap request.
     */
    private static function validateShiftSwap(EmployeeShift $requesterShift, EmployeeShift $targetShift): array
    {
        // Check if shifts are compatible
        if ($requesterShift->shift_id === $targetShift->shift_id) {
            return [
                'valid' => false,
                'message' => 'Cannot swap with the same shift type',
            ];
        }
        
        // Check if time difference is acceptable (within 2 hours)
        $timeDiff = abs($requesterShift->shift->start_time->diffInMinutes($targetShift->shift->start_time));
        if ($timeDiff > 120) {
            return [
                'valid' => false,
                'message' => 'Shift time difference is too large for swapping',
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Shift swap is valid',
        ];
    }

    /**
     * Get shift statistics for outlet.
     */
    public static function getShiftStatistics(?int $outletId, Carbon $startDate, Carbon $endDate, ?int $ownerId = null): array
    {
        $shifts = Shift::withCount('employeeShifts')
            ->when($ownerId, function ($query) use ($ownerId) {
                $query->whereHas('employeeShifts', function ($subQuery) use ($ownerId) {
                    $subQuery->whereHas('user', function ($innerQuery) use ($ownerId) {
                        $innerQuery->whereHas('outlet', function ($outletQuery) use ($ownerId) {
                            $outletQuery->where('owner_id', $ownerId);
                        });
                    });
                });
            })
            ->when($outletId, function ($query) use ($outletId) {
                $query->whereHas('employeeShifts', function ($subQuery) use ($outletId) {
                    $subQuery->whereHas('user', function ($innerQuery) use ($outletId) {
                        $innerQuery->where('outlet_id', $outletId);
                    });
                });
            })
            ->get();
        
        $statistics = [];
        
        foreach ($shifts as $shift) {
            $assignments = EmployeeShift::where('shift_id', $shift->id)
                ->when($ownerId, function ($query) use ($ownerId) {
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
                ->whereBetween('start_date', [$startDate, $endDate])
                ->get();
            
            $statistics[] = [
                'shift' => $shift,
                'total_assignments' => $assignments->count(),
                'unique_employees' => $assignments->pluck('user_id')->unique()->count(),
                'avg_assignments_per_week' => self::calculateAverageAssignments($assignments),
                'coverage_percentage' => self::calculateShiftCoverage($shift, $outletId, $startDate, $endDate, $ownerId),
            ];
        }
        
        return [
            'success' => true,
            'statistics' => $statistics,
        ];
    }

    /**
     * Calculate average assignments per week.
     */
    private static function calculateAverageAssignments($assignments): float
    {
        if ($assignments->isEmpty()) {
            return 0;
        }
        
        $totalWeeks = $assignments->map(function ($assignment) {
            return $assignment->start_date->format('Y-W');
        })->unique()->count();
        
        return $totalWeeks > 0 ? $assignments->count() / $totalWeeks : 0;
    }

    /**
     * Calculate shift coverage percentage.
     */
    private static function calculateShiftCoverage(Shift $shift, ?int $outletId, Carbon $startDate, Carbon $endDate, ?int $ownerId = null): float
    {
        $totalDays = 0;
        $coveredDays = 0;
        
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dayOfWeek = $currentDate->dayOfWeekIso;
            
            // Skip weekends
            if ($dayOfWeek < 6) {
                $totalDays++;
                
                // Check if shift is covered on this day
                $isCovered = EmployeeShift::where('shift_id', $shift->id)
                    ->when($ownerId, function ($query) use ($ownerId) {
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
                    ->whereDate('start_date', $currentDate->toDateString())
                    ->where('is_active', true)
                    ->exists();
                
                if ($isCovered) {
                    $coveredDays++;
                }
            }
            
            $currentDate->addDay();
        }
        
        return $totalDays > 0 ? ($coveredDays / $totalDays) * 100 : 0;
    }
}
