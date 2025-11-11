<script setup>
import { ref, onMounted } from "vue";
import { Head, usePage, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import CameraModal from "@/Components/CameraModal.vue";
import MapModal from "@/Components/MapModal.vue";

const page = usePage();

const props = defineProps({
    user: Object,
    userRole: String,
    stats: Object,
    recentOutlets: Array,
    recentAttendances: Array,
    today_attendance: Object,
    outlet: Object,
});

const todayAttendance = ref(props.today_attendance ?? null);
const recentAttendances = ref(props.recentAttendances ?? []);
const outlet = ref(props.outlet ?? null);
const isLoading = ref(false);
const message = ref("");
const messageType = ref("");

// Camera modal states
const showCameraModal = ref(false);
const cameraAction = ref(""); // 'checkin' or 'checkout'
const capturedSelfie = ref(null);
const isProcessing = ref(false);

// Map modal states
const showMapModal = ref(false);
const selectedOutlet = ref(null);

// Open map modal
const openMapModal = (outletData) => {
    selectedOutlet.value = outletData;
    showMapModal.value = true;
};

// Close map modal
const closeMapModal = () => {
    showMapModal.value = false;
    selectedOutlet.value = null;
};

// Generate Google Maps URL
const getGoogleMapsUrl = (outletData) => {
    if (!outletData.latitude || !outletData.longitude) {
        return "#";
    }
    return `https://www.google.com/maps?q=${outletData.latitude},${outletData.longitude}`;
};

const getLocation = () => {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error("Geolocation is not supported by this browser."));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                });
            },
            (error) => {
                reject(error);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            }
        );
    });
};

const checkIn = () => {
    cameraAction.value = "checkin";
    showCameraModal.value = true;
};

const checkOut = () => {
    cameraAction.value = "checkout";
    showCameraModal.value = true;
};

const handleCameraCapture = async (imageData) => {
    if (isProcessing.value) return;

    isProcessing.value = true;
    message.value = "";

    if (!cameraAction.value) {
        handleError(
            "Aksi belum dipilih. Klik tombol CHECK IN atau CHECK OUT terlebih dahulu."
        );
        isProcessing.value = false;
        return;
    }
    const currentAction = cameraAction.value;

    try {
        if (!imageData) {
            throw new Error("Selfie tidak tersedia. Ulangi pengambilan foto.");
        }

        const formData = new FormData();
        formData.append("selfie", imageData, `selfie_${Date.now()}.jpg`);

        const position = await getLocation();
        formData.append("latitude", position.latitude);
        formData.append("longitude", position.longitude);
        if (position.accuracy !== undefined) {
            formData.append("accuracy", position.accuracy);
        }

        const routeName =
            currentAction === "checkin"
                ? "attendance.checkin"
                : "attendance.checkout";

        const response = await fetch(route(routeName), {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: formData,
        });

        let data = {};
        try {
            data = await response.json();
        } catch (parseError) {
            console.error("Failed to parse response:", parseError);
        }

        if (response.ok) {
            let successMessage = "";
            if (currentAction === "checkin") {
                successMessage = `Berhasil check-in di ${data.outlet_name}! Jarak: ${data.distance}m`;
            } else {
                successMessage = `Berhasil check-out! Durasi kerja: ${
                    data.work_duration?.formatted || "N/A"
                } jam`;
            }
            handleSuccess(successMessage);
            showCameraModal.value = false;
            cameraAction.value = "";
            capturedSelfie.value = null;
            await fetchAttendanceStatus();
        } else {
            // Handle validation errors with global error handler
            const error = new Error(
                data?.message || "Terjadi kesalahan pada server."
            );
            error.response = { status: response.status, data: data };
            throw error;
        }
    } catch (error) {
        const errorMessage = error?.message?.toLowerCase() || "";
        if (
            errorMessage.includes("lokasi") ||
            errorMessage.includes("location")
        ) {
            handleError(
                error,
                "Tidak bisa mendapatkan lokasi Anda. Silakan aktifkan layanan lokasi."
            );
        } else {
            handleError(error);
        }
    } finally {
        isProcessing.value = false;
    }
};

