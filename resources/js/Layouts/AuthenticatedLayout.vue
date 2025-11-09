<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { useThemeStore } from "@/stores/ThemeStore";
import AppSidebar from "@/Components/AppSidebar.vue";
import AppHeader from "@/Components/AppHeader.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

// Initialize theme store
const themeStore = useThemeStore();

// Ensure theme is initialized when component mounts
themeStore.initTheme();

// Mobile sidebar state
const isMobile = ref(false);
const sidebarOpen = ref(false);

// Check screen size
const checkScreenSize = () => {
    isMobile.value = window.innerWidth < 1024; // lg breakpoint
    if (!isMobile.value) {
        sidebarOpen.value = false;
    }
};

// Toggle sidebar
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

// Close sidebar
const closeSidebar = () => {
    sidebarOpen.value = false;
};

// Initialize and cleanup
onMounted(() => {
    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);
});

onUnmounted(() => {
    window.removeEventListener("resize", checkScreenSize);
});
</script>

<template>
    <div
        class="flex h-screen bg-canvas text-text transition-colors duration-200"
    >
        <!-- Mobile Sidebar Overlay -->
        <div
            v-if="isMobile && sidebarOpen"
            class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
            @click="closeSidebar"
        ></div>

        <!-- Sidebar -->
        <AppSidebar
            :class="[
                'fixed left-0 top-0 h-full z-30 transition-transform duration-300 ease-in-out lg:translate-x-0',
                isMobile && sidebarOpen
                    ? 'translate-x-0'
                    : '-translate-x-full lg:translate-x-0',
            ]"
        />

        <!-- Main Content Area -->
        <div
            :class="[
                'flex-1 flex flex-col overflow-hidden relative z-10 transition-all duration-300',
                isMobile ? 'ml-0' : 'ml-64',
            ]"
        >
            <!-- Header -->
            <AppHeader @toggle-sidebar="toggleSidebar" />

            <!-- Main Content -->
            <main
                class="flex-1 overflow-y-auto p-4 lg:p-6 bg-canvas transition-colors duration-200 relative"
            >
                <!-- Breadcrumb -->
                <div class="mb-4">
                    <slot name="breadcrumb">
                        <!-- Default breadcrumb if not provided -->
                    </slot>
                </div>

                <!-- Page content will be rendered here via Inertia.js slots -->
                <div class="relative z-20">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Smooth transitions for theme changes */
.transition-colors {
    transition-property: background-color, border-color, color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Custom scrollbar for main content - Industrial theme */
main {
    scrollbar-width: thin;
    scrollbar-color: rgb(75 85 99) transparent;
}

main::-webkit-scrollbar {
    width: 6px;
}

main::-webkit-scrollbar-track {
    background: transparent;
}

main::-webkit-scrollbar-thumb {
    background-color: rgb(75 85 99);
    border: none;
}

main::-webkit-scrollbar-thumb:hover {
    background-color: rgb(107 114 128);
}
</style>
