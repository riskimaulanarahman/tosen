<script setup>
import { ref, onMounted, computed } from "vue";
import { Head, usePage, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import CameraModal from "@/Components/CameraModal.vue";

const page = usePage();
const attendances = ref(page.props.attendances);
const todayAttendance = ref(page.props.todayAttendance);
const stats = ref(page.props.stats);
const outlet = ref(page.props.outlet);

// Check if user can check in
const canCheckIn = computed(() => {
    return (
        !todayAttendance.value || todayAttendance.value.status === "checked_out"
    );
});

// Check if user can check out
const canCheckOut = computed(() => {
    return (
        todayAttendance.value && todayAttendance.value.status === "checked_in"
    );
});

// Loading states
const isGettingLocation = ref(false);
const locationError = ref("");

// Camera modal states
const showCameraModal = ref(false);
const cameraAction = ref(""); // 'checkin' or 'checkout'
const capturedSelfie = ref(null);
const isProcessing = ref(false);

// Get location and check in
const checkIn = async () => {
    cameraAction.value = "checkin";
    showCameraModal.value = true;
};

// Get location and check out
const checkOut = async () => {
    cameraAction.value = "checkout";
    showCameraModal.value = true;
};

// Handle camera capture
const handleCameraCapture = (imageBlob) => {
    capturedSelfie.value = imageBlob;
};

// Handle camera confirmation - proceed with attendance
const handleCameraConfirm = async (imageBlob) => {
    isProcessing.value = true;
    showCameraModal.value = false;
    locationError.value = "";

    try {
        // Get location first
        const position = await getCurrentPosition();

        // Create FormData for file upload
        const formData = new FormData();
        formData.append("latitude", position.coords.latitude);
        formData.append("longitude", position.coords.longitude);
        formData.append("accuracy", position.coords.accuracy);
        formData.append("selfie", imageBlob, `selfie_${Date.now()}.jpg`);

        // Determine endpoint
        const endpoint =
            cameraAction.value === "checkin"
                ? "/attendance/checkin"
                : "/attendance/checkout";

        const response = await axios.post(endpoint, formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        });

        if (response.data.success) {
            handleSuccess(
                cameraAction.value === "checkin"
                    ? "Check-in berhasil!"
                    : "Check-out berhasil!"
            );
            // Reload data
            window.location.reload();
        } else {
            handleError(new Error(response.data.message));
        }
    } catch (error) {
        console.error("Attendance error:", error);
        handleLocationError(error);
    } finally {
        isProcessing.value = false;
        capturedSelfie.value = null;
        cameraAction.value = "";
    }
};

// Handle camera modal close
const handleCameraClose = () => {
    showCameraModal.value = false;
    capturedSelfie.value = null;
    cameraAction.value = "";
};

// Enhanced location detection with retry mechanism
const getCurrentPosition = (retryCount = 0) => {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(
                new Error(
                    "Geolocation is not supported by this browser. Please try using a different browser."
                )
            );
            return;
        }

        const options = {
            enableHighAccuracy: true,
            timeout: 15000, // Increased timeout
            maximumAge: 30000, // Allow cached position up to 30 seconds
        };

        navigator.geolocation.getCurrentPosition(
            (position) => {
                console.log("Location obtained:", {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                });
                resolve(position);
            },
            (error) => {
                console.error("Geolocation error:", error);
                handleGeolocationError(error, resolve, reject, retryCount);
            },
            options
        );
    });
};

// Handle different types of geolocation errors
const handleGeolocationError = (error, resolve, reject, retryCount) => {
    const maxRetries = 2;

    switch (error.code) {
        case error.PERMISSION_DENIED:
            reject(
                new Error(
                    "Location access was denied. Please enable location permissions in your browser settings and try again."
                )
            );
            break;

        case error.POSITION_UNAVAILABLE:
            if (retryCount < maxRetries) {
                console.log(
                    `Retrying location fetch... Attempt ${retryCount + 1}`
                );
                setTimeout(() => {
                    getCurrentPosition(retryCount + 1)
                        .then(resolve)
                        .catch(reject);
                }, 2000);
            } else {
                reject(
                    new Error(
                        "Location information is unavailable. Please check your GPS/location services and try again."
                    )
                );
            }
            break;

        case error.TIMEOUT:
            if (retryCount < maxRetries) {
                console.log(
                    `Location timeout, retrying... Attempt ${retryCount + 1}`
                );
                setTimeout(() => {
                    getCurrentPosition(retryCount + 1)
                        .then(resolve)
                        .catch(reject);
                }, 3000);
            } else {
                reject(
                    new Error(
                        "Location request timed out. Please ensure you have a stable internet connection and try again."
                    )
                );
            }
            break;

        default:
            reject(
                new Error(
                    "An unknown error occurred while getting your location. Please try again."
                )
            );
            break;
    }
};