const handleCameraConfirm = async (imageData) => {
    console.log("Camera confirm received:", imageData);
    await handleCameraCapture(imageData);
};

const handleCameraClose = () => {
    showCameraModal.value = false;
    cameraAction.value = "";
    capturedSelfie.value = null;
};

const fetchAttendanceStatus = async () => {
    try {
        const response = await fetch(route("attendance.status"));
        const data = await response.json();

        todayAttendance.value = data.today_attendance;
        recentAttendances.value = data.recent_attendances ?? [];
        outlet.value = data.outlet ?? null;
    } catch (error) {
        console.error("Error fetching attendance status:", error);
    }
};

const formatTime = (time) => {
    if (!time) {
        return "-";
    }

    return new Date(time).toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatDate = (date) => {
    if (!date) {
        return "-";
    }

    return new Date(date).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

onMounted(() => {
    fetchAttendanceStatus();
});
</script>

<template>
    <Head title="Dashboard" />

    <!-- Owner Dashboard -->
    <AuthenticatedLayout v-if="userRole === 'owner'">
        <!-- Messages -->
        <div v-if="message" class="mb-6">
            <div
                :class="[
                    'px-4 py-3 rounded-lg text-sm',
                    messageType === 'success'
                        ? 'bg-success-50 border border-success text-success-600'
                        : 'bg-error-50 border border-error text-error-600',
                ]"
            >
                {{ message }}
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <Card class="group">
                <div class="flex items-center">
                    <div class="relative">
                        <div
                            class="w-14 h-14 bg-orange-500 rounded-lg flex items-center justify-center shadow-lg shadow-orange-500/25 group-hover:shadow-xl transition-all duration-200 transform group-hover:scale-110"
                        >
                            <svg
                                class="w-7 h-7 text-white"
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
                        </div>
                        <!-- Glow effect -->
                        <div
                            class="absolute inset-0 bg-orange-500/30 rounded-lg blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                        ></div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-muted text-sm font-medium mb-1">
                            Total Outlet
                        </p>
                        <p class="text-3xl font-bold text-text">
                            {{ stats?.totalOutlets || 0 }}
                        </p>
                        <!-- Trend indicator -->
                        <div class="flex items-center mt-2">
                            <div
                                class="flex items-center text-success text-xs font-medium"
                            >
                                <svg
                                    class="w-3 h-3 mr-1"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                12%
                            </div>
                            <span class="text-text-3 text-xs ml-2"
                                >dari bulan lalu</span
                            >
                        </div>
                    </div>
                </div>
            </Card>

            <Card class="group">
                <div class="flex items-center">
                    <div class="relative">
                        <div
                            class="w-14 h-14 bg-green-500 rounded-lg flex items-center justify-center shadow-lg shadow-green-500/25 group-hover:shadow-xl transition-all duration-200 transform group-hover:scale-110"
                        >
                            <svg
                                class="w-7 h-7 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                        </div>
                        <div
                            class="absolute inset-0 bg-green-500/30 rounded-lg blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                        ></div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-muted text-sm font-medium mb-1">
                            Total Karyawan
                        </p>
                        <p class="text-3xl font-bold text-text">
                            {{ stats?.totalEmployees || 0 }}
                        </p>
                        <div class="flex items-center mt-2">
                            <div
                                class="flex items-center text-success text-xs font-medium"
                            >
                                <svg
                                    class="w-3 h-3 mr-1"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                8%
                            </div>
                            <span class="text-text-3 text-xs ml-2"
                                >dari bulan lalu</span
                            >
                        </div>
                    </div>
                </div>
            </Card>

            <Card class="group">
                <div class="flex items-center">
                    <div class="relative">
                        <div
                            class="w-14 h-14 bg-blue-500 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/25 group-hover:shadow-xl transition-all duration-200 transform group-hover:scale-110"
                        >
                            <svg
                                class="w-7 h-7 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                        </div>
                        <div
                            class="absolute inset-0 bg-blue-500/30 rounded-lg blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                        ></div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-muted text-sm font-medium mb-1">
                            Total Absensi
                        </p>
                        <p class="text-3xl font-bold text-text">
                            {{ stats?.totalAttendances || 0 }}
                        </p>
                        <div class="flex items-center mt-2">
                            <div
                                class="flex items-center text-warning text-xs font-medium"
                            >
                                <svg
                                    class="w-3 h-3 mr-1"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                3%
                            </div>
                            <span class="text-text-3 text-xs ml-2"
                                >dari bulan lalu</span
                            >
                        </div>
                    </div>
                </div>
            </Card>

            <Card class="group">
                <div class="flex items-center">
                    <div class="relative">
                        <div
                            class="w-14 h-14 bg-yellow-500 rounded-lg flex items-center justify-center shadow-lg shadow-yellow-500/25 group-hover:shadow-xl transition-all duration-200 transform group-hover:scale-110"
                        >
                            <svg
                                class="w-7 h-7 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div
                            class="absolute inset-0 bg-yellow-500/30 rounded-lg blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                        ></div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-muted text-sm font-medium mb-1">
                            Check-in Hari Ini
                        </p>
                        <p class="text-3xl font-bold text-text">
                            {{ stats?.todayAttendances || 0 }}
                        </p>
                        <div class="flex items-center mt-2">
                            <div
                                class="flex items-center text-success text-xs font-medium"
                            >
                                <svg
                                    class="w-3 h-3 mr-1"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                15%
                            </div>
                            <span class="text-text-3 text-xs ml-2"
                                >dari kemarin</span
                            >
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Recent Activity Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Outlets -->
            <Card>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-text">Outlet Terbaru</h2>
                    <Link
                        :href="route('outlets.index')"
                        class="text-primary hover:text-primary-400 text-sm font-medium"
                    >
                        Lihat Semua
                    </Link>
                </div>

                <div v-if="recentOutlets?.length > 0" class="space-y-4">
                    <div
                        v-for="outlet in recentOutlets"
                        :key="outlet.id"
                        class="flex items-center justify-between p-3 bg-surface-2 rounded-none"
                    >
                        <div>
                            <p class="font-medium text-text">
                                {{ outlet.name }}
                            </p>
                            <p class="text-sm text-muted">
                                {{ outlet.employees?.length || 0 }} karyawan
                            </p>
                        </div>
                        <Link
                            :href="route('outlets.show', outlet.id)"
                            class="text-primary hover:text-primary-400"
                        >
                            <svg
                                class="w-5 h-5"
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
                        </Link>
                    </div>
                </div>

                <div v-else class="text-center py-8 text-muted">
                    <p>Belum ada outlet. Buat outlet pertama Anda!</p>
                    <Link
                        :href="route('outlets.create')"
                        class="mt-4 inline-block"
                    >
                        <Button variant="primary" size="sm">
                            Buat Outlet
                        </Button>
                    </Link>
                </div>
            </Card>

            <!-- Recent Attendances -->
            <Card>
                <h2 class="text-lg font-bold text-text mb-6">
                    Absensi Terbaru
                </h2>

                <div v-if="recentAttendances?.length > 0" class="space-y-4">
                    <div
                        v-for="attendance in recentAttendances"
                        :key="attendance.id"
                        class="flex items-center justify-between p-3 bg-surface-2 rounded-none"
                    >
                        <div>
                            <p class="font-medium text-text">
                                {{ attendance.user?.name }}
                            </p>
                            <p class="text-sm text-muted">
                                {{ attendance.outlet?.name }}
                            </p>
                            <p class="text-xs text-text-3">
                                {{
                                    attendance.check_in_time
                                        ? formatTime(attendance.check_in_time)
                                        : "-"
                                }}
                                -
                                {{ formatDate(attendance.created_at) }}
                            </p>
                        </div>
                        <div>
                            <span
                                :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    attendance.status === 'checked_in'
                                        ? 'bg-success-100 text-success-600'
                                        : 'bg-surface-3 text-text-2',
                                ]"
                            >
                                {{
                                    attendance.status === "checked_in"
                                        ? "Check-in"
                                        : "Selesai"
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8 text-muted">
                    <p>Belum ada data absensi.</p>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>

    <!-- Employee Dashboard -->
    <AuthenticatedLayout v-else>
        <!-- Messages -->
        <div v-if="message" class="mb-6">
            <div
                :class="[
                    'px-4 py-3 rounded-lg text-sm',
                    messageType === 'success'
                        ? 'bg-success-100 border border-success text-success-600'
                        : 'bg-error-100 border border-error text-error-600',
                ]"
            >
                {{ message }}
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Outlet Info & Check-in/Out -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Outlet Information -->
                <Card>
                    <h2 class="text-xl font-bold text-text mb-4">
                        Informasi Outlet
                    </h2>

                    <div v-if="outlet" class="space-y-3">
                        <div>
                            <div class="text-sm text-muted">Nama Outlet</div>
                            <div class="text-text font-medium">
                                {{ outlet.name }}
                            </div>
                        </div>
                        <!-- <div>
                            <div class="text-sm text-muted">Alamat</div>
                            <div class="text-text text-sm">
                                {{ outlet.address }}
                            </div>
                        </div> -->
                        <div>
                            <div class="text-sm text-muted">
                                Radius Geofence
                            </div>
                            <div class="text-text font-medium">
                                {{ outlet.radius }} meter
                            </div>
                        </div>
                        <div v-if="outlet.latitude && outlet.longitude">
                            <div class="text-sm text-muted mb-2">
                                Lokasi & Peta
                            </div>
                            <div class="flex flex-col gap-1">
                                <!-- Map Modal Link -->
                                <button
                                    @click="openMapModal(outlet)"
                                    class="inline-flex items-center px-3 py-2 text-xs text-primary hover:text-primary-400 transition-colors bg-surface-2 hover:bg-surface-3 rounded-none"
                                    title="Lihat peta"
                                >
                                    <svg
                                        class="w-3 h-3 mr-1"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                    Lihat Peta
                                </button>
                                <!-- Google Maps Button -->
                                <a
                                    :href="getGoogleMapsUrl(outlet)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center px-3 py-2 text-xs text-blue-600 hover:text-blue-700 transition-colors bg-surface-2 hover:bg-surface-3 rounded-none"
                                    title="Buka di Google Maps"
                                >
                                    <svg
                                        class="w-3 h-3 mr-1"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                        />
                                    </svg>
                                    Google Maps
                                </a>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-muted">
                        Anda tidak terassign ke outlet mana pun.
                    </div>
                </Card>

                <!-- Check-in/Out Actions -->
                <Card v-if="outlet">
                    <h2 class="text-xl font-bold text-text mb-4">
                        Aksi Absensi
                    </h2>

                    <!-- Today's Status -->
                    <div class="mb-6">
                        <div class="text-sm text-muted mb-2">
                            Status Hari Ini
                        </div>
                        <div v-if="todayAttendance" class="space-y-2">
                            <div
                                v-if="todayAttendance.check_in_time"
                                class="flex justify-between"
                            >
                                <span class="text-text">Check-in:</span>
                                <span class="text-success font-medium">{{
                                    formatTime(todayAttendance.check_in_time)
                                }}</span>
                            </div>
                            <div
                                v-if="todayAttendance.check_out_time"
                                class="flex justify-between"
                            >
                                <span class="text-text">Check-out:</span>
                                <span class="text-warning font-medium">{{
                                    formatTime(todayAttendance.check_out_time)
                                }}</span>
                            </div>
                            <div
                                v-if="!todayAttendance.check_out_time"
                                class="text-warning text-sm"
                            >
                                Sedang check-in
                            </div>
                        </div>
                        <div v-else class="text-muted">
                            Belum ada absensi hari ini
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <Button
                            v-if="
                                !todayAttendance ||
                                todayAttendance.check_out_time
                            "
                            @click="checkIn"
                            :disabled="isLoading"
                            variant="primary"
                            size="lg"
                            class="w-full"
                        >
                            <span v-if="isLoading">Sedang Check-in...</span>
                            <span v-else>📍 CHECK IN</span>
                        </Button>

                        <Button
                            v-if="
                                todayAttendance &&
                                !todayAttendance.check_out_time
                            "
                            @click="checkOut"
                            :disabled="isLoading"
                            variant="danger"
                            size="lg"
                            class="w-full"
                        >
                            <span v-if="isLoading">Sedang Check-out...</span>
                            <span v-else>📍 CHECK OUT</span>
                        </Button>
                    </div>
                </Card>
            </div>

            <!-- Recent Attendances -->
            <div class="lg:col-span-2">
                <Card>
                    <h2 class="text-xl font-bold text-text mb-6">
                        Riwayat Absensi Terbaru
                    </h2>

                    <div v-if="recentAttendances?.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead
                                    class="bg-surface-2 text-text-2 font-semibold"
                                >
                                    <tr>
                                        <th
                                            class="px-4 py-3 border-b border-border text-left"
                                        >
                                            Tanggal
                                        </th>
                                        <th
                                            class="px-4 py-3 border-b border-border text-left"
                                        >
                                            Check In
                                        </th>
                                        <th
                                            class="px-4 py-3 border-b border-border text-left"
                                        >
                                            Check Out
                                        </th>
                                        <th
                                            class="px-4 py-3 border-b border-border text-left"
                                        >
                                            Jarak
                                        </th>
                                        <th
                                            class="px-4 py-3 border-b border-border text-left"
                                        >
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="attendance in recentAttendances"
                                        :key="attendance.id"
                                        class="hover:bg-surface-2"
                                    >
                                        <td
                                            class="px-4 py-3 border-b border-border"
                                        >
                                            {{
                                                formatDate(
                                                    attendance.created_at
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 border-b border-border text-success"
                                        >
                                            {{
                                                formatTime(
                                                    attendance.check_in_time
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 border-b border-border"
                                        >
                                            <span
                                                v-if="attendance.check_out_time"
                                                class="text-warning"
                                            >
                                                {{
                                                    formatTime(
                                                        attendance.check_out_time
                                                    )
                                                }}
                                            </span>
                                            <span v-else class="text-muted"
                                                >-</span
                                            >
                                        </td>
                                        <td
                                            class="px-4 py-3 border-b border-border"
                                        >
                                            {{
                                                attendance.check_in_distance
                                                    ? Math.round(
                                                          attendance.check_in_distance
                                                      ) + "m"
                                                    : "-"
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 border-b border-border"
                                        >
                                            <span
                                                :class="[
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    attendance.status ===
                                                    'checked_in'
                                                        ? 'bg-success-100 text-success-600'
                                                        : 'bg-surface-3 text-text-2',
                                                ]"
                                            >
                                                {{
                                                    attendance.status ===
                                                    "checked_in"
                                                        ? "Check-in"
                                                        : "Selesai"
                                                }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else class="text-center py-12 text-muted">
                        <p>Belum ada data absensi.</p>
                    </div>
                </Card>
            </div>
        </div>

        <!-- Camera Modal -->
        <CameraModal
            :show="showCameraModal"
            :title="
                cameraAction === 'checkin'
                    ? '📸 Check-in dengan Selfie'
                    : '📸 Check-out dengan Selfie'
            "
            :subtitle="
                cameraAction === 'checkin'
                    ? 'Posisikan wajah Anda di dalam frame'
                    : 'Ambil foto selfie untuk check-out'
            "
            @confirm="handleCameraConfirm"
            @close="handleCameraClose"
        />

        <!-- Map Modal -->
        <MapModal
            :show="showMapModal"
            :outlet="selectedOutlet"
            @close="closeMapModal"
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
