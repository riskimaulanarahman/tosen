<script setup>
import { ref, computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";

const props = defineProps({
    employee: Object,
});

const deleteForm = useForm({});

const deleteEmployee = () => {
    if (
        confirm(
            `Apakah Anda yakin ingin menghapus karyawan ${props.employee.name}?`
        )
    ) {
        deleteForm.delete(route("employees.destroy", props.employee.id));
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatDuration = (attendance) => {
    if (!attendance.check_out_time) return "Still checked in";

    const checkin = new Date(attendance.check_in_time);
    const checkout = new Date(attendance.check_out_time);
    const diff = checkout - checkin;

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    return `${hours}h ${minutes}m`;
};

const attendanceStats = computed(() => {
    const attendances = props.employee.attendances || [];
    const total = attendances.length;
    const thisMonth = attendances.filter((a) => {
        const date = new Date(a.created_at);
        const now = new Date();
        return (
            date.getMonth() === now.getMonth() &&
            date.getFullYear() === now.getFullYear()
        );
    }).length;

    const averageDuration =
        attendances
            .filter((a) => a.check_out_time)
            .reduce((acc, a) => {
                const checkin = new Date(a.check_in_time);
                const checkout = new Date(a.check_out_time);
                return acc + (checkout - checkin);
            }, 0) / attendances.filter((a) => a.check_out_time).length || 0;

    const avgHours = Math.floor(averageDuration / (1000 * 60 * 60));
    const avgMinutes = Math.floor(
        (averageDuration % (1000 * 60 * 60)) / (1000 * 60)
    );

    return {
        total,
        thisMonth,
        averageDuration: avgHours > 0 ? `${avgHours}h ${avgMinutes}m` : "0h 0m",
    };
});
</script>

<template>
    <Head title="Employee Details" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Detail Karyawan
                    </h1>
                    <p class="text-muted">
                        Informasi lengkap: {{ employee.name }}
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <Link :href="route('employees.edit', employee.id)">
                        <Button variant="secondary">
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
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4h-4v4zm0 0h6v2H6v-2z"
                                />
                            </svg>
                            Edit
                        </Button>
                    </Link>
                    <Link :href="route('employees.index')">
                        <Button variant="secondary">
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
                            Kembali
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Employee Info Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Personal Information -->
                <Card>
                    <h3 class="text-lg font-semibold text-text mb-4">
                        Informasi Personal
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <span class="text-text-3 text-sm"
                                >Nama Lengkap</span
                            >
                            <div class="text-text font-medium">
                                {{ employee.name }}
                            </div>
                        </div>
                        <div>
                            <span class="text-text-3 text-sm">Email</span>
                            <div class="text-text font-medium">
                                {{ employee.email }}
                            </div>
                        </div>
                        <div>
                            <span class="text-text-3 text-sm"
                                >Status Email</span
                            >
                            <div class="mt-1">
                                <span
                                    v-if="employee.email_verified_at"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success"
                                >
                                    ✓ Terverifikasi
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning"
                                >
                                    ⚠ Belum Verifikasi
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="text-text-3 text-sm">ID Karyawan</span>
                            <div class="text-text font-medium">
                                #{{ employee.id }}
                            </div>
                        </div>
                        <div>
                            <span class="text-text-3 text-sm"
                                >Tanggal Bergabung</span
                            >
                            <div class="text-text font-medium">
                                {{ formatDate(employee.created_at) }}
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Outlet Information -->
                <Card>
                    <h3 class="text-lg font-semibold text-text mb-4">
                        Informasi Outlet
                    </h3>
                    <div v-if="employee.outlet" class="space-y-3">
                        <div>
                            <span class="text-text-3 text-sm">Nama Outlet</span>
                            <div class="text-text font-medium">
                                {{ employee.outlet.name }}
                            </div>
                        </div>
                        <div>
                            <span class="text-text-3 text-sm">Alamat</span>
                            <div class="text-text text-sm">
                                {{ employee.outlet.address }}
                            </div>
                        </div>
                        <div>
                            <span class="text-text-3 text-sm"
                                >Radius Geofence</span
                            >
                            <div class="text-text font-medium">
                                {{ employee.outlet.radius }} meter
                            </div>
                        </div>
                        <div>
                            <span class="text-text-3 text-sm">Koordinat</span>
                            <div class="text-text text-sm">
                                {{ employee.outlet.latitude }},
                                {{ employee.outlet.longitude }}
                            </div>
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
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-text-3">
                            Belum ada outlet
                        </h3>
                        <p class="mt-1 text-sm text-muted">
                            Karyawan ini belum ditugaskan ke outlet mana pun.
                        </p>
                    </div>
                </Card>

                <!-- Attendance Statistics -->
                <Card>
                    <h3 class="text-lg font-semibold text-text mb-4">
                        Statistik Absensi
                    </h3>
                    <div class="space-y-4">
                        <div class="bg-surface-2 rounded-lg p-4">
                            <div class="text-2xl font-bold text-primary">
                                {{ attendanceStats.total }}
                            </div>
                            <div class="text-sm text-text-3">Total Absensi</div>
                        </div>
                        <div class="bg-surface-2 rounded-lg p-4">
                            <div class="text-2xl font-bold text-info">
                                {{ attendanceStats.thisMonth }}
                            </div>
                            <div class="text-sm text-text-3">
                                Absensi Bulan Ini
                            </div>
                        </div>
                        <div class="bg-surface-2 rounded-lg p-4">
                            <div class="text-lg font-bold text-success">
                                {{ attendanceStats.averageDuration }}
                            </div>
                            <div class="text-sm text-text-3">
                                Rata-rata Durasi
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Recent Attendances -->
            <Card>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-text">
                        Riwayat Absensi
                    </h3>
                    <div class="text-sm text-muted">
                        Menampilkan
                        {{ employee.attendances?.length || 0 }} record terbaru
                    </div>
                </div>

                <div
                    v-if="employee.attendances?.length > 0"
                    class="overflow-x-auto"
                >
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-surface-2">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider"
                                >
                                    Tanggal
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider"
                                >
                                    Check In
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider"
                                >
                                    Check Out
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider"
                                >
                                    Durasi
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface-1 divide-y divide-border">
                            <tr
                                v-for="attendance in employee.attendances"
                                :key="attendance.id"
                                class="hover:bg-surface-2"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-text-3">
                                        {{
                                            new Date(
                                                attendance.created_at
                                            ).toLocaleDateString("id-ID")
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-success">
                                        {{
                                            new Date(
                                                attendance.check_in_time
                                            ).toLocaleTimeString("id-ID")
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div
                                        v-if="attendance.check_out_time"
                                        class="text-sm text-warning"
                                    >
                                        {{
                                            new Date(
                                                attendance.check_out_time
                                            ).toLocaleTimeString("id-ID")
                                        }}
                                    </div>
                                    <div v-else class="text-sm text-muted">
                                        -
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-text-3">
                                        {{ formatDuration(attendance) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="
                                            attendance.status === 'checked_in'
                                                ? 'bg-success-100 text-success'
                                                : 'bg-surface-3 text-text-3'
                                        "
                                    >
                                        {{
                                            attendance.status === "checked_in"
                                                ? "Check-in"
                                                : "Selesai"
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="text-center py-12">
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
                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012 2h2a2 2 0 012 2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-text-3">
                        Tidak ada data absensi
                    </h3>
                    <p class="mt-1 text-sm text-muted">
                        Karyawan ini belum melakukan absensi.
                    </p>
                </div>
            </Card>

            <!-- Danger Zone -->
            <Card class="border-error">
                <h3 class="text-lg font-semibold text-error mb-4">
                    Zone Berbahaya
                </h3>
                <div class="bg-error-50 border border-error rounded-lg p-4">
                    <p class="text-sm text-text mb-4">
                        Menghapus karyawan akan menghapus semua data terkait.
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <Button
                        @click="deleteEmployee"
                        :disabled="deleteForm.processing"
                        variant="danger"
                    >
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
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 6h16"
                            />
                        </svg>
                        <span v-if="deleteForm.processing">Menghapus...</span>
                        <span v-else>Hapus Karyawan</span>
                    </Button>
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
</style>
