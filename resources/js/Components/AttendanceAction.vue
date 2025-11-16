<script setup>
import { ref, computed } from "vue";
import CameraModal from "@/Components/CameraModal.vue";
import Button from "@/Components/ui/Button.vue";
import { useAttendanceState } from "@/utils/attendanceState";

const props = defineProps({
    action: {
        type: String,
        required: true,
        validator: (value) => ["checkin", "checkout"].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    outlet: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["success", "error"]);

// Use shared attendance state
const { canCheckIn, canCheckOut } = useAttendanceState();

// Component state
const showCameraModal = ref(false);
const isProcessing = ref(false);
const locationStatus = ref("idle"); // idle, getting, success, error
const locationError = ref("");

// Computed properties
const actionTitle = computed(() => {
    return props.action === "checkin" ? "Check In" : "Check Out";
});

const actionColor = computed(() => {
    return props.action === "checkin"
        ? "bg-green-600 hover:bg-green-700"
        : "bg-blue-600 hover:bg-blue-700";
});

const isLocationReady = computed(() => {
    return locationStatus.value === "success";
});

// Check if action is enabled based on shared state
const isActionEnabled = computed(() => {
    if (props.action === "checkin") {
        return canCheckIn.value && !props.disabled;
    } else if (props.action === "checkout") {
        return canCheckOut.value && !props.disabled;
    }
    return false;
});

// Simplified location detection
const getLocation = async () => {
    if (locationStatus.value === "getting") return null;

    locationStatus.value = "getting";
    locationError.value = "";

    try {
        const position = await new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error("Geolocation tidak didukung"));
                return;
            }

            navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 60000,
            });
        });

        locationStatus.value = "success";
        return position;
    } catch (error) {
        locationStatus.value = "error";
        locationError.value = error.message;
        throw error;
    }
};

// Simplified attendance processing
const handleAttendance = async (imageBlob) => {
    if (isProcessing.value) return;

    isProcessing.value = true;

    try {
        // Get location
        const position = await getLocation();

        // Prepare form data
        const formData = new FormData();
        formData.append("latitude", position.coords.latitude);
        formData.append("longitude", position.coords.longitude);
        formData.append("accuracy", position.coords.accuracy);
        formData.append("selfie", imageBlob, `selfie_${Date.now()}.jpg`);

        // Send request
        const endpoint =
            props.action === "checkin"
                ? "/attendance/checkin"
                : "/attendance/checkout";
        const response = await axios.post(endpoint, formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        if (response.data.success) {
            // Reset retry counters on success
            window.enhancedErrorHandler?.resetRetries(props.action);

            emit("success", {
                message: `${actionTitle.value} berhasil!`,
                data: response.data,
            });
        } else {
            throw new Error(response.data.message);
        }
    } catch (error) {
        // Use enhanced error handler if available
        if (window.enhancedErrorHandler) {
            const shouldRetry = await window.enhancedErrorHandler.handleError(
                error,
                null,
                {
                    action: props.action,
                    retryCallback: () => handleAttendance(imageBlob),
                }
            );

            if (!shouldRetry) {
                emit("error", {
                    message:
                        error.message ||
                        "Gagal melakukan " + actionTitle.value.toLowerCase(),
                    error: error,
                });
            }
        } else {
            emit("error", {
                message:
                    error.message ||
                    "Gagal melakukan " + actionTitle.value.toLowerCase(),
                error: error,
            });
        }
    } finally {
        isProcessing.value = false;
    }
};

// Handle camera confirmation
const handleCameraConfirm = (imageBlob) => {
    showCameraModal.value = false;
    handleAttendance(imageBlob);
};

// Handle camera error
const handleCameraError = (error) => {
    showCameraModal.value = false;
    emit("error", {
        message: "Gagal mengakses kamera: " + error.message,
        error: error,
    });
};

// Start attendance process
const startAttendance = () => {
    if (props.disabled || isProcessing.value || !isActionEnabled.value) return;
    showCameraModal.value = true;
};
</script>

<template>
    <div class="attendance-action">
        <!-- Main Action Button -->
        <Button
            @click="startAttendance"
            :disabled="disabled || isProcessing || !isActionEnabled"
            :class="actionColor"
            class="w-full py-3 text-lg font-semibold"
        >
            <div class="flex items-center justify-center space-x-2">
                <!-- Loading Spinner -->
                <svg
                    v-if="isProcessing"
                    class="animate-spin h-5 w-5"
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

                <!-- Action Icon -->
                <svg
                    v-else
                    class="h-6 w-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        v-if="action === 'checkin'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                    />
                    <path
                        v-else
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                    />
                </svg>

                <span>{{ isProcessing ? "Memproses..." : actionTitle }}</span>
            </div>
        </Button>

        <!-- Location Status Indicator -->
        <div
            v-if="locationStatus === 'error'"
            class="mt-2 text-sm text-red-600 text-center"
        >
            {{ locationError }}
        </div>

        <!-- Outlet Info -->
        <div v-if="outlet" class="mt-3 text-sm text-gray-600 text-center">
            <div class="font-medium">{{ outlet.name }}</div>
            <div class="text-xs">{{ outlet.formatted_operational_hours }}</div>
        </div>

        <!-- Camera Modal -->
        <CameraModal
            :show="showCameraModal"
            :title="`Ambil Foto ${actionTitle}`"
            @close="showCameraModal = false"
            @capture="() => {}"
            @confirm="handleCameraConfirm"
            @error="handleCameraError"
        />
    </div>
</template>

<style scoped>
.attendance-action {
    @apply max-w-sm mx-auto;
}
</style>
