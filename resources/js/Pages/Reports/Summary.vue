<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";

const props = defineProps({
    summaryData: Array,
    outlets: Array,
    filters: Object,
});

const searchParams = ref({
    outlet_id: props.filters.outlet_id || "",
    date_from: props.filters.date_from || "",
    date_to: props.filters.date_to || "",
});

const backToReports = () => {
    const params = new URLSearchParams();
    Object.keys(searchParams.value).forEach((key) => {
        if (searchParams.value[key]) {
            params.append(key, searchParams.value[key]);
        }
    });

    window.location.href = route("reports.index") + "?" + params.toString();
};

const exportData = () => {
    const params = new URLSearchParams();
    Object.keys(searchParams.value).forEach((key) => {
        if (searchParams.value[key]) {
            params.append(key, searchParams.value[key]);
        }
    });

    window.location.href = route("reports.export") + "?" + params.toString();
};

// Calculate total statistics
const totalStats = computed(() => {
    return props.summaryData.reduce(
        (acc, outlet) => {
            acc.totalAttendances += outlet.total_attendances;
            acc.totalEmployees += outlet.total_employees;
            acc.totalHours += outlet.employee_stats.reduce(
                (sum, emp) => sum + emp.total_hours,
                0
            );
            return acc;
        },
        {
            totalAttendances: 0,
            totalEmployees: 0,
            totalHours: 0,
        }
    );
});
</script>

<template>
    <Head title="Ringkasan Laporan" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Ringkasan Laporan
                    </h1>
                    <p class="text-muted">Analisis komprehensif data absensi</p>
                </div>
                <div class="flex items-center space-x-4">
                    <Button @click="backToReports" variant="secondary">
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
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                        Kembali ke Laporan
                    </Button>
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

            <!-- Overall Statistics -->
            <Card>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-surface-2 rounded-lg p-6">
                        <div class="text-3xl font-bold text-primary">
                            {{ totalStats.totalAttendances }}
                        </div>
                        <div class="text-sm text-muted mt-2">Total Absensi</div>
                    </div>
                    <div class="bg-surface-2 rounded-lg p-6">
                        <div class="text-3xl font-bold text-info">
                            {{ totalStats.totalEmployees }}
                        </div>
                        <div class="text-sm text-muted mt-2">
                            Total Karyawan
                        </div>
                    </div>
                    <div class="bg-surface-2 rounded-lg p-6">
                        <div class="text-3xl font-bold text-success">
                            {{ Math.round(totalStats.totalHours) }}j
                        </div>
                        <div class="text-sm text-muted mt-2">
                            Total Jam Kerja
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Outlet Breakdown -->
            <div v-if="summaryData.length > 0" class="space-y-6">
                <Card
                    v-for="outletData in summaryData"
                    :key="outletData.outlet.id"
                >
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-text">
                            {{ outletData.outlet.name }}
                        </h3>
                        <div
                            class="flex items-center space-x-4 mt-2 text-sm text-muted"
                        >
                            <span>{{ outletData.outlet.address }}</span>
                            <span>•</span>
                            <span
                                >{{ outletData.total_employees }} karyawan</span
                            >
                            <span>•</span>
                            <span
                                >{{
                                    outletData.total_attendances
                                }}
                                absensi</span
                            >
                        </div>
                    </div>

                    <!-- Outlet Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-surface-2 rounded-lg p-4">
                            <div class="text-xl font-bold text-warning">
                                {{ outletData.total_attendances }}
                            </div>
                            <div class="text-sm text-muted">Total Absensi</div>
                        </div>
                        <div class="bg-surface-2 rounded-lg p-4">
                            <div class="text-xl font-bold text-info">
                                {{ outletData.total_employees }}
                            </div>
                            <div class="text-sm text-muted">
                                Jumlah Karyawan
                            </div>
                        </div>
                        <div class="bg-surface-2 rounded-lg p-4">
                            <div class="text-xl font-bold text-accent">
                                {{
                                    Math.round(
                                        outletData.employee_stats.reduce(
                                            (sum, emp) => sum + emp.total_hours,
                                            0
                                        )
                                    )
                                }}j
                            </div>
                            <div class="text-sm text-muted">
                                Total Jam Kerja
                            </div>
                        </div>
                    </div>

                    <!-- Employee Performance -->
                    <div v-if="outletData.employee_stats.length > 0">
                        <h4 class="text-lg font-medium text-text mb-4">
                            Performa Karyawan
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-surface-2">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider"
                                        >
                                            Karyawan
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider"
                                        >
                                            Total Absensi
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider"
                                        >
                                            Total Jam
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider"
                                        >
                                            Rata-rata Jam
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-surface-1 divide-y divide-border"
                                >
                                    <tr
                                        v-for="empStat in outletData.employee_stats"
                                        :key="empStat.employee.id"
                                        class="hover:bg-surface-2"
                                    >
                                        <td class="px-6 py-4">
                                            <div>
                                                <div
                                                    class="text-sm font-medium text-text"
                                                >
                                                    {{ empStat.employee.name }}
                                                </div>
                                                <div
                                                    class="text-sm text-text-3"
                                                >
                                                    {{ empStat.employee.email }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-text-3">
                                                {{ empStat.total_attendances }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-text-3">
                                                {{ empStat.total_hours }}j
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-text-3">
                                                {{ empStat.average_hours }}j
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else class="text-center py-8">
                        <svg
                            class="mx-auto h-12 w-12 text-text-3"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-text-3">
                            Tidak ada karyawan
                        </h3>
                        <p class="mt-1 text-sm text-muted">
                            Outlet ini belum memiliki karyawan.
                        </p>
                    </div>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <svg
                    class="mx-auto h-16 w-16 text-text-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v6a2 2 0 002 2zm6-4V7a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1z"
                    />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-text-3">
                    Tidak ada data laporan
                </h3>
                <p class="mt-2 text-muted">
                    Tidak ada data absensi yang sesuai dengan filter yang
                    dipilih.
                </p>
                <div class="mt-6">
                    <Button @click="backToReports" variant="secondary">
                        Kembali ke Laporan
                    </Button>
                </div>
            </div>
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
</style>
