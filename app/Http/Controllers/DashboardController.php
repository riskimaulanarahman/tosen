<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Outlet;
use App\Models\Attendance;

class DashboardController extends Controller
{
    /**
     * Display the user's dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->role === 'owner') {
            return $this->ownerDashboard();
        } else {
            return $this->employeeDashboard();
        }
    }

    /**
     * Display owner dashboard.
     */
    private function ownerDashboard()
    {
        $user = Auth::user();
        
        // Get owner's statistics
        $totalOutlets = Outlet::where('owner_id', $user->id)->count();
        $totalEmployees = User::where('role', 'employee')
            ->whereHas('outlet', function($query) use ($user) {
                $query->where('owner_id', $user->id);
            })->count();
        
        $totalAttendances = Attendance::whereHas('user', function($query) use ($user) {
            $query->where('role', 'employee')
                ->whereHas('outlet', function($query) use ($user) {
                    $query->where('owner_id', $user->id);
                });
        })->count();

        $todayAttendances = Attendance::whereDate('created_at', today())
            ->whereHas('user', function($query) use ($user) {
                $query->where('role', 'employee')
                    ->whereHas('outlet', function($query) use ($user) {
                        $query->where('owner_id', $user->id);
                    });
            })->count();

        // Get recent outlets
        $recentOutlets = Outlet::where('owner_id', $user->id)
            ->with('employees')
            ->latest()
            ->take(5)
            ->get();

        // Get recent attendances
        $recentAttendances = Attendance::with(['user', 'outlet'])
            ->whereHas('user', function($query) use ($user) {
                $query->where('role', 'employee')
                    ->whereHas('outlet', function($query) use ($user) {
                        $query->where('owner_id', $user->id);
                    });
            })
            ->latest()
            ->take(10)
            ->get();

        return inertia('Dashboard', [
            'user' => $user,
            'stats' => [
                'totalOutlets' => $totalOutlets,
                'totalEmployees' => $totalEmployees,
                'totalAttendances' => $totalAttendances,
                'todayAttendances' => $todayAttendances,
            ],
            'recentOutlets' => $recentOutlets,
            'recentAttendances' => $recentAttendances,
            'userRole' => 'owner'
        ]);
    }

    /**
     * Display employee dashboard.
     */
    private function employeeDashboard()
    {
        $user = Auth::user();
        
        // Debug logging
        \Log::info('Employee Dashboard loading', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'outlet_id' => $user->outlet_id
        ]);
        
        // Get today's attendance
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->first();

        // Get recent attendances
        $recentAttendances = Attendance::where('user_id', $user->id)
            ->with('outlet')
            ->latest()
            ->take(10)
            ->get();

        // Get user's outlet with operational status
        $outlet = $user->outlet;
        
        // Debug logging for outlet
        \Log::info('Outlet data retrieved', [
            'outlet_exists' => $outlet ? true : false,
            'outlet_id' => $outlet?->id,
            'outlet_name' => $outlet?->name
        ]);
        
        // Add operational status to outlet data
        if ($outlet) {
            $outlet->append([
                'operational_start_time_formatted',
                'operational_end_time_formatted',
                'formatted_operational_hours',
                'operational_status',
                'formatted_work_days',
                'tolerance_settings'
            ]);
            
            // Calculate current overtime for checkout validation
            $currentOvertimeMinutes = $outlet->calculateOvertime(now());
            $outlet->current_overtime_minutes = $currentOvertimeMinutes;
        }

        return inertia('Dashboard', [
            'user' => $user,
            'today_attendance' => $todayAttendance,
            'recentAttendances' => $recentAttendances,
            'outlet' => $outlet,
            'userRole' => 'employee'
        ]);
    }
}
