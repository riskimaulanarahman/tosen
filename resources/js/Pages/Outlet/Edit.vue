<script setup>
import { ref, onMounted } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const props = defineProps({
    outlet: Object,
});

const extractTime = (value) => {
    if (!value) return null;

    // Support ISO strings like 2025-01-01T08:00:00.000000Z or simple HH:MM
    const match = String(value).match(/(\d{2}:\d{2})/);
    return match ? match[1] : null;
};

const form = useForm({
    name: props.outlet.name,
    address: props.outlet.address,
    latitude: props.outlet.latitude,
    longitude: props.outlet.longitude,
    radius: props.outlet.radius,
    operational_start_time:
        props.outlet.operational_start_time_formatted ??
        extractTime(props.outlet.operational_start_time) ??
        "08:00",
    operational_end_time:
        props.outlet.operational_end_time_formatted ??
        extractTime(props.outlet.operational_end_time) ??
        "17:00",
    work_days: props.outlet.work_days || [1, 2, 3, 4, 5],
    timezone: props.outlet.timezone || "Asia/Jakarta",
    late_tolerance_minutes: props.outlet.late_tolerance_minutes ?? 15,
    early_checkout_tolerance: props.outlet.early_checkout_tolerance ?? 10,
    grace_period_minutes: props.outlet.grace_period_minutes ?? 5,
    overtime_threshold_minutes: props.outlet.overtime_threshold_minutes ?? 60,
});

let map = null;
let marker = null;
let circle = null;
const isLoadingLocation = ref(false);
const locationError = ref("");

const getCurrentLocation = async () => {
    if (!navigator.geolocation) {
        locationError.value = "Browser tidak mendukung geolocation";
        return;
    }

    isLoadingLocation.value = true;
    locationError.value = "";

    navigator.geolocation.getCurrentPosition(
        handleLocationSuccess,
        handleLocationError,
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
        }
    );
};

const handleLocationSuccess = (position) => {
    const { latitude, longitude } = position.coords;
    form.latitude = latitude.toFixed(6);
    form.longitude = longitude.toFixed(6);
    updateMarkerPosition();

    // Center map to new location
    if (map) {
        map.setView([latitude, longitude], 15);
    }

    isLoadingLocation.value = false;
    locationError.value = "";
};

const handleLocationError = (error) => {
    isLoadingLocation.value = false;

    switch (error.code) {
        case error.PERMISSION_DENIED:
            locationError.value =
                "Akses lokasi ditolak. Silakan aktifkan permission lokasi.";
            break;
        case error.POSITION_UNAVAILABLE:
            locationError.value = "Informasi lokasi tidak tersedia.";
            break;
        case error.TIMEOUT:
            locationError.value = "Timeout mendapatkan lokasi. Coba lagi.";
            break;
        default:
            locationError.value = "Terjadi kesalahan mendapatkan lokasi.";
    }
};

const initMap = () => {
    // Initialize map
    map = L.map("map").setView([form.latitude, form.longitude], 15);

    // Add light tile layer
    L.tileLayer(
        "https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png",
        {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: "abcd",
            maxZoom: 19,
        }
    ).addTo(map);

    // Add marker
    marker = L.marker([form.latitude, form.longitude], {
        draggable: true,
    }).addTo(map);

    // Add radius circle
    circle = L.circle([form.latitude, form.longitude], {
        color: "#fb923c",
        fillColor: "#fb923c",
        fillOpacity: 0.2,
        radius: form.radius,
    }).addTo(map);

    // Update position when marker is dragged
    marker.on("dragend", function (e) {
        const position = e.target.getLatLng();
        form.latitude = position.lat.toFixed(6);
        form.longitude = position.lng.toFixed(6);
        updateCircle();
    });
};

const updateCircle = () => {
    if (circle) {
        circle.setRadius(form.radius);
        circle.setLatLng([form.latitude, form.longitude]);
    }
};

const updateMarkerPosition = () => {
    if (marker) {
        marker.setLatLng([form.latitude, form.longitude]);
        updateCircle();
    }
};

const submit = () => {
    form.put(route("outlets.update", props.outlet.id), {
        onSuccess: () => {
            // Success message will be handled by redirect
        },
    });
};

