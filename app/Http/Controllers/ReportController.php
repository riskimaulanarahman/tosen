<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display a listing of the reports.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get owner's outlets
        $outlets = Outlet::where('owner_id', $user->id)
            ->orderBy('name')
            ->get();

        // Get filters
        $selectedOutlet = $request->get('outlet_id');
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();

        // Build query
        $attendancesQuery = Attendance::whereHas('user', function($query) use ($user) {
            $query->whereHas('outlet', function($subQuery) use ($user) {
                $subQuery->where('owner_id', $user->id);
            });
        })->with(['user', 'user.outlet'])
        ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($selectedOutlet) {
            $attendancesQuery->whereHas('user.outlet', function($query) use ($selectedOutlet) {
                $query->where('id', $selectedOutlet);
            });
        }

        $attendances = $attendancesQuery->orderBy('created_at', 'desc')
            ->paginate(50);

        // Statistics
        $stats = [
            'total_attendances' => $attendancesQuery->count(),
            'total_employees' => User::whereHas('outlet', function($query) use ($user) {
                $query->where('owner_id', $user->id);
            })->where('role', 'employee')->count(),
            'total_outlets' => $outlets->count(),
            'this_month_attendances' => Attendance::whereHas('user', function($query) use ($user) {
                $query->whereHas('outlet', function($subQuery) use ($user) {
                    $subQuery->where('owner_id', $user->id);
                });
            })->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count(),
        ];

        return inertia('Reports/Index', [
            'attendances' => $attendances,
            'outlets' => $outlets,
            'stats' => $stats,
            'filters' => [
                'outlet_id' => $selectedOutlet,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Export attendance data to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        // Get filters
        $selectedOutlet = $request->get('outlet_id');
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();

        // Build query
        $attendancesQuery = Attendance::whereHas('user', function($query) use ($user) {
            $query->whereHas('outlet', function($subQuery) use ($user) {
                $subQuery->where('owner_id', $user->id);
            });
        })->with(['user', 'user.outlet'])
        ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($selectedOutlet) {
            $attendancesQuery->whereHas('user.outlet', function($query) use ($selectedOutlet) {
                $query->where('id', $selectedOutlet);
            });
        }

        $attendances = $attendancesQuery->orderBy('created_at', 'desc')->get();

        // Generate CSV
        $filename = "laporan_absensi_" . date('Y-m-d_H-i-s') . ".csv";
        $handle = fopen('php://memory', 'r+');

        // CSV headers
        fputcsv($handle, [
            'ID',
            'Nama Karyawan',
            'Email',
            'Outlet',
            'Tanggal',
            'Check In',
            'Check Out',
            'Status',
        ]);

        // CSV data
        foreach ($attendances as $attendance) {
            fputcsv($handle, [
                $attendance->id,
                $attendance->user->name,
                $attendance->user->email,
                $attendance->user->outlet->name,
                $attendance->created_at->format('Y-m-d'),
                $attendance->check_in_time->format('H:i:s'),
                $attendance->check_out_time ? $attendance->check_out_time->format('H:i:s') : '-',
                $attendance->status,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Generate summary report
     */
    public function summary(Request $request)
    {
        $user = Auth::user();
        
        // Get filters
        $selectedOutlet = $request->get('outlet_id');
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();

        // Get outlets
        $outlets = Outlet::where('owner_id', $user->id)
            ->orderBy('name')
            ->get();

        $summaryData = [];

        foreach ($outlets as $outlet) {
            if ($selectedOutlet && $selectedOutlet != $outlet->id) {
                continue;
            }

            $employees = User::where('outlet_id', $outlet->id)
                ->where('role', 'employee')
                ->get();

            $totalAttendances = Attendance::whereHas('user', function($query) use ($outlet) {
                $query->where('outlet_id', $outlet->id);
            })->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

            $employeeStats = [];
            foreach ($employees as $employee) {
                $attendances = Attendance::where('user_id', $employee->id)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->get();

                $totalHours = 0;
                foreach ($attendances as $attendance) {
                    if ($attendance->check_out_time) {
                        $checkin = Carbon::parse($attendance->check_in_time);
                        $checkout = Carbon::parse($attendance->check_out_time);
                        $totalHours += $checkout->diffInMinutes($checkin) / 60;
                    }
                }

                $employeeStats[] = [
                    'employee' => $employee,
                    'total_attendances' => $attendances->count(),
                    'total_hours' => round($totalHours, 2),
                    'average_hours' => $attendances->count() > 0 ? round($totalHours / $attendances->count(), 2) : 0,
                ];
            }

            $summaryData[] = [
                'outlet' => $outlet,
                'total_attendances' => $totalAttendances,
                'total_employees' => $employees->count(),
                'employee_stats' => $employeeStats,
            ];
        }

        return inertia('Reports/Summary', [
            'summaryData' => $summaryData,
            'outlets' => $outlets,
            'filters' => [
                'outlet_id' => $selectedOutlet,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
            ],
        ]);
    }
}
