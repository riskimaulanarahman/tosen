<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed, watch } from "vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: "Ambil Foto Selfie",
    },
    cancelButtonTitle: {
        type: String,
        default: "Batal",
    },
    captureButtonTitle: {
        type: String,
        default: "Ambil Foto",
    },
    retakeButtonTitle: {
        type: String,
        default: "Ambil Ulang",
    },
    confirmButtonTitle: {
        type: String,
        default: "Gunakan Foto",
    },
});

const emit = defineEmits(["close", "capture", "confirm"]);

// Camera states
const stream = ref(null);
const video = ref(null);
const canvas = ref(null);
const capturedImage = ref(null);
const isCameraReady = ref(false);
const cameraError = ref("");
const isLoading = ref(true);
const isCapturing = ref(false);

// Camera constraints
const cameraConstraints = {
    video: {
        width: { ideal: 1280 },
        height: { ideal: 720 },
        facingMode: "user", // Front camera
        aspectRatio: { ideal: 16 / 9 },
    },
    audio: false,
};

// Initialize camera
const waitForVideoElement = async (attempts = 10) => {
    for (let i = 0; i < attempts; i++) {
        await nextTick();
        if (video.value) {
            return true;
        }
        // wait a bit before next attempt
        await new Promise((resolve) => setTimeout(resolve, 100));
    }
    return false;
};

const initializeCamera = async () => {
    try {
        isLoading.value = true;
        cameraError.value = "";

        const videoReady = await waitForVideoElement();
        if (!videoReady) {
            cameraError.value =
                "Element video tidak ditemukan. Silakan refresh halaman.";
            isLoading.value = false;
            return;
        }

        // Check if camera is supported
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error(
                "Kamera tidak didukung pada browser ini. Silakan gunakan browser modern."
            );
        }

        console.log(
            "Requesting camera permission with constraints:",
            cameraConstraints
        );

        // Request camera permission with more permissive constraints as fallback
        try {
            stream.value = await navigator.mediaDevices.getUserMedia(
                cameraConstraints
            );
        } catch (constraintError) {
            console.log(
                "Primary constraints failed, trying fallback:",
                constraintError
            );
            // Fallback to basic constraints
            const fallbackConstraints = {
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: "user",
                },
                audio: false,
            };
            stream.value = await navigator.mediaDevices.getUserMedia(
                fallbackConstraints
            );
        }

        console.log("Camera stream obtained:", stream.value);

        // Attach stream to video element
        if (video.value) {
            video.value.srcObject = stream.value;

            // Wait for video to be ready
            video.value.onloadedmetadata = () => {
                console.log("Video metadata loaded");
                video.value
                    .play()
                    .then(() => {
                        console.log("Video playing successfully");
                        isCameraReady.value = true;
                        isLoading.value = false;
                    })
                    .catch((playError) => {
                        console.error("Video play error:", playError);
                        cameraError.value =
                            "Gagal memulai video. Silakan refresh halaman.";
                        isLoading.value = false;
                    });
            };

            video.value.onerror = (error) => {
                console.error("Video error:", error);
                cameraError.value =
                    "Error saat memuat video. Silakan coba lagi.";
                isLoading.value = false;
            };
        } else {
            console.error("Video element not found");
            cameraError.value =
                "Element video tidak ditemukan. Silakan refresh halaman.";
            isLoading.value = false;
        }
    } catch (error) {
        console.error("Camera initialization error:", error);
        cameraError.value = getCameraErrorMessage(error);
        isLoading.value = false;
        // Also show error with SweetAlert for better visibility
        handleError(error, getCameraErrorMessage(error));
    }
};

// Get user-friendly camera error message
const getCameraErrorMessage = (error) => {
    switch (error.name) {
        case "NotAllowedError":
        case "PermissionDeniedError":
            return "Akses kamera ditolak. Silakan izinkan akses kamera di browser Anda dan refresh halaman.";

        case "NotFoundError":
        case "DevicesNotFoundError":
            return "Tidak ada kamera yang ditemukan. Pastikan kamera terhubung dan tidak digunakan oleh aplikasi lain.";

        case "NotSupportedError":
            return "Kamera tidak didukung. Silakan gunakan browser yang mendukung WebRTC (Chrome, Firefox, Safari, Edge).";

        case "NotReadableError":
        case "TrackStartError":
            return "Kamera sedang digunakan oleh aplikasi lain. Silakan tutup aplikasi lain yang menggunakan kamera dan coba lagi.";

        case "OverconstrainedError":
        case "ConstraintNotSatisfiedError":
            return "Kamera tidak memenuhi persyaratan. Mencoba dengan pengaturan default...";

        case "TypeError":
            return "Terjadi kesalahan saat mengakses kamera. Silakan refresh halaman dan coba lagi.";

        default:
            return (
                error.message ||
                "Terjadi kesalahan yang tidak diketahui saat mengakses kamera."
            );
    }
};

