<script setup>
import { ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import SelfieModal from "@/Components/SelfieModal.vue";

const props = defineProps({
    attendances: Object,
    outlets: Array,
    stats: Object,
    filters: Object,
});

const searchParams = ref({
    outlet_id: props.filters.outlet_id || "",
    date_from: props.filters.date_from || "",
    date_to: props.filters.date_to || "",
});

const applyFilters = () => {
    router.get(
        route("reports.index"),
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

    window.location.href = route("reports.export") + "?" + params.toString();
};

const viewSummary = () => {
    const params = new URLSearchParams();
    Object.keys(searchParams.value).forEach((key) => {
        if (searchParams.value[key]) {
            params.append(key, searchParams.value[key]);
        }
    });

    window.location.href = route("reports.summary") + "?" + params.toString();
};

const viewPivot = () => {
    const params = new URLSearchParams();
    Object.keys(searchParams.value).forEach((key) => {
        if (searchParams.value[key]) {
            params.append(key, searchParams.value[key]);
        }
    });

    window.location.href = route("reports.pivot") + "?" + params.toString();
};

const viewSelfieFeed = () => {
    const params = new URLSearchParams();
    Object.keys(searchParams.value).forEach((key) => {
        if (searchParams.value[key]) {
            params.append(key, searchParams.value[key]);
        }
    });

    const query = params.toString();
    window.location.href =
        route("reports.selfies") + (query ? `?${query}` : "");
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const formatTime = (time) => {
    return new Date(time).toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatDuration = (attendance) => {
    if (!attendance.check_out_time) return "Masih check-in";

    const checkin = new Date(attendance.check_in_time);
    const checkout = new Date(attendance.check_out_time);
    const diff = checkout - checkin;

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    return `${hours}j ${minutes}m`;
};

const getStatusClass = (status) => {
    switch (status) {
        case "checked_in":
            return "bg-success-100 text-success";
        case "checked_out":
            return "bg-surface-3 text-text-3";
        default:
            return "bg-surface-3 text-text-3";
    }
};

const getStatusText = (status) => {
    switch (status) {
        case "checked_in":
            return "Check-in";
        case "checked_out":
            return "Selesai";
        default:
            return status;
    }
};

// Selfie modal state
const showSelfieModal = ref(false);
const selectedAttendance = ref(null);

const openSelfieModal = (attendance) => {
    selectedAttendance.value = attendance;
    showSelfieModal.value = true;
};

const closeSelfieModal = () => {
    showSelfieModal.value = false;
    selectedAttendance.value = null;
};

const hasSelfie = (attendance) => {
    return attendance.check_in_selfie_url || attendance.check_out_selfie_url;
};
</script>

<template>
    <Head title="Laporan Absensi" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Laporan Absensi
                    </h1>
                    <p class="text-muted">Analisis data absensi karyawan</p>
                </div>
                <div class="flex items-center space-x-4">
                    <Button @click="viewSelfieFeed" variant="secondary">
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
                                d="M3 7h4l2-3h6l2 3h4v12H3z"
                            />
                            <circle
                                cx="12"
                                cy="13"
                                r="3"
                                stroke="currentColor"
                                stroke-width="2"
                                fill="none"
                            />
                        </svg>
                        Mode Selfie
                    </Button>
                    <Button @click="viewPivot" variant="secondary">
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
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                        Pivot Table
                    </Button>
                    <Button @click="viewSummary" variant="secondary">
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
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                            />
                        </svg>
                        Ringkasan
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

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card>
                    <div class="bg-surface-2 rounded-lg p-4">
                        <div class="text-2xl font-bold text-primary">
                            {{ stats.total_attendances }}
                        </div>
                        <div class="text-sm text-muted">Total Absensi</div>
                    </div>
                </Card>
                <Card>
                    <div class="bg-surface-2 rounded-lg p-4">
                        <div class="text-2xl font-bold text-info">
                            {{ stats.total_employees }}
                        </div>
                        <div class="text-sm text-muted">Total Karyawan</div>
                    </div>
                </Card>
                <Card>
                    <div class="bg-surface-2 rounded-lg p-4">
                        <div class="text-2xl font-bold text-success">
                            {{ stats.total_outlets }}
                        </div>
                        <div class="text-sm text-muted">Total Outlet</div>
                    </div>
                </Card>
                <Card>
                    <div class="bg-surface-2 rounded-lg p-4">
                        <div class="text-2xl font-bold text-warning">
                            {{ stats.this_month_attendances }}
                        </div>
                        <div class="text-sm text-muted">Absensi Bulan Ini</div>
                    </div>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label
                            class="block text-text-3 text-sm font-medium mb-2"
                        >
                            Outlet
                        </label>
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
                        >
                            Dari Tanggal
                        </label>
                        <input
                            v-model="searchParams.date_from"
                            type="date"
                            class="w-full px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-text-3 text-sm font-medium mb-2"
                        >
                            Sampai Tanggal
                        </label>
                        <input
                            v-model="searchParams.date_to"
                            type="date"
                            class="w-full px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>
                    <div class="flex items-end space-x-2">
                        <Button @click="applyFilters" variant="primary">
                            Terapkan
                        </Button>
                        <Button @click="clearFilters" variant="secondary">
                            Reset
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Attendance Table -->
            <Card>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-text">
                        Data Absensi
                    </h3>
                    <div class="text-sm text-muted">
                        Menampilkan {{ attendances.data?.length || 0 }} record
                    </div>
                </div>

                <div
                    v-if="attendances.data?.length > 0"
                    class="overflow-x-auto"
                >
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
                                    Outlet
                                </th>
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
                                    Foto Selfie
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
                                v-for="attendance in attendances.data"
                                :key="attendance.id"
                                class="hover:bg-surface-2"
                            >
                                <td class="px-6 py-4">
                                    <div>
                                        <div
                                            class="text-sm font-medium text-text"
                                        >
                                            {{ attendance.user.name }}
                                        </div>
                                        <div class="text-sm text-text-3">
                                            {{ attendance.user.email }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-text-3">
                                        {{
                                            attendance.user.outlet?.name || "-"
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-text-3">
                                        {{ formatDate(attendance.created_at) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-success">
                                        {{
                                            formatTime(attendance.check_in_time)
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        v-if="attendance.check_out_time"
                                        class="text-sm text-warning"
                                    >
                                        {{
                                            formatTime(
                                                attendance.check_out_time
                                            )
                                        }}
                                    </div>
                                    <div v-else class="text-sm text-muted">
                                        -
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-text-3">
                                        {{ formatDuration(attendance) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        v-if="hasSelfie(attendance)"
                                        class="flex items-center space-x-2"
                                    >
                                        <button
                                            @click="openSelfieModal(attendance)"
                                            class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-primary/10 text-primary hover:bg-primary/20 transition-colors"
                                        >
                                            <svg
                                                class="w-4 h-4 mr-1"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                />
                                            </svg>
                                            Lihat Foto
                                        </button>
                                        <div
                                            class="flex items-center space-x-1 text-xs text-text-3"
                                        >
                                            <svg
                                                v-if="
                                                    attendance.check_in_selfie_url
                                                "
                                                class="w-3 h-3 text-success"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            <svg
                                                v-if="
                                                    attendance.check_out_selfie_url
                                                "
                                                class="w-3 h-3 text-success"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-muted">
                                        <svg
                                            class="w-4 h-4 inline mr-1"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>
                                        Tidak ada foto
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="
                                            getStatusClass(attendance.status)
                                        "
                                    >
                                        {{ getStatusText(attendance.status) }}
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
                            d="M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v6a2 2 0 002 2zm6-4V7a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-text-3">
                        Tidak ada data
                    </h3>
                    <p class="mt-1 text-sm text-muted">
                        Tidak ada data absensi yang sesuai dengan filter yang
                        dipilih.
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="attendances.links" class="mt-6">
                    <template
                        v-for="(link, key) in attendances.links"
                        :key="key"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-2 text-sm border rounded-none',
                                link.active
                                    ? 'bg-primary text-white border-primary'
                                    : 'bg-surface-1 text-text border-border hover:bg-surface-2',
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-2 text-sm text-muted border border-border rounded-none"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </Card>

            <!-- Selfie Modal -->
            <SelfieModal
                :show="showSelfieModal"
                :attendance="selectedAttendance"
                @close="closeSelfieModal"
            />
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
