<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\EmployeeShift;
use App\Models\Outlet;
use App\Models\User;
use App\Services\ShiftSchedulingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShiftController extends Controller
{
    /**
     * Display shift management dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $outletId = $request->get('outlet_id');
        $dateFrom = $request->get('date_from') ? \Carbon\Carbon::parse($request->get('date_from')) : now()->startOfMonth();
        $dateTo = $request->get('date_to') ? \Carbon\Carbon::parse($request->get('date_to')) : now()->endOfMonth();

        // Get shifts
        $shifts = Shift::active()->get();
        
        // Get shift schedules
        $shiftSchedules = ShiftSchedule::with(['shift', 'outlet'])
            ->when($outletId, function ($query) use ($outletId) {
                return $query->where('outlet_id', $outletId);
            })
            ->whereBetween('effective_date', [$dateFrom, $dateTo])
            ->orderBy('effective_date', 'desc')
            ->paginate(20);

        // Get employee shifts
        $employeeShifts = EmployeeShift::with(['user', 'shift'])
            ->when($outletId, function ($query) use ($outletId) {
                return $query->whereHas('user', function ($subQuery) use ($outletId) {
                    $subQuery->where('outlet_id', $outletId);
                });
            })
            ->whereBetween('start_date', [$dateFrom, $dateTo])
            ->orderBy('start_date', 'desc')
            ->paginate(20);

        return inertia('Shift/Index', [
            'shifts' => $shifts,
            'shiftSchedules' => $shiftSchedules,
            'employeeShifts' => $employeeShifts,
            'filters' => [
                'outlet_id' => $outletId,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Display schedule management page.
     */
    public function schedulePage(Request $request)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $outletId = $request->get('outlet_id');
        $dateFrom = $request->get('date_from') ? \Carbon\Carbon::parse($request->get('date_from')) : now()->startOfMonth();
        $dateTo = $request->get('date_to') ? \Carbon\Carbon::parse($request->get('date_to')) : now()->endOfMonth();

        $shiftSchedules = ShiftSchedule::with(['shift', 'outlet'])
            ->when($outletId, function ($query) use ($outletId) {
                return $query->where('outlet_id', $outletId);
            })
            ->whereBetween('effective_date', [$dateFrom, $dateTo])
            ->orderBy('effective_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $outlets = Outlet::select('id', 'name')->orderBy('name')->get();

        return inertia('Shift/Schedule', [
            'shiftSchedules' => $shiftSchedules,
            'outlets' => $outlets,
            'filters' => [
                'outlet_id' => $outletId,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Display shift assignment management page.
     */
    public function assignmentPage(Request $request)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $outletId = $request->get('outlet_id');

        $employeeShifts = EmployeeShift::with(['user', 'shift'])
            ->when($outletId, function ($query) use ($outletId) {
                return $query->whereHas('user', function ($subQuery) use ($outletId) {
                    $subQuery->where('outlet_id', $outletId);
                });
            })
            ->orderBy('start_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $employees = User::where('role', 'employee')
            ->select('id', 'name', 'email', 'outlet_id')
            ->orderBy('name')
            ->get();

        $shifts = Shift::orderBy('name')->get([
            'id',
            'name',
            'start_time',
            'end_time',
            'break_duration',
        ]);

        $outlets = Outlet::select('id', 'name')->orderBy('name')->get();

        return inertia('Shift/Assignment', [
            'employeeShifts' => $employeeShifts,
            'employees' => $employees,
            'shifts' => $shifts,
            'outlets' => $outlets,
            'filters' => [
                'outlet_id' => $outletId,
            ],
        ]);
    }

    /**
     * Store new shift.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'break_duration' => 'nullable|integer|min:0|max:180',
            'is_overnight' => 'boolean',
            'color_code' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $shift = Shift::create([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'break_duration' => $request->break_duration ?? 60,
            'is_overnight' => $request->is_overnight ?? false,
            'color_code' => $request->color_code ?? '#3B82F6',
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return back()->with('success', 'Shift created successfully');
    }

    /**
     * Update existing shift.
     */
    public function update(Request $request, Shift $shift)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'break_duration' => 'nullable|integer|min:0|max:180',
            'is_overnight' => 'boolean',
            'color_code' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $shift->update([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'break_duration' => $request->break_duration ?? $shift->break_duration,
            'is_overnight' => $request->is_overnight ?? $shift->is_overnight,
            'color_code' => $request->color_code ?? $shift->color_code,
            'description' => $request->description ?? $shift->description,
            'is_active' => $request->is_active ?? $shift->is_active,
        ]);

        return back()->with('success', 'Shift updated successfully');
    }

    /**
     * Generate shift schedule.
     */
    public function generateSchedule(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:500',
        ]);

        $result = ShiftSchedulingService::generateShiftSchedule(
            $request->outlet_id,
            \Carbon\Carbon::parse($request->start_date),
            \Carbon\Carbon::parse($request->end_date)
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors([
            'schedule' => $result['message']
        ]);
    }

    /**
     * Assign employee to shift.
     */
    public function assignEmployee(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'shift_id' => 'required|exists:shifts,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:500',
        ]);

        $result = ShiftSchedulingService::assignEmployeeToShift(
            $request->user_id,
            $request->shift_id,
            \Carbon\Carbon::parse($request->start_date),
            $request->end_date ? \Carbon\Carbon::parse($request->end_date) : null
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors([
            'assignment' => $result['message']
        ]);
    }

    /**
     * Handle shift swap request.
     */
    public function swapShift(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'requester_id' => 'required|exists:users,id',
            'target_id' => 'required|exists:users,id',
            'date' => 'required|date',
        ]);

        $result = ShiftSchedulingService::handleShiftSwap(
            $request->requester_id,
            $request->target_id,
            \Carbon\Carbon::parse($request->date)
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors([
            'swap' => $result['message']
        ]);
    }

    /**
     * Display schedule for authenticated employee.
     */
    public function employeeSchedule(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        $currentAssignment = ShiftSchedulingService::getEmployeeShift($user->id);

        $history = EmployeeShift::with('shift')
            ->where('user_id', $user->id)
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        $weeklyPreview = collect(range(0, 6))->map(function ($offset) use ($user) {
            $date = Carbon::now()->startOfDay()->addDays($offset);
            $assignment = ShiftSchedulingService::getEmployeeShift(
                $user->id,
                $date->copy()
            );
            $shift = $assignment?->shift;

            return [
                'date' => $date->toDateString(),
                'day_label' => $date->translatedFormat('l'),
                'shift_name' => $shift?->name,
                'start_time' => $shift?->start_time
                    ? $shift->start_time->format('H:i')
                    : null,
                'end_time' => $shift?->end_time
                    ? $shift->end_time->format('H:i')
                    : null,
                'is_today' => $date->isToday(),
            ];
        });

        $stats = [
            'total_assignments' => $history->total(),
            'current_shift_name' => $currentAssignment?->shift?->name,
            'active_since' => $currentAssignment?->start_date
                ? Carbon::parse($currentAssignment->start_date)->toDateString()
                : null,
        ];

        return inertia('Shift/MySchedule', [
            'currentAssignment' => $currentAssignment,
            'history' => $history,
            'weeklyPreview' => $weeklyPreview,
            'stats' => $stats,
        ]);
    }

    /**
     * Get shift statistics.
     */
    public function statistics(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $outletId = $request->get('outlet_id');
        $startDate = $request->get('start_date') ? \Carbon\Carbon::parse($request->get('start_date')) : now()->startOfMonth();
        $endDate = $request->get('end_date') ? \Carbon\Carbon::parse($request->get('end_date')) : now()->endOfMonth();

        $result = ShiftSchedulingService::getShiftStatistics($outletId, $startDate, $endDate);

        return inertia('Shift/Statistics', [
            'statistics' => $result['statistics'],
            'filters' => [
                'outlet_id' => $outletId,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Display shift details.
     */
    public function show(Shift $shift)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        $shift->load(['shiftSchedules', 'employeeShifts']);

        return inertia('Shift/Show', [
            'shift' => $shift,
        ]);
    }

    /**
     * Delete shift.
     */
    public function destroy(Shift $shift)
    {
        $user = Auth::user();
        
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        // Check if shift is being used
        $isInUse = ShiftSchedule::where('shift_id', $shift->id)
            ->where('is_active', true)
            ->exists();

        if ($isInUse) {
            return back()->withErrors([
                'shift' => 'Cannot delete shift that is currently in use'
            ]);
        }

        $shift->delete();

        return back()->with('success', 'Shift deleted successfully');
    }
}