onMounted(() => {
    // Initialize map when component is mounted
    setTimeout(() => {
        initMap();
    }, 100);
});

// Breadcrumb items
const breadcrumbItems = [
    { name: "Dashboard", href: route("dashboard") },
    { name: "Manajemen Outlet", href: route("outlets.index") },
    { name: "Edit Outlet" },
];
</script>

<template>
    <Head :title="`Edit ${outlet.name}`" />

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
                        Edit Outlet
                    </h1>
                    <p class="text-muted">
                        Perbarui informasi outlet {{ outlet.name }}
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
                v-if="$page.props.flash?.success"
                class="bg-green-900/20 border border-green-600 rounded-lg p-4"
            >
                <div class="flex">
                    <svg
                        class="w-5 h-5 text-green-400 mr-2"
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
                    <div class="text-green-400">
                        {{ $page.props.flash.success }}
                    </div>
                </div>
            </div>

            <div
                v-if="$page.props.flash?.error"
                class="bg-red-900/20 border border-red-600 rounded-lg p-4"
            >
                <div class="flex">
                    <svg
                        class="w-5 h-5 text-red-400 mr-2"
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
                    <div class="text-red-400">
                        {{ $page.props.flash.error }}
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Form Section -->
                <Card>
                    <h2
                        class="text-xl font-semibold text-text mb-6"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Informasi Outlet
                    </h2>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name Field -->
                        <div>
                            <label
                                class="block text-text-2 text-sm font-medium mb-2"
                            >
                                Nama Outlet
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="Masukkan nama outlet"
                                required
                            />
                            <div
                                v-if="form.errors.name"
                                class="mt-1 text-sm text-error"
                            >
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Address Field -->
                        <div>
                            <label
                                class="block text-text-2 text-sm font-medium mb-2"
                            >
                                Alamat
                            </label>
                            <textarea
                                v-model="form.address"
                                rows="3"
                                class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="Masukkan alamat outlet"
                                required
                            ></textarea>
                            <div
                                v-if="form.errors.address"
                                class="mt-1 text-sm text-error"
                            >
                                {{ form.errors.address }}
                            </div>
                        </div>

                        <!-- Location Button -->
                        <div class="mb-4">
                            <Button
                                type="button"
                                @click="getCurrentLocation"
                                :disabled="isLoadingLocation"
                                variant="secondary"
                                class="w-full"
                            >
                                <span
                                    v-if="isLoadingLocation"
                                    class="flex items-center"
                                >
                                    <svg
                                        class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        ></circle>
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                    Mendapatkan lokasi...
                                </span>
                                <span v-else class="flex items-center">
                                    📍 Gunakan Lokasi Saat Ini
                                </span>
                            </Button>
                        </div>

                        <!-- Error Message -->
                        <div
                            v-if="locationError"
                            class="mb-4 p-3 bg-error-100 border border-error rounded-lg text-error text-sm"
                        >
                            {{ locationError }}
                        </div>

                        <!-- Coordinates Fields -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-text-2 text-sm font-medium mb-2"
                                >
                                    Latitude
                                </label>
                                <input
                                    v-model="form.latitude"
                                    type="number"
                                    step="0.000001"
                                    class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                    placeholder="-6.200000"
                                    @input="updateMarkerPosition"
                                    required
                                />
                                <div
                                    v-if="form.errors.latitude"
                                    class="mt-1 text-sm text-error"
                                >
                                    {{ form.errors.latitude }}
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-text-2 text-sm font-medium mb-2"
                                >
                                    Longitude
                                </label>
                                <input
                                    v-model="form.longitude"
                                    type="number"
                                    step="0.000001"
                                    class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                    placeholder="106.816666"
                                    @input="updateMarkerPosition"
                                    required
                                />
                                <div
                                    v-if="form.errors.longitude"
                                    class="mt-1 text-sm text-error"
                                >
                                    {{ form.errors.longitude }}
                                </div>
                            </div>
                        </div>

                        <!-- Radius Field -->
                        <div>
                            <label
                                class="block text-text-2 text-sm font-medium mb-2"
                            >
                                Radius Geofence (meter)
                            </label>
                            <input
                                v-model="form.radius"
                                type="number"
                                min="10"
                                max="1000"
                                class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="50"
                                @input="updateCircle"
                                required
                            />
                            <div
                                v-if="form.errors.radius"
                                class="mt-1 text-sm text-error"
                            >
                                {{ form.errors.radius }}
                            </div>
                            <p class="mt-1 text-sm text-muted">
                                Karyawan harus berada dalam radius ini untuk
                                melakukan check-in.
                            </p>
                        </div>

                        <!-- Operational Hours Section -->
                        <div class="border-t border-border pt-6">
                            <h3
                                class="text-lg font-semibold text-text mb-4"
                                style="font-family: 'Oswald', sans-serif"
                            >
                                Jam Operasional
                            </h3>

                            <div class="space-y-4">
                                <!-- Time Fields -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            class="block text-text-2 text-sm font-medium mb-2"
                                        >
                                            Waktu Mulai
                                        </label>
                                        <input
                                            v-model="
                                                form.operational_start_time
                                            "
                                            type="time"
                                            class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                            required
                                        />
                                        <div
                                            v-if="
                                                form.errors
                                                    .operational_start_time
                                            "
                                            class="mt-1 text-sm text-error"
                                        >
                                            {{
                                                form.errors
                                                    .operational_start_time
                                            }}
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-text-2 text-sm font-medium mb-2"
                                        >
                                            Waktu Selesai
                                        </label>
                                        <input
                                            v-model="form.operational_end_time"
                                            type="time"
                                            class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                            required
                                        />
                                        <div
                                            v-if="
                                                form.errors.operational_end_time
                                            "
                                            class="mt-1 text-sm text-error"
                                        >
                                            {{
                                                form.errors.operational_end_time
                                            }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Work Days -->
                                <div>
                                    <label
                                        class="block text-text-2 text-sm font-medium mb-2"
                                    >
                                        Hari Kerja
                                    </label>
                                    <div class="grid grid-cols-4 gap-2">
                                        <label
                                            v-for="day in [
                                                { value: 1, label: 'Sen' },
                                                { value: 2, label: 'Sel' },
                                                { value: 3, label: 'Rab' },
                                                { value: 4, label: 'Kam' },
                                                { value: 5, label: 'Jum' },
                                                { value: 6, label: 'Sab' },
                                                { value: 7, label: 'Min' },
                                            ]"
                                            :key="day.value"
                                            class="flex items-center space-x-2 p-2 rounded border border-border cursor-pointer hover:bg-surface-3"
                                            :class="{
                                                'bg-primary border-primary':
                                                    form.work_days.includes(
                                                        day.value
                                                    ),
                                            }"
                                        >
                                            <input
                                                type="checkbox"
                                                :value="day.value"
                                                v-model="form.work_days"
                                                class="rounded text-primary focus:ring-primary bg-surface-2 border-border"
                                            />
                                            <span class="text-sm text-text-2">{{
                                                day.label
                                            }}</span>
                                        </label>
                                    </div>
                                    <div
                                        v-if="form.errors.work_days"
                                        class="mt-1 text-sm text-error"
                                    >
                                        {{ form.errors.work_days }}
                                    </div>
                                </div>

                                <!-- Timezone -->
                                <div>
                                    <label
                                        class="block text-text-2 text-sm font-medium mb-2"
                                    >
                                        Timezone
                                    </label>
                                    <select
                                        v-model="form.timezone"
                                        class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                        required
                                    >
                                        <option value="Asia/Jakarta">
                                            WIB (UTC+7)
                                        </option>
                                        <option value="Asia/Makassar">
                                            WITA (UTC+8)
                                        </option>
                                        <option value="Asia/Jayapura">
                                            WIT (UTC+9)
                                        </option>
                                    </select>
                                    <div
                                        v-if="form.errors.timezone"
                                        class="mt-1 text-sm text-error"
                                    >
                                        {{ form.errors.timezone }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tolerance Settings Section -->
                        <div class="border-t border-border pt-6">
                            <h3
                                class="text-lg font-semibold text-text mb-4"
                                style="font-family: 'Oswald', sans-serif"
                            >
                                Pengaturan Toleransi
                            </h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-text-2 text-sm font-medium mb-2"
                                    >
                                        Grace Period (menit)
                                    </label>
                                    <input
                                        v-model="form.grace_period_minutes"
                                        type="number"
                                        min="0"
                                        max="30"
                                        class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                        required
                                    />
                                    <div
                                        v-if="form.errors.grace_period_minutes"
                                        class="mt-1 text-sm text-error"
                                    >
                                        {{ form.errors.grace_period_minutes }}
                                    </div>
                                    <p class="mt-1 text-xs text-muted">
                                        Waktu tambahan sebelum dianggap
                                        terlambat
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-text-2 text-sm font-medium mb-2"
                                    >
                                        Toleransi Keterlambatan (menit)
                                    </label>
                                    <input
                                        v-model="form.late_tolerance_minutes"
                                        type="number"
                                        min="0"
                                        max="60"
                                        class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                        required
                                    />
                                    <div
                                        v-if="
                                            form.errors.late_tolerance_minutes
                                        "
                                        class="mt-1 text-sm text-error"
                                    >
                                        {{ form.errors.late_tolerance_minutes }}
                                    </div>
                                    <p class="mt-1 text-xs text-muted">
                                        Maksimal keterlambatan yang
                                        diperbolehkan
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-text-2 text-sm font-medium mb-2"
                                    >
                                        Toleransi Checkout Awal (menit)
                                    </label>
                                    <input
                                        v-model="form.early_checkout_tolerance"
                                        type="number"
                                        min="0"
                                        max="60"
                                        class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                        required
                                    />
                                    <div
                                        v-if="
                                            form.errors.early_checkout_tolerance
                                        "
                                        class="mt-1 text-sm text-error"
                                    >
                                        {{
                                            form.errors.early_checkout_tolerance
                                        }}
                                    </div>
                                    <p class="mt-1 text-xs text-muted">
                                        Maksimal checkout lebih awal yang
                                        diperbolehkan
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-text-2 text-sm font-medium mb-2"
                                    >
                                        Threshold Lembur (menit)
                                    </label>
                                    <input
                                        v-model="
                                            form.overtime_threshold_minutes
                                        "
                                        type="number"
                                        min="0"
                                        max="240"
                                        class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                        required
                                    />
                                    <div
                                        v-if="
                                            form.errors
                                                .overtime_threshold_minutes
                                        "
                                        class="mt-1 text-sm text-error"
                                    >
                                        {{
                                            form.errors
                                                .overtime_threshold_minutes
                                        }}
                                    </div>
                                    <p class="mt-1 text-xs text-muted">
                                        Minimal jam kerja untuk dihitung lembur
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end space-x-4">
                            <Link :href="route('outlets.show', outlet.id)">
                                <Button variant="secondary" type="button">
                                    Batal
                                </Button>
                            </Link>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                variant="primary"
                            >
                                <span v-if="form.processing"
                                    >Memperbarui...</span
                                >
                                <span v-else>Update Outlet</span>
                            </Button>
                        </div>
                    </form>
                </Card>

                <!-- Map Section -->
                <Card>
                    <h2
                        class="text-xl font-semibold text-text mb-6"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Peta Lokasi
                    </h2>
                    <div class="text-sm text-muted mb-4">
                        Seret marker untuk menentukan lokasi tepat. Lingkaran
                        oranye menunjukkan radius geofence.
                    </div>
                    <div
                        id="map"
                        class="h-96 bg-surface-2 rounded-none border border-border"
                    ></div>
                </Card>
            </div>
        </div>
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

/* Leaflet map styling */
#map {
    border: 1px solid var(--color-border);
}

/* Dark mode for Leaflet controls */
:deep(.leaflet-control-zoom) {
    background: var(--color-surface-3) !important;
    border: 1px solid var(--color-border) !important;
}

:deep(.leaflet-control-zoom a) {
    background: var(--color-surface-3) !important;
    color: var(--color-text-2) !important;
    border-color: var(--color-border) !important;
}

:deep(.leaflet-control-zoom a:hover) {
    background: var(--color-surface-1) !important;
    color: var(--color-text-2) !important;
}
</style>
