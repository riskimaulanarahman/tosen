<script setup lang="ts">
import { computed } from "vue";

interface Props {
    variant?: "default" | "gradient" | "glass" | "bordered";
    padding?: "none" | "sm" | "md" | "lg" | "xl";
    rounded?: "none" | "sm" | "md" | "lg" | "xl" | "2xl" | "full";
    shadow?: "none" | "sm" | "md" | "lg" | "xl" | "2xl" | "glow";
    hover?: boolean;
    clickable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: "default",
    padding: "md",
    rounded: "lg",
    shadow: "md",
    hover: false,
    clickable: false,
});

const emit = defineEmits<{
    click: [event: MouseEvent];
}>();

const classes = computed(() => {
    const base = [
        "relative transition-all duration-normal",
        "backdrop-blur-sm",
    ];

    const variants = {
        default: [
            "bg-surface-1 border border-border",
            "hover:border-primary-500/50",
        ],
        gradient: [
            "bg-gradient-to-br from-surface-1 via-surface-2 to-surface-1",
            "border border-primary-500/20",
            "hover:border-primary-500/40",
            "shadow-lg shadow-primary-500/10",
        ],
        glass: [
            "bg-surface-1/80 backdrop-blur-md border border-white/10",
            "hover:bg-surface-1/90 hover:border-primary-500/30",
            "shadow-xl shadow-black/20",
        ],
        bordered: [
            "bg-gradient-to-br from-surface-1 to-surface-2",
            "border-2 border-border",
            "hover:border-primary-500/60",
            "shadow-lg",
        ],
    };

    const paddings = {
        none: "",
        sm: "p-3",
        md: "p-4",
        lg: "p-6",
        xl: "p-8",
    };

    const roundeds = {
        none: "",
        sm: "rounded-sm",
        md: "rounded-md",
        lg: "rounded-lg",
        xl: "rounded-xl",
        "2xl": "rounded-2xl",
        full: "rounded-full",
    };

    const shadows = {
        none: "",
        sm: "shadow-sm",
        md: "shadow-md",
        lg: "shadow-lg",
        xl: "shadow-xl",
        "2xl": "shadow-2xl",
        glow: "shadow-glow",
    };

    const hoverEffects = props.hover
        ? [
              "transform hover:scale-105 hover:-translate-y-1",
              "transition-all duration-spring",
          ]
        : [];

    const clickableEffects = props.clickable
        ? ["cursor-pointer", "active:scale-95"]
        : [];

    return [
        ...base,
        ...variants[props.variant],
        paddings[props.padding],
        roundeds[props.rounded],
        shadows[props.shadow],
        ...hoverEffects,
        ...clickableEffects,
    ].join(" ");
});

const handleClick = (event: MouseEvent) => {
    if (props.clickable) {
        emit("click", event);
    }
};
</script>

<template>
    <div :class="classes" @click="handleClick">
        <!-- Gradient overlay effect -->
        <div
            v-if="variant === 'gradient' || variant === 'glass'"
            class="absolute inset-0 bg-gradient-to-br from-primary-500/5 via-transparent to-accent-500/5 rounded-inherit pointer-events-none"
        ></div>

        <!-- Border glow effect -->
        <div
            v-if="hover && (variant === 'gradient' || variant === 'glass')"
            class="absolute inset-0 rounded-inherit opacity-0 hover:opacity-100 transition-opacity duration-normal pointer-events-none"
            :class="[
                'bg-gradient-to-br from-primary-500/20 via-accent-500/20 to-primary-500/20',
                'shadow-glow',
            ]"
        ></div>

        <!-- Card content -->
        <div class="relative z-10">
            <!-- Header slot -->
            <div v-if="$slots.header" class="mb-4">
                <slot name="header" />
            </div>

            <!-- Main content -->
            <slot />

            <!-- Footer slot -->
            <div
                v-if="$slots.footer"
                class="mt-4 pt-4 border-t border-border/50"
            >
                <slot name="footer" />
            </div>
        </div>

        <!-- Decorative corner for gradient variant -->
        <div
            v-if="variant === 'gradient'"
            class="absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-primary-500/20 to-transparent rounded-bl-full pointer-events-none"
        ></div>

        <!-- Shimmer effect for hover -->
        <div
            v-if="hover"
            class="absolute inset-0 rounded-inherit opacity-0 hover:opacity-100 transition-opacity duration-slow pointer-events-none overflow-hidden"
        >
            <div
                class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full hover:translate-x-full transition-transform duration-slow"
            ></div>
        </div>
    </div>
</template>
