# Implementasi Pivot Table untuk Laporan Absensi Karyawan

## Overview

Dokumen ini merinci implementasi pivot table untuk menampilkan laporan absensi detail per karyawan dengan format:

-   **Baris**: Nama karyawan
-   **Kolom**: Tanggal
-   **Nilai**: Status kehadiran (hadir, terlambat, izin, dll)

## 1. Service Layer - PivotTableService

### File: `app/Services/PivotTableService.php`

```php
<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Outlet;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PivotTableService
{
    /**
     * Generate pivot table data for attendance report
     */
    public function generatePivotTable($ownerId, $startDate, $endDate, $outletId = null)
    {
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
}
```

## 2. Controller Layer - ReportController

### Tambahkan method di `app/Http/Controllers/ReportController.php`:

```php
use App\Services\PivotTableService;

class ReportController extends Controller
{
    protected $pivotTableService;

    public function __construct(PivotTableService $pivotTableService)
    {
        $this->pivotTableService = $pivotTableService;
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
```

## 3. Routes

### Tambahkan di `routes/web.php`:

```php
// Owner routes
Route::middleware(['auth', 'owner'])->group(function () {
    // ... existing routes

    // Reports routes
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pivot', [App\Http\Controllers\ReportController::class, 'pivot'])->name('reports.pivot');
    Route::get('/reports/export', [App\Http\Controllers\ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/export-pivot', [App\Http\Controllers\ReportController::class, 'exportPivot'])->name('reports.export.pivot');
    Route::get('/reports/summary', [App\Http\Controllers\ReportController::class, 'summary'])->name('reports.summary');
});
```

## 4. Frontend Component - Pivot Table

### File: `resources/js/Pages/Reports/Pivot.vue`

