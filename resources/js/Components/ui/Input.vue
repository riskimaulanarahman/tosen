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
        "w-full px-3 py-2 bg-surface-2 border border-border rounded-lg text-text placeholder-text-muted transition-all duration-fast focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed";

    const errorClasses = props.error
        ? "border-error focus:ring-error"
        : "focus:border-primary-400";

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
            class="block text-sm font-medium text-text"
        >
            {{ label }}
            <span v-if="required" class="text-error ml-1">*</span>
        </label>

        <div class="relative">
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

            <button
                v-if="type === 'password' && showPasswordToggle"
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-text-muted hover:text-text transition-colors duration-fast"
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

        <p v-if="error" class="text-sm text-error">
            {{ error }}
        </p>
    </div>
</template>
