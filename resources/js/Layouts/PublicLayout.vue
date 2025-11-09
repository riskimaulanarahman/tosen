<script setup lang="ts">
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import Button from "@/Components/ui/Button.vue";

const isMenuOpen = ref(false);

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const closeMenu = () => {
    isMenuOpen.value = false;
};
</script>

<template>
    <div class="min-h-screen bg-canvas">
        <!-- Navigation -->
        <nav
            class="sticky top-0 z-40 bg-surface-1 border-b border-border backdrop-blur-sm bg-opacity-90"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center space-x-2">
                        <div
                            class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center"
                        >
                            <svg
                                class="w-5 h-5 text-white"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"
                                />
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-text"
                            >TOSEN TOGA</span
                        >
                    </Link>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <Link
                            href="#features"
                            class="text-text hover:text-accent transition-colors duration-fast"
                        >
                            Fitur
                        </Link>
                        <Link
                            href="#pricing"
                            class="text-text hover:text-accent transition-colors duration-fast"
                        >
                            Harga
                        </Link>
                        <Link
                            href="#about"
                            class="text-text hover:text-accent transition-colors duration-fast"
                        >
                            Tentang
                        </Link>
                        <div class="flex items-center space-x-4">
                            <Link href="/login">
                                <Button variant="ghost" size="sm">
                                    Masuk
                                </Button>
                            </Link>
                            <Link href="/register">
                                <Button variant="primary" size="sm">
                                    Daftar
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button
                            @click="toggleMenu"
                            class="p-2 rounded-lg text-text hover:bg-surface-2 transition-colors duration-fast"
                        >
                            <svg
                                v-if="!isMenuOpen"
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                ></path>
                            </svg>
                            <svg
                                v-else
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
                                ></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <Transition
                enter-active-class="transition-all duration-fast"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-fast"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="isMenuOpen" class="md:hidden border-t border-border">
                    <div class="px-4 py-4 space-y-4">
                        <Link
                            href="#features"
                            class="block text-text hover:text-accent transition-colors duration-fast"
                            @click="closeMenu"
                        >
                            Fitur
                        </Link>
                        <Link
                            href="#pricing"
                            class="block text-text hover:text-accent transition-colors duration-fast"
                            @click="closeMenu"
                        >
                            Harga
                        </Link>
                        <Link
                            href="#about"
                            class="block text-text hover:text-accent transition-colors duration-fast"
                            @click="closeMenu"
                        >
                            Tentang
                        </Link>
                        <div
                            class="flex flex-col space-y-2 pt-4 border-t border-border"
                        >
                            <Link href="/login" @click="closeMenu">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="w-full justify-center"
                                >
                                    Masuk
                                </Button>
                            </Link>
                            <Link href="/register" @click="closeMenu">
                                <Button
                                    variant="primary"
                                    size="sm"
                                    class="w-full justify-center"
                                >
                                    Daftar
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </Transition>
        </nav>

        <!-- Main Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-surface-1 border-t border-border">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Company -->
                    <div class="col-span-1 md:col-span-2">
                        <div class="flex items-center space-x-2 mb-4">
                            <div
                                class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center"
                            >
                                <svg
                                    class="w-5 h-5 text-white"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"
                                    />
                                </svg>
                            </div>
                            <span class="text-xl font-bold text-text"
                                >TOSEN TOGA</span
                            >
                        </div>
                        <p class="text-text-2 max-w-md">
                            Sistem manajemen absensi modern yang membantu
                            mengelola kehadiran karyawan dengan mudah dan
                            efisien.
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h3 class="text-text font-semibold mb-4">Link Cepat</h3>
                        <ul class="space-y-2">
                            <li>
                                <Link
                                    href="/login"
                                    class="text-text-2 hover:text-accent transition-colors duration-fast"
                                >
                                    Masuk
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/register"
                                    class="text-text-2 hover:text-accent transition-colors duration-fast"
                                >
                                    Daftar
                                </Link>
                            </li>
                            <li>
                                <a
                                    href="#features"
                                    class="text-text-2 hover:text-accent transition-colors duration-fast"
                                >
                                    Fitur
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#pricing"
                                    class="text-text-2 hover:text-accent transition-colors duration-fast"
                                >
                                    Harga
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Support -->
                    <div>
                        <h3 class="text-text font-semibold mb-4">Bantuan</h3>
                        <ul class="space-y-2">
                            <li>
                                <a
                                    href="#"
                                    class="text-text-2 hover:text-accent transition-colors duration-fast"
                                >
                                    Dokumentasi
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-text-2 hover:text-accent transition-colors duration-fast"
                                >
                                    Panduan
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-text-2 hover:text-accent transition-colors duration-fast"
                                >
                                    Kontak
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-text-2 hover:text-accent transition-colors duration-fast"
                                >
                                    FAQ
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-border">
                    <div
                        class="flex flex-col md:flex-row justify-between items-center"
                    >
                        <p class="text-text-muted text-sm">
                            © 2024 TOSEN TOGA Presence. Semua hak dilindungi.
                        </p>
                        <div class="flex space-x-6 mt-4 md:mt-0">
                            <a
                                href="#"
                                class="text-text-muted hover:text-accent transition-colors duration-fast"
                            >
                                Kebijakan Privasi
                            </a>
                            <a
                                href="#"
                                class="text-text-muted hover:text-accent transition-colors duration-fast"
                            >
                                Syarat & Ketentuan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