// Handle location errors with user-friendly messages
const handleLocationError = (error) => {
    let errorMessage = "Failed to get location. ";

    if (error.message) {
        errorMessage += error.message;
    } else {
        errorMessage +=
            "Please ensure location services are enabled and you have granted permission.";
    }

    locationError.value = errorMessage;

    // Additional troubleshooting tips
    setTimeout(() => {
        locationError.value +=
            "\n\nTroubleshooting tips:\n" +
            "• Make sure location services are enabled on your device\n" +
            "• Grant location permission to this website\n" +
            "• Try refreshing the page and trying again\n" +
            "• If indoors, try moving closer to a window\n" +
            "• Check your internet connection";
    }, 1000);
};

// Format time
const formatTime = (dateTime) => {
    if (!dateTime) return "-";
    return new Date(dateTime).toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
    });
};

// Format date
const formatDate = (dateTime) => {
    if (!dateTime) return "-";
    return new Date(dateTime).toLocaleDateString("id-ID", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

// Get status badge class
const getStatusClass = (status) => {
    switch (status) {
        case "checked_in":
            return "bg-success-100 text-success";
        case "checked_out":
            return "bg-info-100 text-info";
        default:
            return "bg-surface-3 text-text-3";
    }
};

// Get status text
const getStatusText = (status) => {
    switch (status) {
        case "checked_in":
            return "Checked In";
        case "checked_out":
            return "Checked Out";
        default:
            return "Unknown";
    }
};
</script>

<template>
    <Head title="Absensi" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-text">Absensi</h1>
                    <p class="mt-2 text-muted">
                        Kelola kehadiran Anda di {{ outlet?.name || "Outlet" }}
                    </p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <Card>
                        <div class="p-6">
                            <div class="flex items-center">
                                <div
                                    class="flex-shrink-0 bg-primary-100 rounded-lg p-3"
                                >
                                    <svg
                                        class="w-6 h-6 text-primary"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted">
                                        Total Kehadiran
                                    </p>
                                    <p class="text-2xl font-bold text-text">
                                        {{ stats.total_attendances }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div class="p-6">
                            <div class="flex items-center">
                                <div
                                    class="flex-shrink-0 bg-info-100 rounded-lg p-3"
                                >
                                    <svg
                                        class="w-6 h-6 text-info"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted">
                                        Bulan Ini
                                    </p>
                                    <p class="text-2xl font-bold text-text">
                                        {{ stats.this_month }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div class="p-6">
                            <div class="flex items-center">
                                <div
                                    class="flex-shrink-0 bg-success-100 rounded-lg p-3"
                                >
                                    <svg
                                        class="w-6 h-6 text-success"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted">
                                        Tepat Waktu
                                    </p>
                                    <p class="text-2xl font-bold text-text">
                                        {{ stats.on_time }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Today's Status & Actions -->
                <Card class="mb-8">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-text mb-4">
                            Status Hari Ini
                        </h2>

                        <div v-if="todayAttendance" class="mb-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted">Status</p>
                                    <span
                                        :class="[
                                            'inline-flex px-3 py-1 rounded-full text-sm font-medium',
                                            getStatusClass(
                                                todayAttendance.status
                                            ),
                                        ]"
                                    >
                                        {{
                                            getStatusText(
                                                todayAttendance.status
                                            )
                                        }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-muted">Check In</p>
                                    <p class="text-lg font-semibold text-text">
                                        {{
                                            formatTime(
                                                todayAttendance.check_in_time
                                            )
                                        }}
                                    </p>
                                </div>
                                <div
                                    v-if="todayAttendance.check_out_time"
                                    class="text-right"
                                >
                                    <p class="text-sm text-muted">Check Out</p>
                                    <p class="text-lg font-semibold text-text">
                                        {{
                                            formatTime(
                                                todayAttendance.check_out_time
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Location Error Display -->
                        <div
                            v-if="locationError"
                            class="mb-4 p-4 bg-error-50 border border-error rounded-lg"
                        >
                            <div class="flex items-start">
                                <svg
                                    class="w-5 h-5 text-error mt-0.5 mr-2 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                                <div
                                    class="text-sm text-error whitespace-pre-line"
                                >
                                    {{ locationError }}
                                </div>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div
                            v-if="isGettingLocation"
                            class="mb-4 p-4 bg-info-50 border border-info rounded-lg"
                        >
                            <div class="flex items-center">
                                <svg
                                    class="animate-spin h-5 w-5 text-info mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    ></path>
                                </svg>
                                <span class="text-sm text-info">
                                    Mendapatkan lokasi Anda...
                                </span>
                            </div>
                        </div>

                        <div class="flex space-x-4">
                            <Button
                                v-if="canCheckIn"
                                @click="checkIn"
                                variant="success"
                                :disabled="!outlet || isGettingLocation"
                            >
                                <svg
                                    v-if="isGettingLocation"
                                    class="animate-spin h-5 w-5 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    ></path>
                                </svg>
                                <svg
                                    v-else
                                    class="w-5 h-5 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                                {{
                                    isGettingLocation
                                        ? "Mendapatkan Lokasi..."
                                        : "Check In"
                                }}
                            </Button>

                            <Button
                                v-if="canCheckOut"
                                @click="checkOut"
                                variant="danger"
                                :disabled="!outlet || isGettingLocation"
                            >
                                <svg
                                    v-if="isGettingLocation"
                                    class="animate-spin h-5 w-5 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    ></path>
                                </svg>
                                <svg
                                    v-else
                                    class="w-5 h-5 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                    ></path>
                                </svg>
                                {{
                                    isGettingLocation
                                        ? "Mendapatkan Lokasi..."
                                        : "Check Out"
                                }}
                            </Button>
                        </div>

                        <div
                            v-if="!outlet"
                            class="mt-4 p-4 bg-warning-50 border border-warning rounded-lg"
                        >
                            <p class="text-sm text-warning">
                                Anda belum ditugaskan ke outlet mana pun.
                                Silakan hubungi administrator.
                            </p>
                        </div>
                    </div>
                </Card>

                <!-- Attendance History -->
                <Card>
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-text mb-4">
                            Riwayat Kehadiran
                        </h2>

                        <div class="overflow-x-auto">
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
                                            Outlet
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider"
                                        >
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-surface-1 divide-y divide-border"
                                >
                                    <tr
                                        v-for="attendance in attendances.data"
                                        :key="attendance.id"
                                    >
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-text"
                                        >
                                            {{
                                                formatDate(
                                                    attendance.created_at
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-text"
                                        >
                                            {{
                                                formatTime(
                                                    attendance.check_in_time
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-text"
                                        >
                                            {{
                                                formatTime(
                                                    attendance.check_out_time
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-text"
                                        >
                                            {{ attendance.outlet?.name || "-" }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="[
                                                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                                    getStatusClass(
                                                        attendance.status
                                                    ),
                                                ]"
                                            >
                                                {{
                                                    getStatusText(
                                                        attendance.status
                                                    )
                                                }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div
                            v-if="attendances.links"
                            class="mt-6 flex justify-center"
                        >
                            <template
                                v-for="(link, index) in attendances.links"
                                :key="index"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-2 mx-1 text-sm rounded-md',
                                        link.active
                                            ? 'bg-primary text-white'
                                            : 'bg-surface-1 text-text border border-border hover:bg-surface-2',
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-2 mx-1 text-sm text-muted"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </Card>
            </div>
        </div>

        <!-- Camera Modal -->
        <CameraModal
            :show="showCameraModal"
            :title="
                cameraAction === 'checkin'
                    ? 'Ambil Foto Check-in'
                    : 'Ambil Foto Check-out'
            "
            @close="handleCameraClose"
            @capture="handleCameraCapture"
            @confirm="handleCameraConfirm"
        />

        <!-- Processing Overlay -->
        <div
            v-if="isProcessing"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
            <div
                class="bg-surface-1 rounded-lg p-6 flex items-center space-x-4"
            >
                <svg
                    class="animate-spin h-6 w-6 text-primary"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>
                <span class="text-text">Memproses...</span>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.computed {
    /* Ensure computed properties work correctly */
}
</style>
