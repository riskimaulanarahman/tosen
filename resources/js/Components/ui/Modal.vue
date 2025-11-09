<script setup lang="ts">
import { computed, watch, nextTick, ref, onUnmounted } from "vue";

interface Props {
    show: boolean;
    size?: "sm" | "md" | "lg" | "xl" | "full";
    closable?: boolean;
    closeOnEscape?: boolean;
    closeOnBackdrop?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    size: "md",
    closable: true,
    closeOnEscape: true,
    closeOnBackdrop: true,
});

const emit = defineEmits<{
    close: [];
    "update:show": [value: boolean];
}>();

const isVisible = ref(false);

const modalClasses = computed(() => {
    const base = "fixed inset-0 z-50 overflow-y-auto";
    return base;
});

const backdropClasses = computed(() => {
    const base =
        "fixed inset-0 bg-black bg-opacity-50 transition-opacity duration-fast";
    return base;
});

const contentClasses = computed(() => {
    const sizes = {
        sm: "max-w-md",
        md: "max-w-lg",
        lg: "max-w-2xl",
        xl: "max-w-4xl",
        full: "max-w-7xl",
    };

    const base =
        "relative mx-auto my-8 min-w-[320px] transform rounded-lg bg-surface-1 shadow-xl transition-all duration-fast";

    return [base, sizes[props.size]].join(" ");
});

const close = () => {
    if (props.closable) {
        emit("close");
        emit("update:show", false);
    }
};

const handleBackdropClick = (event: MouseEvent) => {
    if (props.closeOnBackdrop && event.target === event.currentTarget) {
        close();
    }
};

const handleEscape = (event: KeyboardEvent) => {
    if (props.closeOnEscape && event.key === "Escape" && props.show) {
        close();
    }
};

watch(
    () => props.show,
    (newValue) => {
        if (newValue) {
            nextTick(() => {
                isVisible.value = true;
            });
            document.addEventListener("keydown", handleEscape);
            document.body.style.overflow = "hidden";
        } else {
            isVisible.value = false;
            document.removeEventListener("keydown", handleEscape);
            document.body.style.overflow = "";
        }
    },
    { immediate: true }
);

onUnmounted(() => {
    document.removeEventListener("keydown", handleEscape);
    document.body.style.overflow = "";
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-fast"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-fast"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" :class="modalClasses" @click="handleBackdropClick">
                <div :class="backdropClasses" />

                <Transition
                    enter-active-class="transition-all duration-fast"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-fast"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="isVisible" :class="contentClasses">
                        <!-- Header -->
                        <div
                            v-if="$slots.header || closable"
                            class="flex items-center justify-between border-b border-border px-6 py-4"
                        >
                            <slot name="header" />

                            <button
                                v-if="closable"
                                type="button"
                                class="ml-4 h-8 w-8 flex items-center justify-center rounded-lg text-text-muted hover:bg-surface-2 hover:text-text transition-colors duration-fast"
                                @click="close"
                            >
                                <svg
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
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="px-6 py-4">
                            <slot />
                        </div>

                        <!-- Footer -->
                        <div
                            v-if="$slots.footer"
                            class="border-t border-border px-6 py-4"
                        >
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