// Capture photo from video stream
const capturePhoto = () => {
    if (!video.value || !canvas.value || !isCameraReady.value) {
        return;
    }

    try {
        isCapturing.value = true;

        // Set canvas dimensions to match video
        canvas.value.width = video.value.videoWidth;
        canvas.value.height = video.value.videoHeight;

        // Get canvas context
        const context = canvas.value.getContext("2d");

        // Draw video frame to canvas
        context.drawImage(
            video.value,
            0,
            0,
            canvas.value.width,
            canvas.value.height
        );

        // Add timestamp overlay
        const now = new Date();
        const timestamp = now.toLocaleString("id-ID", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });

        // Configure text style
        context.fillStyle = "white";
        context.strokeStyle = "black";
        context.lineWidth = 2;
        context.font = "bold 16px Arial";
        context.textAlign = "right";
        context.textBaseline = "bottom";

        // Add text shadow for better visibility
        context.shadowColor = "rgba(0, 0, 0, 0.8)";
        context.shadowBlur = 4;
        context.shadowOffsetX = 2;
        context.shadowOffsetY = 2;

        // Draw timestamp
        const padding = 10;
        const textX = canvas.value.width - padding;
        const textY = canvas.value.height - padding;

        context.strokeText(timestamp, textX, textY);
        context.fillText(timestamp, textX, textY);

        // Get image data as blob
        canvas.value.toBlob(
            (blob) => {
                if (blob) {
                    capturedImage.value = blob;
                    emit("capture", blob);
                }
                isCapturing.value = false;
            },
            "image/jpeg",
            0.9
        );
    } catch (error) {
        console.error("Capture photo error:", error);
        cameraError.value = "Gagal mengambil foto. Silakan coba lagi.";
        isCapturing.value = false;
        // Also show error with SweetAlert for better visibility
        handleError(error, "Gagal mengambil foto. Silakan coba lagi.");
    }
};

// Retake photo
const retakePhoto = () => {
    capturedImage.value = null;
    cameraError.value = "";
};

// Confirm captured photo
const confirmPhoto = () => {
    if (capturedImage.value) {
        emit("confirm", capturedImage.value);
        closeModal();
    }
};

// Close modal and cleanup
const closeModal = () => {
    emit("close");
    cleanupCamera();
};

// Cleanup camera resources
const cleanupCamera = () => {
    // Stop video stream
    if (stream.value) {
        stream.value.getTracks().forEach((track) => {
            track.stop();
        });
        stream.value = null;
    }

    // Clear video source
    if (video.value) {
        video.value.srcObject = null;
    }

    // Reset states
    isCameraReady.value = false;
    isLoading.value = true;
    capturedImage.value = null;
    cameraError.value = "";
    isCapturing.value = false;
};

// Get preview URL for captured image
const capturedImageUrl = computed(() => {
    if (!capturedImage.value) return null;
    return URL.createObjectURL(capturedImage.value);
});

// Watch for modal visibility changes
watch(
    () => props.show,
    async (newValue) => {
        if (newValue) {
            await initializeCamera();
        } else {
            cleanupCamera();
        }
    }
);

// Cleanup on component unmount
onUnmounted(() => {
    cleanupCamera();
});

// Handle page visibility change (pause/resume camera)
const handleVisibilityChange = () => {
    if (document.hidden) {
        // Page hidden, pause camera
        if (video.value) {
            video.value.pause();
        }
    } else {
        // Page visible, resume camera
        if (video.value && isCameraReady.value) {
            video.value.play();
        }
    }
};

onMounted(() => {
    document.addEventListener("visibilitychange", handleVisibilityChange);
});

onUnmounted(() => {
    document.removeEventListener("visibilitychange", handleVisibilityChange);
});
</script>