```vue
<script setup>
import { ref, computed, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";

const props = defineProps({
    pivotData: Object,
    statusConfig: Object,
    outlets: Array,
    filters: Object,
});

const searchParams = ref({
    outlet_id: props.filters.outlet_id || "",
    date_from: props.filters.date_from || "",
    date_to: props.filters.date_to || "",
});

// Navigation
const navigateMonth = (direction) => {
    const currentDate = searchParams.value.date_from
        ? new Date(searchParams.value.date_from)
        : new Date();

    const newDate = new Date(currentDate);
    if (direction === "prev") {
        newDate.setMonth(newDate.getMonth() - 1);
    } else {
        newDate.setMonth(newDate.getMonth() + 1);
    }

    const firstDay = new Date(newDate.getFullYear(), newDate.getMonth(), 1);
    const lastDay = new Date(newDate.getFullYear(), newDate.getMonth() + 1, 0);

    searchParams.value.date_from = firstDay.toISOString().split("T")[0];
    searchParams.value.date_to = lastDay.toISOString().split("T")[0];

    applyFilters();
};

const applyFilters = () => {
    router.get(
        route("reports.pivot"),
        {
            ...searchParams.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const clearFilters = () => {
    searchParams.value = {
        outlet_id: "",
        date_from: "",
        date_to: "",
    };
    applyFilters();
};

const exportData = () => {
    const params = new URLSearchParams();
    Object.keys(searchParams.value).forEach((key) => {
        if (searchParams.value[key]) {
            params.append(key, searchParams.value[key]);
        }
    });

    window.location.href =
        route("reports.export.pivot") + "?" + params.toString();
};

// Computed properties
const monthYear = computed(() => {
    if (searchParams.value.date_from) {
        const date = new Date(searchParams.value.date_from);
        return date.toLocaleDateString("id-ID", {
            year: "numeric",
            month: "long",
        });
    }
    return "";
});

const totalStats = computed(() => {
    const summary = props.pivotData.summary.attendance_summary;
    const total = Object.values(summary).reduce((sum, count) => sum + count, 0);

    return {
        total,
        ...summary,
        attendance_rate:
            total > 0
                ? (
                      ((summary.on_time +
                          summary.late +
                          summary.early_checkout +
                          summary.overtime) /
                          total) *
                      100
                  ).toFixed(1)
                : 0,
    };
});

// Helper functions
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString("id-ID", { day: "numeric", month: "short" });
};

const isWeekend = (dateString) => {
    const date = new Date(dateString);
    return date.getDay() === 0 || date.getDay() === 6;
};

const getStatusStyle = (status) => {
    const config = props.statusConfig[status] || props.statusConfig["absent"];
    return {
        backgroundColor: config.bg_color,
        color: config.color,
    };
};
</script>

<template>
    <Head title="Pivot Table Laporan Absensi" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Pivot Table Absensi
                    </h1>
                    <p class="text-muted">
                        Laporan absensi detail per karyawan
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <Button @click="exportData" variant="primary">
                        <svg
                            class="w-4 h-4 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        Export CSV
                    </Button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <Card>
                    <div class="bg-surface-2 rounded-lg p-4">
                        <div class="text-xl font-bold text-primary">
                            {{ totalStats.total }}
                        </div>
                        <div class="text-sm text-muted">Total Record</div>
                    </div>
                </Card>
                <Card>
                    <div class="bg-surface-2 rounded-lg p-4">
                        <div class="text-xl font-bold text-success">
                            {{ totalStats.on_time }}
                        </div>
                        <div class="text-sm text-muted">Hadir Tepat Waktu</div>
                    </div>
                </Card>
                <Card>
                    <div class="bg-surface-2 rounded-lg p-4">
                        <div class="text-xl font-bold text-warning">
                            {{ totalStats.late }}
                        </div>
                        <div class="text-sm text-muted">Terlambat</div>
                    </div>
                </Card>
                <Card>
                    <div class="bg-surface-2 rounded-lg p-4">
                        <div class="text-xl font-bold text-danger">
                            {{ totalStats.absent }}
                        </div>
                        <div class="text-sm text-muted">Tidak Hadir</div>
                    </div>
                </Card>
                <Card>
                    <div class="bg-surface-2 rounded-lg p-4">
                        <div class="text-xl font-bold text-info">
                            {{ totalStats.attendance_rate }}%
                        </div>
                        <div class="text-sm text-muted">Tingkat Kehadiran</div>
                    </div>
                </Card>
            </div>

            <!-- Filters and Navigation -->
            <Card>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label
                            class="block text-text-3 text-sm font-medium mb-2"
                            >Outlet</label
                        >
                        <select
                            v-model="searchParams.outlet_id"
                            class="w-full px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="">Semua Outlet</option>
                            <option
                                v-for="outlet in outlets"
                                :key="outlet.id"
                                :value="outlet.id"
                            >
                                {{ outlet.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-text-3 text-sm font-medium mb-2"
                            >Dari Tanggal</label
                        >
                        <input
                            v-model="searchParams.date_from"
                            type="date"
                            class="w-full px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-text-3 text-sm font-medium mb-2"
                            >Sampai Tanggal</label
                        >
                        <input
                            v-model="searchParams.date_to"
                            type="date"
                            class="w-full px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>
                    <div class="flex items-end space-x-2">
                        <Button @click="applyFilters" variant="primary"
                            >Terapkan</Button
                        >
                        <Button @click="clearFilters" variant="secondary"
                            >Reset</Button
                        >
                    </div>
                    <div class="flex items-end space-x-2">
                        <Button
                            @click="navigateMonth('prev')"
                            variant="secondary"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                        </Button>
                        <Button
                            @click="navigateMonth('next')"
                            variant="secondary"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Status Legend -->
            <Card>
                <h3 class="text-lg font-semibold text-text mb-4">
                    Legenda Status
                </h3>
                <div class="flex flex-wrap gap-4">
                    <div
                        v-for="(config, status) in statusConfig"
                        :key="status"
                        class="flex items-center space-x-2"
                    >
                        <div
                            class="w-4 h-4 rounded flex items-center justify-center text-xs font-bold"
                            :style="getStatusStyle(status)"
                        >
                            {{ config.icon }}
                        </div>
                        <span class="text-sm text-text-3">{{
                            config.text
                        }}</span>
                    </div>
                </div>
            </Card>

            <!-- Pivot Table -->
            <Card>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-text">
                        Pivot Table - {{ monthYear }}
                    </h3>
                    <div class="text-sm text-muted">
                        {{ pivotData.summary.total_employees }} karyawan •
                        {{ pivotData.date_range.total_days }} hari
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <!-- Header Row -->
                        <thead class="bg-surface-2 sticky top-0">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider sticky left-0 bg-surface-2"
                                >
                                    Karyawan
                                </th>
                                <th
                                    v-for="date in pivotData.date_range.dates"
                                    :key="date"
                                    class="px-2 py-3 text-center text-xs font-medium text-text-3 uppercase tracking-wider min-w-[60px]"
                                    :class="{ 'bg-gray-100': isWeekend(date) }"
                                >
                                    <div>{{ formatDate(date) }}</div>
                                    <div class="text-xs text-muted">
                                        {{
                                            new Date(date).toLocaleDateString(
                                                "id-ID",
                                                { weekday: "short" }
                                            )
                                        }}
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface-1 divide-y divide-border">
                            <!-- Employee Rows -->
                            <tr
                                v-for="employee in pivotData.employees"
                                :key="employee.id"
                                class="hover:bg-surface-2"
                            >
                                <td
                                    class="px-4 py-3 sticky left-0 bg-surface-1 hover:bg-surface-2"
                                >
                                    <div>
                                        <div
                                            class="text-sm font-medium text-text"
                                        >
                                            {{ employee.name }}
                                        </div>
                                        <div class="text-xs text-text-3">
                                            {{ employee.outlet || "-" }}
                                        </div>
                                    </div>
                                </td>
                                <td
                                    v-for="date in pivotData.date_range.dates"
                                    :key="date"
                                    class="px-2 py-2 text-center"
                                    :class="{ 'bg-gray-50': isWeekend(date) }"
                                >
                                    <div
                                        v-if="employee.attendances[date]"
                                        class="w-8 h-8 mx-auto rounded flex items-center justify-center text-xs font-bold cursor-pointer hover:scale-110 transition-transform"
                                        :style="
                                            getStatusStyle(
                                                employee.attendances[date]
                                                    .status
                                            )
                                        "
                                        :title="`${
                                            statusConfig[
                                                employee.attendances[date]
                                                    .status
                                            ]?.text || 'Unknown'
                                        } - ${
                                            employee.attendances[date]
                                                .check_in_time || '-'
                                        } s/d ${
                                            employee.attendances[date]
                                                .check_out_time || '-'
                                        }`"
                                    >
                                        {{
                                            statusConfig[
                                                employee.attendances[date]
                                                    .status
                                            ]?.icon || "?"
                                        }}
                                    </div>
                                    <div
                                        v-else
                                        class="w-8 h-8 mx-auto rounded bg-gray-100 flex items-center justify-center text-xs text-gray-400"
                                    >
                                        -
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap");

h1 {
    font-family: "Oswald", sans-serif;
}

body {
    font-family: "Roboto", sans-serif;
}

/* Custom scrollbar for horizontal scroll */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
```

