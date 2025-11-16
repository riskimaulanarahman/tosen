<script setup>
import { ref, computed, watch } from "vue";
import Button from "@/Components/ui/Button.vue";

const props = defineProps({
    show: Boolean,
    validation: Object,
    remarks: String,
});

const emit = defineEmits(["close", "confirm", "update:remarks"]);

const localRemarks = ref(props.remarks || "");

// Watch for prop changes
watch(
    () => props.remarks,
    (newValue) => {
        localRemarks.value = newValue || "";
    }
);

const isFormValid = computed(() => {
    if (
        !props.validation.requiresEarlyCheckoutRemarks &&
        !props.validation.requiresOvertimeRemarks
    ) {
        return true;
    }

    const minLength = props.validation.requiresEarlyCheckoutRemarks
        ? props.validation.earlyCheckoutRules.min
        : props.validation.overtimeRules.min;

    const maxLength = props.validation.requiresEarlyCheckoutRemarks
        ? props.validation.earlyCheckoutRules.max
        : props.validation.overtimeRules.max;

    return (
        localRemarks.value.length >= minLength &&
        localRemarks.value.length <= maxLength
    );
});

const title = computed(() => {
    if (props.validation.requiresEarlyCheckoutRemarks) {
        return "⚠️ Early Checkout Detected";
    }
    if (props.validation.requiresOvertimeRemarks) {
        return "⏰ Overtime Detected";
    }
    return "📝 Checkout Remarks";
});

const subtitle = computed(() => {
    if (props.validation.requiresEarlyCheckoutRemarks) {
        return `Work duration: ${Math.floor(
            props.validation.workDurationMinutes / 60
        )}h ${
            props.validation.workDurationMinutes % 60
        }m (minimum: ${Math.floor(
            props.validation.earlyCheckoutThreshold / 60
        )}h)`;
    }
    if (props.validation.requiresOvertimeRemarks) {
        return `Overtime: ${Math.floor(
            props.validation.overtimeMinutes / 60
        )}h ${props.validation.overtimeMinutes % 60}m`;
    }
    return "";
});

const placeholder = computed(() => {
    if (props.validation.requiresEarlyCheckoutRemarks) {
        return "Mohon isi alasan early checkout (contoh: ada urusan penting, sakit, dll)...";
    }
    if (props.validation.requiresOvertimeRemarks) {
        return "Mohon isi alasan overtime (contoh: project mendesak, covering rekan, dll)...";
    }
    return "Catatan checkout...";
});

const characterLimits = computed(() => {
    if (props.validation.requiresEarlyCheckoutRemarks) {
        return props.validation.earlyCheckoutRules;
    }
    if (props.validation.requiresOvertimeRemarks) {
        return props.validation.overtimeRules;
    }
    return { min: 10, max: 300 };
});

const handleConfirm = () => {
    if (isFormValid.value) {
        emit("confirm", localRemarks.value);
    }
};

const handleClose = () => {
    emit("close");
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
                <p v-if="subtitle" class="text-sm text-gray-600 mt-1">
                    {{ subtitle }}
                </p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    📝
                    {{
                        validation.requiresEarlyCheckoutRemarks
                            ? "Justifikasi Early Checkout"
                            : validation.requiresOvertimeRemarks
                            ? "Catatan Overtime"
                            : "Catatan Checkout"
                    }}
                    <span class="text-red-500">*</span>
                </label>
                <textarea
                    v-model="localRemarks"
                    :min="characterLimits.min"
                    :max="characterLimits.max"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                    :placeholder="placeholder"
                ></textarea>
                <div class="flex justify-between mt-1">
                    <span class="text-xs text-gray-500">
                        Minimum {{ characterLimits.min }} karakter
                    </span>
                    <span class="text-xs text-gray-500">
                        {{ localRemarks.length }} / {{ characterLimits.max }}
                    </span>
                </div>
            </div>

            <div class="flex space-x-3">
                <Button @click="handleClose" variant="secondary" class="flex-1">
                    Batal
                </Button>
                <Button
                    @click="handleConfirm"
                    :disabled="!isFormValid"
                    variant="primary"
                    class="flex-1"
                >
                    Lanjutkan Checkout
                </Button>
            </div>
        </div>
    </div>
</template>
