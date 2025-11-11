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

const getAttendanceTooltip = (attendance) => {
    if (!attendance) return "Tidak ada data";

    const config = props.statusConfig[attendance.status] || { text: "Unknown" };
    let tooltip = `${config.text}`;

    if (attendance.check_in_time) {
        tooltip += `\nCheck-in: ${attendance.check_in_time}`;
    }
    if (attendance.check_out_time) {
        tooltip += `\nCheck-out: ${attendance.check_out_time}`;
    }
    if (
        attendance.work_duration &&
        attendance.work_duration !== "Still checked in"
    ) {
        tooltip += `\nDurasi: ${attendance.work_duration}`;
    }
    if (attendance.late_minutes > 0) {
        tooltip += `\nTerlambat: ${attendance.late_minutes} menit`;
    }
    if (attendance.early_checkout_minutes > 0) {
        tooltip += `\nPulang awal: ${attendance.early_checkout_minutes} menit`;
    }
    if (attendance.overtime_minutes > 0) {
        tooltip += `\nLembur: ${attendance.overtime_minutes} menit`;
    }

    return tooltip;
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
                                    class="px-4 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider sticky left-0 bg-surface-2 z-10"
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
                                    class="px-4 py-3 sticky left-0 bg-surface-1 hover:bg-surface-2 z-10"
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
                                        :title="
                                            getAttendanceTooltip(
                                                employee.attendances[date]
                                            )
                                        "
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

/* Sticky column and header styles */
.sticky {
    position: sticky;
}

.left-0 {
    left: 0;
}

.top-0 {
    top: 0;
}

.z-10 {
    z-index: 10;
}

/* Hover effects */
.hover\:scale-110:hover {
    transform: scale(1.1);
}

.transition-transform {
    transition: transform 0.2s ease-in-out;
}
</style>
