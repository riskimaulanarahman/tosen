<script setup lang="ts">
import { computed, ref } from "vue";

interface Props {
    modelValue?: string | number;
    type?: "text" | "email" | "password" | "number" | "tel" | "url";
    placeholder?: string;
    disabled?: boolean;
    readonly?: boolean;
    required?: boolean;
    error?: string;
    label?: string;
    id?: string;
    name?: string;
    autocomplete?: string;
    showPasswordToggle?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: "text",
    disabled: false,
    readonly: false,
    required: false,
    showPasswordToggle: false,
});

const emit = defineEmits<{
    "update:modelValue": [value: string | number];
    focus: [event: FocusEvent];
    blur: [event: FocusEvent];
}>();

const inputType = computed(() => {
    if (props.type === "password" && showPasswordToggle.value) {
        return "text";
    }
    return props.type;
});

const showPasswordToggle = ref(false);

const togglePassword = () => {
    showPasswordToggle.value = !showPasswordToggle.value;
};

const classes = computed(() => {
    const base =
        "w-full px-3 py-2 bg-surface-2 border border-border rounded-lg text-text placeholder-text-muted transition-all duration-fast focus:outline-none focus:ring-2 focus:ring-tosen-primary-400 focus:border-tosen-primary-500 disabled:opacity-50 disabled:cursor-not-allowed relative";

    const errorClasses = props.error
        ? "border-tosen-error focus:ring-tosen-error animate-shake"
        : "focus:border-tosen-primary-500 focus:ring-tosen-primary-400/30";

    return [base, errorClasses].join(" ");
});

const inputId = computed(
    () => props.id || `input-${Math.random().toString(36).substr(2, 9)}`
);

const handleInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = props.type === "number" ? Number(target.value) : target.value;
    emit("update:modelValue", value);
};

const handleFocus = (event: FocusEvent) => {
    emit("focus", event);
};

const handleBlur = (event: FocusEvent) => {
    emit("blur", event);
};
</script>

<template>
    <div class="space-y-1">
        <label
            v-if="label"
            :for="inputId"
            class="block text-sm font-medium text-tosen-gray-700 transition-colors duration-fast"
        >
            {{ label }}
            <span v-if="required" class="text-tosen-error ml-1">*</span>
        </label>

        <div class="relative group">
            <input
                :id="inputId"
                :type="inputType"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                :required="required"
                :name="name"
                :autocomplete="autocomplete"
                :class="classes"
                @input="handleInput"
                @focus="handleFocus"
                @blur="handleBlur"
            />

            <!-- Enhanced focus indicator -->
            <div class="absolute inset-0 rounded-lg pointer-events-none">
                <div
                    class="absolute inset-0 rounded-lg border-2 border-tosen-primary-300 opacity-0 transition-opacity duration-fast group-focus-within:opacity-100 scale-105 group-focus-within:translate-y-[-1px]"
                ></div>
            </div>

            <button
                v-if="type === 'password' && showPasswordToggle"
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-tosen-gray-500 hover:text-tosen-primary-600 transition-colors duration-fast group-focus-within:text-tosen-primary-600"
                @click="togglePassword"
            >
                <svg
                    v-if="showPasswordToggle"
                    class="h-5 w-5"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                </svg>
                <svg
                    v-else
                    class="h-5 w-5"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                    />
                </svg>
            </button>
        </div>

        <!-- Enhanced error message with animation -->
        <div v-if="error" class="flex items-start space-x-2 animate-slideDown">
            <svg
                class="w-4 h-4 text-tosen-error mt-0.5 flex-shrink-0"
                fill="currentColor"
                viewBox="0 0 20 20"
            >
                <path
                    fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                />
            </svg>
            <p class="text-sm text-tosen-error">{{ error }}</p>
        </div>
    </div>
</template>

<style scoped>
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes shake {
    0%,
    100% {
        transform: translateX(0);
    }
    10%,
    30%,
    50%,
    70%,
    90% {
        transform: translateX(-2px);
    }
    20%,
    40%,
    60%,
    80% {
        transform: translateX(2px);
    }
}

.animate-slideDown {
    animation: slideDown 0.3s ease-out;
}

.animate-shake {
    animation: shake 0.5s ease-in-out;
}
</style>