<template>
    <Modal :show="show" @close="closeModal" max-width="2xl">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl">
            <!-- Header -->
            <div
                class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center justify-between">
                    <h3
                        class="text-lg font-medium text-gray-900 dark:text-white"
                    >
                        {{ title }}
                    </h3>
                    <button
                        @click="closeModal"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="px-6 py-4">
                <!-- Camera Error -->
                <div v-if="cameraError" class="text-center py-8">
                    <div class="mb-4">
                        <svg
                            class="w-16 h-16 text-red-500 mx-auto"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>
                    <p class="text-red-600 dark:text-red-400 mb-4">
                        {{ cameraError }}
                    </p>
                    <button
                        @click="initializeCamera"
                        class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500"
                    >
                        Coba Lagi
                    </button>
                </div>

                <!-- Captured Image Preview -->
                <div v-else-if="capturedImage" class="space-y-4">
                    <!-- Image Preview -->
                    <div
                        class="relative bg-black rounded-lg overflow-hidden"
                        style="aspect-ratio: 16/9"
                    >
                        <img
                            :src="capturedImageUrl"
                            alt="Captured selfie"
                            class="w-full h-full object-cover"
                        />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-center space-x-4">
                        <button
                            @click="retakePhoto"
                            class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            {{ retakeButtonTitle }}
                        </button>
                        <button
                            @click="confirmPhoto"
                            class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                        >
                            {{ confirmButtonTitle }}
                        </button>
                    </div>
                </div>

                <!-- Camera View -->
                <div v-else class="space-y-4">
                    <!-- Video Preview -->
                    <div
                        class="relative bg-black rounded-lg overflow-hidden"
                        style="aspect-ratio: 16/9"
                    >
                        <video
                            ref="video"
                            class="w-full h-full object-cover"
                            autoplay
                            playsinline
                            muted
                        />

                        <!-- Loading overlay -->
                        <div
                            v-if="isLoading"
                            class="absolute inset-0 flex flex-col items-center justify-center bg-black/60 text-white space-y-3"
                        >
                            <svg
                                class="animate-spin h-10 w-10 text-orange-400"
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
                            <span class="text-sm font-medium"
                                >Mengaktifkan kamera...</span
                            >
                        </div>

                        <!-- Camera Overlay -->
                        <div class="absolute inset-0 pointer-events-none">
                            <!-- Face guide overlay -->
                            <div
                                class="flex items-center justify-center h-full"
                            >
                                <div class="relative">
                                    <!-- Face oval guide -->
                                    <div
                                        class="w-48 h-64 border-2 border-white border-opacity-50 rounded-full"
                                    ></div>

                                    <!-- Corner indicators -->
                                    <div
                                        class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-white"
                                    ></div>
                                    <div
                                        class="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-white"
                                    ></div>
                                    <div
                                        class="absolute bottom-0 left-0 w-8 h-8 border-b-2 border-l-2 border-white"
                                    ></div>
                                    <div
                                        class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-white"
                                    ></div>
                                </div>
                            </div>

                            <!-- Instructions -->
                            <div
                                class="absolute bottom-4 left-0 right-0 text-center"
                            >
                                <p
                                    class="text-white text-sm bg-black bg-opacity-50 inline-block px-4 py-2 rounded"
                                >
                                    Pastikan wajah Anda terlihat jelas dalam
                                    frame
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Capture Button -->
                    <div class="flex justify-center">
                        <button
                            @click="capturePhoto"
                            :disabled="!isCameraReady || isCapturing"
                            class="relative w-20 h-20 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-400 rounded-full flex items-center justify-center transition-colors focus:outline-none focus:ring-4 focus:ring-orange-300"
                        >
                            <div class="w-16 h-16 bg-white rounded-full"></div>
                            <svg
                                v-if="isCapturing"
                                class="absolute animate-spin w-8 h-8 text-orange-600"
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
                        </button>
                    </div>

                    <!-- Status Text -->
                    <div class="text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{
                                isLoading
                                    ? "Mengaktifkan kamera..."
                                    : isCameraReady
                                    ? "Klik tombol untuk mengambil foto"
                                    : "Menunggu kamera siap..."
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div
                v-if="!capturedImage && !cameraError"
                class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"
            >
                <div class="flex justify-end">
                    <button
                        @click="closeModal"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                        {{ cancelButtonTitle }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden canvas for image processing -->
        <canvas ref="canvas" class="hidden" />
    </Modal>
</template>

<style scoped>
/* Custom animations */
@keyframes pulse-ring {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.7;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-pulse-ring {
    animation: pulse-ring 2s ease-in-out infinite;
}
</style>
