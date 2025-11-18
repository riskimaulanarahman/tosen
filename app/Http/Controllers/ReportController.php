<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\User;
use App\Models\Attendance;
use App\Services\PivotTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected $pivotTableService;

    public function __construct(PivotTableService $pivotTableService)
    {
        $this->pivotTableService = $pivotTableService;
    }

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

        // Append selfie URLs to attendance records
        $attendances->getCollection()->transform(function ($attendance) {
            $attendance->append([
                'check_in_selfie_url',
                'check_out_selfie_url',
                'check_in_thumbnail_url',
                'check_out_thumbnail_url',
                'check_in_file_size_formatted',
                'check_out_file_size_formatted',
                'selfie_deletion_status'
            ]);
            return $attendance;
        });

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

    /**
     * Provide a simplified selfie review feed for owners.
     */
    public function selfieFeed(Request $request)
    {
        $user = Auth::user();

        $filters = [
            'outlet_id' => $request->input('outlet_id'),
            'date' => $request->input('date'),
            'search' => $request->input('search'),
        ];

        $outlets = Outlet::where('owner_id', $user->id)
            ->orderBy('name')
            ->get();

        $baseQuery = Attendance::with(['user', 'user.outlet'])
            ->whereHas('user', function ($query) use ($user) {
                $query->whereHas('outlet', function ($subQuery) use ($user) {
                    $subQuery->where('owner_id', $user->id);
                });
            })
            ->where(function ($query) {
                $query->whereNotNull('check_in_selfie_path')
                    ->orWhereNotNull('check_out_selfie_path');
            });

        if (!empty($filters['outlet_id'])) {
            $baseQuery->whereHas('user', function ($query) use ($filters) {
                $query->where('outlet_id', $filters['outlet_id']);
            });
        }

        if (!empty($filters['date'])) {
            $date = Carbon::parse($filters['date']);
            $baseQuery->whereBetween('check_in_time', [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ]);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $baseQuery->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $attendances = (clone $baseQuery)
            ->orderByDesc('check_in_time')
            ->paginate(24);

        $attendances->getCollection()->transform(function ($attendance) {
            $attendance->append([
                'check_in_selfie_url',
                'check_out_selfie_url',
                'check_in_thumbnail_url',
                'check_out_thumbnail_url',
                'check_in_file_size_formatted',
                'check_out_file_size_formatted',
                'selfie_deletion_status',
            ]);

            return [
                'id' => $attendance->id,
                'user' => [
                    'id' => $attendance->user->id,
                    'name' => $attendance->user->name,
                    'email' => $attendance->user->email,
                    'outlet' => optional($attendance->user->outlet)->name,
                ],
                'check_in_time' => optional($attendance->check_in_time)?->toIso8601String(),
                'check_out_time' => optional($attendance->check_out_time)?->toIso8601String(),
                'check_in_selfie_url' => $attendance->check_in_selfie_url,
                'check_out_selfie_url' => $attendance->check_out_selfie_url,
                'check_in_thumbnail_url' => $attendance->check_in_thumbnail_url,
                'check_out_thumbnail_url' => $attendance->check_out_thumbnail_url,
                'check_in_file_size_formatted' => $attendance->check_in_file_size_formatted,
                'check_out_file_size_formatted' => $attendance->check_out_file_size_formatted,
                'selfie_deletion_status' => $attendance->selfie_deletion_status,
            ];
        });

        $statsQuery = clone $baseQuery;
        $stats = [
            'total_records' => (clone $statsQuery)->count(),
            'with_check_in_selfie' => (clone $statsQuery)->whereNotNull('check_in_selfie_path')->count(),
            'with_check_out_selfie' => (clone $statsQuery)->whereNotNull('check_out_selfie_path')->count(),
            'with_both' => (clone $statsQuery)
                ->whereNotNull('check_in_selfie_path')
                ->whereNotNull('check_out_selfie_path')
                ->count(),
        ];

        return inertia('Reports/SelfieFeed', [
            'attendances' => $attendances,
            'outlets' => $outlets,
            'stats' => $stats,
            'filters' => [
                'outlet_id' => $filters['outlet_id'],
                'date' => $filters['date'],
                'search' => $filters['search'],
            ],
        ]);
    }

    /**
     * Display pivot table attendance report
     */
    public function pivot(Request $request)
    {
        $user = Auth::user();
        
        // Get filters
        $selectedOutlet = $request->get('outlet_id');
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();

        // Get owner's outlets
        $outlets = Outlet::where('owner_id', $user->id)
            ->orderBy('name')
            ->get();

        // Generate pivot table data
        $pivotData = $this->pivotTableService->generatePivotTable(
            $user->id,
            $dateFrom->format('Y-m-d'),
            $dateTo->format('Y-m-d'),
            $selectedOutlet
        );

        $statusConfig = $this->pivotTableService->getStatusConfig();

        return inertia('Reports/Pivot', [
            'pivotData' => $pivotData,
            'statusConfig' => $statusConfig,
            'outlets' => $outlets,
            'filters' => [
                'outlet_id' => $selectedOutlet,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Export pivot table to CSV
     */
    public function exportPivot(Request $request)
    {
        $user = Auth::user();
        
        // Get filters
        $selectedOutlet = $request->get('outlet_id');
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();

        // Generate pivot table data
        $pivotData = $this->pivotTableService->generatePivotTable(
            $user->id,
            $dateFrom->format('Y-m-d'),
            $dateTo->format('Y-m-d'),
            $selectedOutlet
        );

        $statusConfig = $this->pivotTableService->getStatusConfig();
        $exportData = $this->pivotTableService->exportToCsv($pivotData, $statusConfig);

        return response($exportData['content'])
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $exportData['filename'] . '"');
    }
}
