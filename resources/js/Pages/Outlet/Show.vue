<script setup>
import { ref, onMounted, watch, nextTick } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import AddEmployeeModal from "@/Components/AddEmployeeModal.vue";
import OperationalStatus from "@/Components/OperationalStatus.vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

const props = defineProps({
    outlet: Object,
});

const page = usePage();
const successMessage = ref(page.props.flash?.success);
const errorMessage = ref(page.props.flash?.error);

const showEmployeeModal = ref(false);

const openEmployeeModal = () => {
    showEmployeeModal.value = true;
};

const closeEmployeeModal = () => {
    showEmployeeModal.value = false;
};

const formatTime = (time) => {
    return new Date(time).toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const calculateWorkDuration = (checkinTime, checkoutTime) => {
    if (!checkinTime || !checkoutTime) return "-";

    const checkin = new Date(checkinTime);
    const checkout = new Date(checkoutTime);
    const duration = checkout - checkin;

    const hours = Math.floor(duration / (1000 * 60 * 60));
    const minutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));

    return `${hours}j ${minutes}m`;
};

const getStatusBadge = (status) => {
    if (status === "checked_in") {
        return {
            class: "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success border border-success",
            text: "Sedang Check-In",
        };
    } else {
        return {
            class: "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-surface-3 text-text-2 border border-border",
            text: "Selesai",
        };
    }
};

// Breadcrumb items
const breadcrumbItems = [
    { name: "Dashboard", href: route("dashboard") },
    { name: "Manajemen Outlet", href: route("outlets.index") },
    { name: props.outlet.name },
];

const mapRef = ref(null);
let mapInstance = null;
let markerInstance = null;
let circleInstance = null;

const initMap = () => {
    if (!mapRef.value) return;

    const lat = parseFloat(props.outlet.latitude);
    const lng = parseFloat(props.outlet.longitude);

    if (Number.isNaN(lat) || Number.isNaN(lng)) return;

    if (!mapInstance) {
        mapInstance = L.map(mapRef.value, { zoomControl: true }).setView(
            [lat, lng],
            15
        );

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(mapInstance);
    } else {
        mapInstance.setView([lat, lng], 15);
    }

    if (markerInstance) {
        markerInstance.setLatLng([lat, lng]);
    } else {
        markerInstance = L.marker([lat, lng]).addTo(mapInstance);
    }

    if (circleInstance) {
        circleInstance.setLatLng([lat, lng]);
        circleInstance.setRadius(props.outlet.radius || 0);
    } else if (props.outlet.radius) {
        circleInstance = L.circle([lat, lng], {
            radius: props.outlet.radius,
            color: "#2563eb",
            fillColor: "#3b82f6",
            fillOpacity: 0.15,
        }).addTo(mapInstance);
    }

    if (mapInstance) {
        nextTick(() => {
            mapInstance.invalidateSize();
        });
    }
};

onMounted(() => {
    initMap();
});

watch(
    () => [props.outlet.latitude, props.outlet.longitude, props.outlet.radius],
    () => {
        initMap();
    }
);
</script>

