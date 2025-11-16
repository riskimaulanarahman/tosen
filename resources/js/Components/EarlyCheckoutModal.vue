<script setup>
import { ref, computed, watch } from "vue";
import Button from "@/Components/ui/Button.vue";
import { useAttendanceState } from "@/utils/attendanceState";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "submit"]);

// Use shared attendance state
const { checkoutRemarks, resetCheckoutRemarks } = useAttendanceState();

// Local state for form
const justification = ref("");
const isSubmitting = ref(false);
const errorMessage = ref("");

// Validation
const isFormValid = computed(() => {
    return justification.value.trim().length >= 10; // Minimum 10 characters
});

// Watch for modal close to reset form
watch(
    () => props.show,
    (newValue) => {
        if (!newValue) {
            justification.value = "";
            errorMessage.value = "";
            isSubmitting.value = false;
        }
    }
);

const handleSubmit = async () => {
    if (!isFormValid.value) {
        errorMessage.value = "Justifikasi wajib diisi (minimal 10 karakter)";
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = "";

    try {
        // Update shared state with justification
        checkoutRemarks.value = justification.value.trim();

        // Emit submit event with justification
        emit("submit", {
            justification: justification.value.trim(),
            timestamp: new Date().toISOString(),
        });

        // Close modal
        emit("close");
    } catch (error) {
        console.error("Early checkout submission error:", error);
        errorMessage.value = "Gagal mengirim justifikasi. Silakan coba lagi.";
        isSubmitting.value = false;
    }
};

const handleClose = () => {
    emit("close");
};

const handleCancel = () => {
    // Reset form when cancelled
    justification.value = "";
    errorMessage.value = "";
    isSubmitting.value = false;
    emit("close");
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    >
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div
                            class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center"
                        >
                            <svg
                                class="w-6 h-6 text-yellow-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h-01m-4 0h18M9 5l7-7-7 7"
                                ></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Early Checkout Detected
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            Anda mencoba checkout lebih awal dari jadwal yang
                            ditentukan
                        </p>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="px-6 py-4">
                <!-- Warning Message -->
                <div
                    class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg"
                >
                    <div class="flex">
                        <svg
                            class="w-5 h-5 text-yellow-600 mr-2"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M8.257 3.014a3 3 0 00-3 3h.01a3 3 0 003 3v.01a3 3 0 003 3h.01a3 3 0 00-3 3z"
                                clip-rule="evenodd"
                            ></path>
                        </svg>
                        <div class="text-sm text-yellow-800">
                            <p class="font-medium">
                                Early checkout memerlukan justifikasi
                            </p>
                            <p class="text-xs mt-1">
                                Pastikan Anda memiliki alasan yang valid untuk
                                checkout lebih awal dari jadwal normal.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="space-y-4">
                    <div>
                        <label
                            for="justification"
                            class="block text-sm font-medium text-gray-700 mb-2"
                        >
                            Justifikasi Early Checkout
                            <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="justification"
                            v-model="justification"
                            :disabled="isSubmitting"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-sm resize-none"
                            placeholder="Silakan berikan justifikasi untuk early checkout (wajib diisi)"
                        ></textarea>
                        <div class="flex justify-between mt-1">
                            <span class="text-xs text-gray-500">
                                Minimal 10 karakter
                            </span>
                            <span
                                class="text-xs"
                                :class="
                                    justification.length > 200
                                        ? 'text-red-500'
                                        : 'text-gray-500'
                                "
                            >
                                {{ justification.length }} / 200 karakter
                            </span>
                        </div>
                        <!-- Error Message -->
                        <div
                            v-if="errorMessage"
                            class="mt-2 text-sm text-red-600"
                        >
                            {{ errorMessage }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg"
            >
                <div class="flex justify-end space-x-3">
                    <Button
                        @click="handleCancel"
                        :disabled="isSubmitting"
                        variant="secondary"
                        class="px-4 py-2"
                    >
                        Batal
                    </Button>
                    <Button
                        @click="handleSubmit"
                        :disabled="isSubmitting || !isFormValid"
                        variant="primary"
                        class="px-4 py-2"
                    >
                        <span v-if="isSubmitting" class="flex items-center">
                            <svg
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                                    d="M4 12a8 8 0 018-8v8a8 8 0 018 8z"
                                ></path>
                            </svg>
                            Mengirim...
                        </span>
                        <span v-else>Kirim Justifikasi & Checkout</span>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fixed {
    position: fixed;
}

.inset-0 {
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
}

.bg-opacity-50 {
    background-color: rgba(0, 0, 0, 0.5);
}

.z-50 {
    z-index: 50;
}

.resize-none {
    resize: none;
}

.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
