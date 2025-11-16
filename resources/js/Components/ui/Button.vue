<script setup lang="ts">
import { computed } from "vue";

interface Props {
    variant?:
        | "primary"
        | "secondary"
        | "ghost"
        | "danger"
        | "success"
        | "gradient"
        | "accent"
        | "info"
        | "warning";
    size?: "sm" | "md" | "lg" | "xl";
    disabled?: boolean;
    type?: "button" | "submit" | "reset";
    loading?: boolean;
    icon?: boolean;
    rounded?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: "primary",
    size: "md",
    type: "button",
    disabled: false,
    loading: false,
    icon: false,
    rounded: false,
});

const emit = defineEmits<{
    click: [event: MouseEvent];
}>();

const classes = computed(() => {
    const base = [
        "inline-flex items-center justify-center font-semibold transition-all duration-normal",
        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-surface-1",
        "disabled:opacity-50 disabled:cursor-not-allowed",
        "relative overflow-hidden group",
        "transform hover:scale-105 active:scale-95",
    ];

    const variants = {
        primary: [
            "bg-gradient-primary text-white",
            "hover:opacity-95",
            "focus-visible:ring-primary-400",
            "shadow-glow hover:shadow-glow-accent",
            "relative overflow-hidden",
        ],
        secondary: [
            "bg-surface-1 text-primary-700 border-2 border-primary-300",
            "hover:bg-primary-50 hover:text-primary-800 hover:border-primary-400",
            "focus-visible:ring-primary-400",
            "shadow-sm hover:shadow-md",
            "relative overflow-hidden",
        ],
        ghost: [
            "text-primary-600 hover:bg-primary-50 hover:text-primary-700",
            "focus-visible:ring-primary-300",
            "relative overflow-hidden",
        ],
        danger: [
            "bg-gradient-to-r from-error-500 to-error-600 text-white",
            "hover:from-error-600 hover:to-error-700",
            "focus-visible:ring-error-400",
            "shadow-lg hover:shadow-xl",
            "relative overflow-hidden",
        ],
        success: [
            "bg-gradient-to-r from-success-500 to-success-600 text-white",
            "hover:from-success-600 hover:to-success-700",
            "focus-visible:ring-success-400",
            "shadow-lg hover:shadow-xl",
            "relative overflow-hidden",
        ],
        accent: [
            "bg-gradient-to-r from-accent-500 to-accent-600 text-white",
            "hover:from-accent-600 hover:to-accent-600/90",
            "focus-visible:ring-accent-500",
            "shadow-lg hover:shadow-xl",
            "relative overflow-hidden",
        ],
        info: [
            "bg-gradient-to-r from-info-500 to-info-600 text-white",
            "hover:from-info-600 hover:to-info-700",
            "focus-visible:ring-info-500",
            "shadow-lg hover:shadow-xl",
            "relative overflow-hidden",
        ],
        warning: [
            "bg-gradient-to-r from-warning-500 to-warning-600 text-white",
            "hover:from-warning-600 hover:to-warning-700",
            "focus-visible:ring-warning-500",
            "shadow-lg hover:shadow-xl",
            "relative overflow-hidden",
        ],
        gradient: [
            "bg-gradient-to-r from-primary-500 via-accent-500 to-primary-600 text-white",
            "hover:from-primary-600 hover:via-accent-600 hover:to-primary-700",
            "focus-visible:ring-accent-500",
            "shadow-xl hover:shadow-2xl",
            "animate-pulse-glow relative overflow-hidden",
        ],
    };

    const sizes = {
        sm: "px-3 py-1.5 text-xs",
        md: "px-4 py-2 text-sm",
        lg: "px-6 py-3 text-base",
        xl: "px-8 py-4 text-lg",
    };

    const iconSizes = {
        sm: "p-1.5 text-xs",
        md: "p-2 text-sm",
        lg: "p-3 text-base",
        xl: "p-4 text-lg",
    };

    const roundedClasses = {
        true: "rounded-full",
        false: "rounded-lg",
    };

    return [
        ...base,
        ...variants[props.variant],
        props.icon ? iconSizes[props.size] : sizes[props.size],
        roundedClasses[props.rounded ? "true" : "false"],
        props.loading ? "cursor-wait" : "cursor-pointer",
    ].join(" ");
});

const handleClick = (event: MouseEvent) => {
    if (!props.disabled && !props.loading) {
        emit("click", event);
    }
};
</script>

<template>
    <button
        :type="type"
        :class="classes"
        :disabled="disabled || loading"
        @click="handleClick"
    >
        <!-- Enhanced ripple effect overlay -->
        <div
            class="absolute inset-0 bg-white opacity-0 group-hover:opacity-15 transition-opacity duration-fast rounded-inherit"
        ></div>

        <!-- Shimmer effect for all variants -->
        <div
            class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-slow rounded-inherit"
        ></div>

        <!-- Loading spinner with vibrant teal -->
        <svg
            v-if="loading"
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

        <!-- Icon slot -->
        <slot name="icon" v-if="$slots.icon && !loading" />

        <!-- Button content -->
        <span
            :class="{ 'ml-2': $slots.icon && !loading }"
            class="relative z-10"
        >
            <slot />
        </span>

        <!-- Enhanced shine effect for gradient variant -->
        <div
            v-if="variant === 'gradient'"
            class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-slow rounded-inherit"
        ></div>
    </button>
</template>
