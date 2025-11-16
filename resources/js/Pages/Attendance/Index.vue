<script setup>
import { ref, onMounted, computed } from "vue";
import { Head, usePage, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import AttendanceAction from "@/Components/AttendanceAction.vue";
import { useAttendanceState } from "@/utils/attendanceState";

const page = usePage();
const attendances = ref(page.props.attendances);
const stats = ref(page.props.stats);

// Use shared attendance state
const {
    todayAttendance,
    outlet,
    canCheckIn,
    canCheckOut,
    isCheckedIn,
    fetchAttendanceStatus,
} = useAttendanceState();

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

    const performAttendance = async () => {
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
                // Reset retry counters on success
                window.enhancedErrorHandler.resetRetries(cameraAction.value);

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

            // Use enhanced error handler with retry mechanism
            const shouldRetry = await window.enhancedErrorHandler.handleError(
                error,
                null,
                {
                    action: cameraAction.value,
                    retryCallback: performAttendance,
                }
            );

            if (!shouldRetry) {
                handleLocationError(error);
            }
        }
    };

    await performAttendance();

    isProcessing.value = false;
    capturedSelfie.value = null;
    cameraAction.value = "";
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
                    "Geolocation tidak didukung pada browser ini. Silakan gunakan browser modern."
                )
            );
            return;
        }

        const options = {
            enableHighAccuracy: true,
            timeout: 20000, // Increased timeout for better reliability
            maximumAge: 60000, // Allow cached position up to 60 seconds
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
    const maxRetries = 1; // Reduced retries since enhanced handler handles retries

    switch (error.code) {
        case error.PERMISSION_DENIED:
            reject(
                new Error(
                    "Akses lokasi ditolak. Silakan aktifkan izin lokasi di pengaturan browser Anda."
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
                        "Informasi lokasi tidak tersedia. Periksa layanan GPS/lokasi Anda dan coba lagi."
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
                        "Permintaan lokasi timeout. Pastikan Anda memiliki koneksi internet stabil dan coba lagi."
                    )
                );
            }
            break;

        default:
            reject(
                new Error(
                    "Terjadi kesalahan tidak diketahui saat mendapatkan lokasi Anda. Silakan coba lagi."
                )
            );
            break;
    }
};

// Handle location errors with user-friendly messages
const handleLocationError = (error) => {
    let errorMessage = "Gagal mendapatkan lokasi. ";

    if (error.message) {
        errorMessage += error.message;
    } else {
        errorMessage +=
            "Pastikan layanan lokasi aktif dan Anda telah memberikan izin.";
    }

    locationError.value = errorMessage;

    // Additional troubleshooting tips
    setTimeout(() => {
        locationError.value +=
            "\n\nTips troubleshooting:\n" +
            "• Pastikan layanan lokasi aktif di perangkat Anda\n" +
            "• Berikan izin lokasi ke website ini\n" +
            "• Coba refresh halaman dan coba lagi\n" +
            "• Jika di dalam ruangan, coba mendekati jendela\n" +
            "• Periksa koneksi internet Anda";
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

// Handle attendance success
const handleAttendanceSuccess = (data) => {
    // Refresh attendance status after successful action
    fetchAttendanceStatus();
};

// Handle attendance error
const handleAttendanceError = (errorData) => {
    console.error("Attendance error:", errorData);
    // Could show error message here if needed
};
// Initialize state with props
onMounted(() => {
    // Initialize with server-side props
    if (page.props.todayAttendance) {
        todayAttendance.value = page.props.todayAttendance;
    }
    if (page.props.outlet) {
        outlet.value = page.props.outlet;
    }
});
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

                <!-- Attendance Actions -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <AttendanceAction
                        action="checkin"
                        :disabled="!canCheckIn"
                        :outlet="outlet"
                        @success="handleAttendanceSuccess"
                        @error="handleAttendanceError"
                    />
                    <AttendanceAction
                        action="checkout"
                        :disabled="!canCheckOut"
                        :outlet="outlet"
                        @success="handleAttendanceSuccess"
                        @error="handleAttendanceError"
                    />
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