## 5. Navigation Update

### Update `resources/js/router/navRoutes.ts` untuk menambahkan link ke pivot table:

```typescript
// Add to the reports section
{
    name: 'Pivot Table',
    route: 'reports.pivot',
    icon: 'M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v6a2 2 0 002 2zm6-4V7a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1z',
},
```

## 6. Update Index Reports Page

### Update `resources/js/Pages/Reports/Index.vue` untuk menambahkan tombol Pivot Table:

```vue
<!-- Add to the header buttons section -->
<Button @click="viewPivot" variant="secondary">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
    Pivot Table
</Button>

// Add to script section const viewPivot = () => { const params = new
URLSearchParams(); Object.keys(searchParams.value).forEach((key) => { if
(searchParams.value[key]) { params.append(key, searchParams.value[key]); } });
window.location.href = route("reports.pivot") + "?" + params.toString(); };
```

## 7. Performance Optimizations

### Database Indexes

Pastikan ada index yang tepat untuk query pivot table:

```sql
-- Add indexes for better performance
ALTER TABLE attendances ADD INDEX idx_user_checkin_date (user_id, check_in_date);
ALTER TABLE attendances ADD INDEX idx_attendance_status (attendance_status);
ALTER TABLE users ADD INDEX idx_role_outlet (role, outlet_id);
ALTER TABLE outlets ADD INDEX idx_owner_id (owner_id);
```

### Caching Strategy

Tambahkan caching untuk data yang sering diakses:

```php
// In PivotTableService
public function generatePivotTable($ownerId, $startDate, $endDate, $outletId = null)
{
    $cacheKey = "pivot_table_{$ownerId}_{$startDate}_{$endDate}_{$outletId}";

    return Cache::remember($cacheKey, 300, function () use ($ownerId, $startDate, $endDate, $outletId) {
        // ... existing implementation
    });
}
```

## 8. Testing Strategy

### Unit Tests

Buat test untuk service dan controller:

```php
// tests/Feature/PivotTableTest.php
class PivotTableTest extends TestCase
{
    public function test_pivot_table_generation()
    {
        // Test pivot table data generation
    }

    public function test_pivot_table_export()
    {
        // Test CSV export functionality
    }
}
```

### Frontend Tests

Buat component test untuk Vue:

```javascript
// tests/js/components/PivotTable.test.js
describe("PivotTable", () => {
    it("renders pivot table correctly", () => {
        // Test component rendering
    });

    it("handles date navigation", () => {
        // Test month navigation
    });
});
```

## 9. Deployment Considerations

### Memory Usage

Pivot table bisa memakan banyak memory untuk rentang tanggal yang luas. Pertimbangkan:

-   Pagination untuk data yang besar
-   Lazy loading untuk kolom tanggal
-   Limit maksimal rentang tanggal (misal: max 3 bulan)

### Browser Performance

-   Virtual scrolling untuk tabel besar
-   Debounce untuk filter changes
-   Web Workers untuk data processing

## 10. Future Enhancements

### Advanced Features

-   Drill-down capability (klik cell untuk detail attendance)
-   Heat map visualization
-   Comparison mode (bandingkan periode berbeda)
-   Real-time updates dengan WebSocket
-   Export ke Excel dengan formatting
-   Custom pivot table builder (drag & drop fields)

### Mobile Responsiveness

-   Horizontal scroll dengan swipe gesture
-   Collapsible columns
-   Mobile-optimized view
