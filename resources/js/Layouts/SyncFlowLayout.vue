<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";

// Reactive state
const isScrolled = ref(false);
const isMobileMenuOpen = ref(false);

// Navigation items
const navItems = [
    { name: "Fitur", href: "#features" },
    { name: "Cara Kerja", href: "#how-it-works" },
    { name: "Testimoni", href: "#testimonials" },
    { name: "Harga", href: "#pricing" },
    { name: "FAQ", href: "#faq" },
];

// Handle scroll for navbar
const handleScroll = () => {
    isScrolled.value = window.scrollY > 50;
};

// Mobile menu toggle
const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false;
};

// Lifecycle
onMounted(() => {
    window.addEventListener("scroll", handleScroll);
});

onUnmounted(() => {
    window.removeEventListener("scroll", handleScroll);
});
</script>

<template>
    <div class="min-h-screen bg-syncflow-gray-50">
        <!-- Navigation -->
        <nav
            class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-white/95 backdrop-blur-md border-b border-syncflow-gray-200"
            :class="
                isScrolled
                    ? 'py-2 shadow-lg shadow-syncflow-primary-500/10'
                    : 'py-4'
            "
        >
            <div class="syncflow-container">
                <div class="flex justify-between items-center">
                    <!-- Logo -->
                    <a href="/" class="flex items-center space-x-3 group">
                        <div
                            class="w-10 h-10 bg-gradient-to-br from-syncflow-primary to-syncflow-primary-600 rounded-xl flex items-center justify-center shadow-lg shadow-syncflow-primary-500/25 transform transition-all duration-300 group-hover:scale-110 group-hover:shadow-xl group-hover:shadow-syncflow-primary-500/40"
                        >
                            <svg
                                class="w-6 h-6 text-white"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12 2L2 7l10 5 10-5-10 5zM2 17l10 5 10-5M2 12l10 5 10-5"
                                />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-xl font-bold bg-gradient-to-r from-syncflow-primary to-syncflow-primary-600 bg-clip-text text-transparent"
                            >
                                SyncFlow
                            </span>
                            <span
                                class="text-xs font-semibold tracking-[0.2em] uppercase text-syncflow-gray-500"
                            >
                                Manajemen Proyek Modern
                            </span>
                        </div>
                    </a>

                    <!-- Desktop Navigation -->
                    <div class="hidden lg:flex items-center space-x-8">
                        <a
                            v-for="item in navItems"
                            :key="item.name"
                            :href="item.href"
                            class="relative text-syncflow-gray-700 font-medium hover:text-syncflow-primary transition-colors duration-300 group"
                        >
                            {{ item.name }}
                            <span
                                class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-syncflow-primary to-syncflow-success transition-all duration-300 group-hover:w-full"
                            ></span>
                        </a>
                        <div class="flex items-center space-x-4">
                            <a href="/login">
                                <button class="syncflow-btn-secondary">
                                    Masuk
                                </button>
                            </a>
                            <a href="/register">
                                <button class="syncflow-btn-primary">
                                    Mulai Gratis
                                </button>
                            </a>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <button
                        @click="toggleMobileMenu"
                        class="lg:hidden p-2 rounded-lg text-syncflow-primary hover:bg-syncflow-primary-50 transition-all duration-300"
                    >
                        <svg
                            class="w-6 h-6 transition-transform duration-300"
                            :class="isMobileMenuOpen ? 'rotate-90' : ''"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                v-if="!isMobileMenuOpen"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                v-else
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Navigation -->
                <div
                    v-if="isMobileMenuOpen"
                    class="lg:hidden mt-4 pb-4 border-t border-syncflow-gray-200 animate-slide-down"
                >
                    <div class="pt-4 space-y-3">
                        <a
                            v-for="item in navItems"
                            :key="item.name"
                            :href="item.href"
                            class="block px-4 py-3 rounded-lg text-syncflow-gray-700 font-medium hover:bg-syncflow-primary-50 hover:text-syncflow-primary transition-all duration-300"
                            @click="closeMobileMenu"
                        >
                            {{ item.name }}
                        </a>
                        <div
                            class="flex flex-col space-y-2 pt-4 border-t border-syncflow-gray-200"
                        >
                            <a href="/login" @click="closeMobileMenu">
                                <button
                                    class="syncflow-btn-secondary w-full justify-center"
                                >
                                    Masuk
                                </button>
                            </a>
                            <a href="/register" @click="closeMobileMenu">
                                <button
                                    class="syncflow-btn-primary w-full justify-center"
                                >
                                    Mulai Gratis
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="pt-16">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-syncflow-gray-900 text-white">
            <!-- Footer content will be implemented in FooterSection component -->
        </footer>
    </div>
</template>