<template>
    <Head :title="`${outlet.name} - Detail`" />

    <AuthenticatedLayout>
        <template #breadcrumb>
            <Breadcrumb :items="breadcrumbItems" />
        </template>

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        {{ outlet.name }}
                    </h1>
                    <p class="text-muted">
                        Detail informasi dan aktivitas outlet
                    </p>
                </div>
                <Link :href="route('outlets.index')">
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
                        Kembali ke Daftar
                    </Button>
                </Link>
            </div>

            <!-- Success/Error Messages -->
            <div
                v-if="successMessage"
                class="bg-success-100 border border-success rounded-lg p-4"
            >
                <div class="flex">
                    <svg
                        class="w-5 h-5 text-success mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <div class="text-success">{{ successMessage }}</div>
                </div>
            </div>

            <div
                v-if="errorMessage"
                class="bg-error-100 border border-error rounded-lg p-4"
            >
                <div class="flex">
                    <svg
                        class="w-5 h-5 text-error mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <div class="text-error">{{ errorMessage }}</div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Outlet Information -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Outlet Details -->
                    <Card>
                        <h2
                            class="text-xl font-semibold text-text mb-4"
                            style="font-family: 'Oswald', sans-serif"
                        >
                            Detail Outlet
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <div class="text-sm text-muted">Nama</div>
                                <div class="text-text font-medium">
                                    {{ outlet.name }}
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-muted">Alamat</div>
                                <div class="text-text-2 text-sm">
                                    {{ outlet.address }}
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-muted">Koordinat</div>
                                <div class="text-text-2 text-sm">
                                    {{ outlet.latitude }},
                                    {{ outlet.longitude }}
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-muted">
                                    Radius Geofence
                                </div>
                                <div class="text-text font-medium">
                                    {{ outlet.radius }} meter
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Map -->
                    <Card>
                        <h2
                            class="text-xl font-semibold text-text mb-4"
                            style="font-family: 'Oswald', sans-serif"
                        >
                            Lokasi di Peta
                        </h2>
                        <div
                            v-if="outlet.latitude && outlet.longitude"
                            class="h-64 rounded-lg overflow-hidden border border-border"
                        >
                            <div
                                ref="mapRef"
                                class="w-full h-full"
                                style="min-height: 16rem"
                            ></div>
                        </div>
                        <div
                            v-else
                            class="text-sm text-muted text-center border border-dashed border-border rounded-lg p-6"
                        >
                            Lokasi belum tersedia untuk outlet ini.
                        </div>
                    </Card>

                    <!-- Operational Status -->
                    <Card>
                        <h2
                            class="text-xl font-semibold text-text mb-4"
                            style="font-family: 'Oswald', sans-serif"
                        >
                            Status Operasional
                        </h2>
                        <OperationalStatus
                            :outlet="outlet"
                            :show-details="true"
                        />
                    </Card>

                    <!-- Employees -->
                    <Card>
                        <div class="flex items-center justify-between mb-4">
                            <h2
                                class="text-xl font-semibold text-text"
                                style="font-family: 'Oswald', sans-serif"
                            >
                                Karyawan
                            </h2>
                            <Button
                                @click="openEmployeeModal"
                                variant="primary"
                                size="sm"
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
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                    />
                                </svg>
                                Tambah
                            </Button>
                        </div>

                        <div
                            v-if="
                                outlet.employees && outlet.employees.length > 0
                            "
                        >
                            <div class="space-y-3">
                                <div
                                    v-for="employee in outlet.employees"
                                    :key="employee.id"
                                    class="flex items-center justify-between p-3 bg-surface-2 rounded-none"
                                >
                                    <div class="flex items-center">
                                        <div
                                            class="w-8 h-8 bg-surface-3 rounded-none flex items-center justify-center text-xs text-text-2 font-medium"
                                        >
                                            {{
                                                employee.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-text font-medium">
                                                {{ employee.name }}
                                            </div>
                                            <div class="text-muted text-sm">
                                                {{ employee.email }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                employee.email_verified_at
                                                    ? 'bg-success-100 text-success border border-success'
                                                    : 'bg-warning-100 text-warning border border-warning',
                                            ]"
                                        >
                                            {{
                                                employee.email_verified_at
                                                    ? "Terverifikasi"
                                                    : "Pending"
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-muted">
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
                                    d="M12 4.354a4 4 0 100-5.646 5.646m5.646 10.354H12a4 4 0 100-5.646-5.646M17.646 12a4 4 0 100-5.646 5.646m-5.646 10.354H12a4 4 0 1005.646-5.646m-1.646-12.354H12a4 4 0 100-5.646-5.646"
                                />
                            </svg>
                            <p class="mt-2">Belum ada karyawan.</p>
                        </div>
                    </Card>
                </div>

                <!-- Attendance History -->
                <div class="lg:col-span-2">
                    <Card>
                        <h2
                            class="text-xl font-semibold text-text mb-6"
                            style="font-family: 'Oswald', sans-serif"
                        >
                            Riwayat Absensi
                        </h2>

                        <div
                            v-if="
                                outlet.attendances &&
                                outlet.attendances.length > 0
                            "
                        >
                            <!-- Summary Stats -->
                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6"
                            >
                                <div
                                    class="bg-surface-2 border border-border rounded-none p-4"
                                >
                                    <div class="text-sm text-muted">
                                        Total Record
                                    </div>
                                    <div class="text-2xl font-bold text-text">
                                        {{ outlet.attendances.length }}
                                    </div>
                                </div>
                                <div
                                    class="bg-surface-2 border border-border rounded-none p-4"
                                >
                                    <div class="text-sm text-muted">
                                        Check-in Hari Ini
                                    </div>
                                    <div class="text-2xl font-bold text-text">
                                        {{
                                            outlet.attendances.filter(
                                                (a) =>
                                                    new Date(
                                                        a.created_at
                                                    ).toDateString() ===
                                                    new Date().toDateString()
                                            ).length
                                        }}
                                    </div>
                                </div>
                                <div
                                    class="bg-surface-2 border border-border rounded-none p-4"
                                >
                                    <div class="text-sm text-muted">
                                        Aktif Sekarang
                                    </div>
                                    <div class="text-2xl font-bold text-text">
                                        {{
                                            outlet.attendances.filter(
                                                (a) => a.status === "checked_in"
                                            ).length
                                        }}
                                    </div>
                                </div>
                            </div>

                            <!-- Attendance Table -->
                            <div class="overflow-x-auto">
                                <table
                                    class="min-w-full divide-y divide-border"
                                >
                                    <thead class="bg-surface-2">
                                        <tr>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-text-2 uppercase tracking-wider"
                                            >
                                                Karyawan
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-text-2 uppercase tracking-wider"
                                            >
                                                Tanggal
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-text-2 uppercase tracking-wider"
                                            >
                                                Check In
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-text-2 uppercase tracking-wider"
                                            >
                                                Check Out
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-text-2 uppercase tracking-wider"
                                            >
                                                Durasi
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-text-2 uppercase tracking-wider"
                                            >
                                                Jarak
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-text-2 uppercase tracking-wider"
                                            >
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-surface-1 divide-y divide-border"
                                    >
                                        <tr
                                            v-for="attendance in outlet.attendances"
                                            :key="attendance.id"
                                            class="hover:bg-surface-2"
                                        >
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div
                                                        class="w-8 h-8 bg-surface-3 rounded-none flex items-center justify-center text-xs text-text-2 font-medium"
                                                    >
                                                        {{
                                                            attendance.user.name
                                                                .charAt(0)
                                                                .toUpperCase()
                                                        }}
                                                    </div>
                                                    <div class="ml-3">
                                                        <div
                                                            class="text-text font-medium"
                                                        >
                                                            {{
                                                                attendance.user
                                                                    .name
                                                            }}
                                                        </div>
                                                        <div
                                                            class="text-muted text-sm"
                                                        >
                                                            {{
                                                                attendance.user
                                                                    .email
                                                            }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-sm text-text-2"
                                            >
                                                {{
                                                    formatDate(
                                                        attendance.created_at
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-sm text-success"
                                            >
                                                {{
                                                    formatTime(
                                                        attendance.checkin_time
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-sm"
                                            >
                                                <span
                                                    v-if="
                                                        attendance.checkout_time
                                                    "
                                                    class="text-primary"
                                                >
                                                    {{
                                                        formatTime(
                                                            attendance.checkout_time
                                                        )
                                                    }}
                                                </span>
                                                <span v-else class="text-muted"
                                                    >-</span
                                                >
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-sm text-text-2"
                                            >
                                                {{
                                                    calculateWorkDuration(
                                                        attendance.checkin_time,
                                                        attendance.checkout_time
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-sm text-text-2"
                                            >
                                                {{
                                                    attendance.checkin_distance
                                                        ? Math.round(
                                                              attendance.checkin_distance
                                                          ) + "m"
                                                        : "-"
                                                }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-sm"
                                            >
                                                <span
                                                    :class="
                                                        getStatusBadge(
                                                            attendance.status
                                                        ).class
                                                    "
                                                >
                                                    {{
                                                        getStatusBadge(
                                                            attendance.status
                                                        ).text
                                                    }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-muted">
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
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                />
                            </svg>
                            <p class="mt-2">Belum ada riwayat absensi.</p>
                        </div>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Employee Modal -->
        <AddEmployeeModal
            :show="showEmployeeModal"
            :outlet="outlet"
            @close="closeEmployeeModal"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap");

h1,
h2 {
    font-family: "Oswald", sans-serif;
}

body {
    font-family: "Roboto", sans-serif;
}
</style>
