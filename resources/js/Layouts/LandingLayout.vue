<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";

// Mobile menu state
const isMobileMenuOpen = ref(false);

// Smooth scroll to top on mount
onMounted(() => {
    window.scrollTo(0, 0);
});

// Handle scroll for navbar
const isScrolled = ref(false);
const handleScroll = () => {
    isScrolled.value = window.scrollY > 50;
};

onMounted(() => {
    window.addEventListener("scroll", handleScroll);
});

onUnmounted(() => {
    window.removeEventListener("scroll", handleScroll);
});

// Navigation items
const navItems = [
    { name: "Fitur", href: "#features" },
    { name: "Cara Kerja", href: "#how-it-works" },
    { name: "Testimoni", href: "#testimonials" },
    { name: "FAQ", href: "#faq" },
];
</script>

<template>
    <div class="min-h-screen bg-white">
        <!-- Enhanced Navigation with Vibrant Teal -->
        <nav
            class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-white/95 backdrop-blur-md border-b border-tosen-primary-100"
            :class="
                isScrolled
                    ? 'py-2 shadow-lg shadow-tosen-primary-500/10'
                    : 'py-4'
            "
        >
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center">
                    <!-- Enhanced Logo -->
                    <div class="flex items-center space-x-3 group">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-tosen-primary-500 to-tosen-primary-600 rounded-xl flex items-center justify-center shadow-lg shadow-tosen-primary-500/25 transform transition-all duration-300 group-hover:scale-110 group-hover:shadow-xl group-hover:shadow-tosen-primary-500/40"
                        >
                            <svg
                                class="w-7 h-7 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </div>
                        <Link href="/" class="flex flex-col">
                            <span
                                class="text-2xl font-bold bg-gradient-to-r from-tosen-primary-500 to-tosen-primary-700 bg-clip-text text-transparent"
                                >TOSEN-TOGA Presence</span
                            >
                            <span
                                class="text-xs font-semibold tracking-[0.2em] uppercase text-tosen-primary-200"
                                >Presensi tepercaya untuk UMKM</span
                            >
                        </Link>
                    </div>

                    <!-- Enhanced Desktop Navigation -->
                    <div class="hidden lg:flex items-center space-x-8">
                        <a
                            v-for="item in navItems"
                            :key="item.name"
                            :href="item.href"
                            class="relative text-tosen-gray-700 font-medium hover:text-tosen-primary-600 transition-colors duration-300 group"
                        >
                            {{ item.name }}
                            <span
                                class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-tosen-primary-500 to-tosen-accent transition-all duration-300 group-hover:w-full"
                            ></span>
                        </a>
                        <Link
                            href="/login"
                            class="relative overflow-hidden group tosen-btn tosen-btn-primary px-6 py-2.5 rounded-lg font-semibold"
                        >
                            <span class="relative z-10">Masuk</span>
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-tosen-accent to-tosen-accent-dark transform translate-y-full group-hover:translate-y-0 transition-transform duration-300"
                            ></div>
                        </Link>
                    </div>

                    <!-- Enhanced Mobile menu button -->
                    <button
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        class="lg:hidden p-2 rounded-lg text-tosen-primary-600 hover:bg-tosen-primary-50 transition-all duration-300"
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

                <!-- Enhanced Mobile Navigation -->
                <div
                    v-if="isMobileMenuOpen"
                    class="lg:hidden mt-4 pb-4 border-t border-tosen-primary-100 animate-slideDown"
                >
                    <div class="pt-4 space-y-3">
                        <a
                            v-for="item in navItems"
                            :key="item.name"
                            :href="item.href"
                            class="block px-4 py-3 rounded-lg text-tosen-gray-700 font-medium hover:bg-tosen-primary-50 hover:text-tosen-primary-600 transition-all duration-300"
                            @click="isMobileMenuOpen = false"
                        >
                            {{ item.name }}
                        </a>
                        <Link
                            href="/login"
                            class="block tosen-btn tosen-btn-primary text-center px-4 py-3 rounded-lg font-semibold"
                            @click="isMobileMenuOpen = false"
                        >
                            Masuk
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            <slot />
        </main>

        <!-- Enhanced Footer with Vibrant Teal -->
        <footer
            class="bg-gradient-to-br from-tosen-gray-900 to-tosen-gray-800 text-white relative overflow-hidden"
        >
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-5">
                <div
                    class="absolute top-10 left-10 w-32 h-32 border-2 border-tosen-primary-400 rounded-lg transform rotate-12"
                ></div>
                <div
                    class="absolute bottom-20 right-20 w-40 h-40 border-2 border-tosen-primary-400 rounded-full transform -rotate-6"
                ></div>
                <div
                    class="absolute top-1/2 left-1/3 w-24 h-24 border-2 border-tosen-primary-400 rounded-lg transform rotate-45"
                ></div>
            </div>
            <div class="container mx-auto px-4">
                <div class="grid lg:grid-cols-4 gap-8 mb-8">
                    <!-- Company Info -->
                    <div>
                        <div class="flex items-center space-x-2 mb-4">
                            <div
                                class="w-10 h-10 tosen-bg-primary rounded-lg flex items-center justify-center"
                            >
                                <svg
                                    class="w-6 h-6 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                            </div>
                            <span class="text-xl font-bold text-white"
                                >TOSEN</span
                            >
                        </div>
                        <h3 class="tosen-footer-title">TOGA Presence</h3>
                        <p class="text-gray-400 mb-4">
                            Sistem absensi geofencing terpadu untuk UMKM
                            Indonesia. Tingkatkan produktivitas dan efisiensi
                            bisnis Anda.
                        </p>
                        <div class="flex space-x-4">
                            <a href="#" class="tosen-footer-link">
                                <svg
                                    class="w-6 h-6"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953c0-.002-1.447 0-2.692 0-3.023 0-2.358 1.437-4.328 4.328-4.328v3.47h3.047c-.393 1.278-2.008 3.908-4.328 3.908z"
                                    />
                                </svg>
                            </a>
                            <a href="#" class="tosen-footer-link">
                                <svg
                                    class="w-6 h-6"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 00-8.767 3.052 4.958 4.958 0 00-3.052 8.767 10 10 0 01-.775 2.825c2.166 1.38 3.594 3.78 3.594 6.492a10 10 0 01-14.075 9.163 4.958 4.958 0 00-1.597 1.667 4.958 4.958 0 00-1.667 1.597 10 10 0 01-9.163-14.075 4.958 4.958 0 001.667-1.597 4.958 4.958 0 001.597-1.667 10 10 0 0114.075-9.163 4.958 4.958 0 002.825.775z"
                                    />
                                </svg>
                            </a>
                            <a href="#" class="tosen-footer-link">
                                <svg
                                    class="w-6 h-6"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"
                                    />
                                </svg>
                            </a>
                            <a href="#" class="tosen-footer-link">
                                <svg
                                    class="w-6 h-6"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505a3.017 3.017 0 00-2.122 2.136C.305 8.049.306 9.927.307 11.805c-.001 1.878 0 3.756.194 5.619a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136c.194-1.863.195-3.741.194-5.619.001-1.878 0-3.756-.194-5.619zM9.61 15.601V8.408l6.379 3.607-6.379 3.586z"
                                    />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Enhanced Quick Links -->
                    <div>
                        <h4
                            class="text-lg font-bold text-white mb-4 flex items-center"
                        >
                            <span
                                class="w-8 h-0.5 bg-gradient-to-r from-tosen-primary-400 to-tosen-accent mr-2"
                            ></span>
                            Navigasi Cepat
                        </h4>
                        <ul class="space-y-3">
                            <li>
                                <a
                                    href="#features"
                                    class="text-tosen-gray-400 hover:text-tosen-primary-400 transition-colors duration-300 flex items-center group"
                                >
                                    <span
                                        class="w-1.5 h-1.5 bg-tosen-primary-400 rounded-full mr-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    ></span>
                                    Fitur Unggulan
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#pricing"
                                    class="text-tosen-gray-400 hover:text-tosen-primary-400 transition-colors duration-300 flex items-center group"
                                >
                                    <span
                                        class="w-1.5 h-1.5 bg-tosen-primary-400 rounded-full mr-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    ></span>
                                    Harga & Paket
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-tosen-gray-400 hover:text-tosen-primary-400 transition-colors duration-300 flex items-center group"
                                >
                                    <span
                                        class="w-1.5 h-1.5 bg-tosen-primary-400 rounded-full mr-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    ></span>
                                    Blog & Panduan
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-tosen-gray-400 hover:text-tosen-primary-400 transition-colors duration-300 flex items-center group"
                                >
                                    <span
                                        class="w-1.5 h-1.5 bg-tosen-primary-400 rounded-full mr-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    ></span>
                                    Karir
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-tosen-gray-400 hover:text-tosen-primary-400 transition-colors duration-300 flex items-center group"
                                >
                                    <span
                                        class="w-1.5 h-1.5 bg-tosen-primary-400 rounded-full mr-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    ></span>
                                    Hubungi Kami
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Enhanced Legal -->
                    <div>
                        <h4
                            class="text-lg font-bold text-white mb-4 flex items-center"
                        >
                            <span
                                class="w-8 h-0.5 bg-gradient-to-r from-tosen-primary-400 to-tosen-accent mr-2"
                            ></span>
                            Informasi Legal
                        </h4>
                        <ul class="space-y-3">
                            <li>
                                <a
                                    href="#"
                                    class="text-tosen-gray-400 hover:text-tosen-primary-400 transition-colors duration-300 flex items-center group"
                                >
                                    <span
                                        class="w-1.5 h-1.5 bg-tosen-primary-400 rounded-full mr-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    ></span>
                                    Kebijakan Privasi
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-tosen-gray-400 hover:text-tosen-primary-400 transition-colors duration-300 flex items-center group"
                                >
                                    <span
                                        class="w-1.5 h-1.5 bg-tosen-primary-400 rounded-full mr-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    ></span>
                                    Syarat & Ketentuan
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-tosen-gray-400 hover:text-tosen-primary-400 transition-colors duration-300 flex items-center group"
                                >
                                    <span
                                        class="w-1.5 h-1.5 bg-tosen-primary-400 rounded-full mr-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    ></span>
                                    Kebijakan Keamanan
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-tosen-gray-400 hover:text-tosen-primary-400 transition-colors duration-300 flex items-center group"
                                >
                                    <span
                                        class="w-1.5 h-1.5 bg-tosen-primary-400 rounded-full mr-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    ></span>
                                    SLA & Garansi
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Enhanced Contact Info -->
                    <div>
                        <h4
                            class="text-lg font-bold text-white mb-4 flex items-center"
                        >
                            <span
                                class="w-8 h-0.5 bg-gradient-to-r from-tosen-primary-400 to-tosen-accent mr-2"
                            ></span>
                            Hubungi Kami
                        </h4>
                        <ul class="space-y-4 text-tosen-gray-400">
                            <li class="flex items-center group">
                                <div
                                    class="w-10 h-10 bg-tosen-primary-600/20 rounded-lg flex items-center justify-center mr-3 group-hover:bg-tosen-primary-500 transition-colors duration-300"
                                >
                                    <svg
                                        class="w-5 h-5 text-tosen-primary-400 group-hover:text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <div
                                        class="font-medium text-white group-hover:text-tosen-primary-400 transition-colors"
                                    >
                                        Email
                                    </div>
                                    <div class="text-sm">
                                        support@tosen-toga.id
                                    </div>
                                </div>
                            </li>
                            <li class="flex items-center group">
                                <div
                                    class="w-10 h-10 bg-tosen-primary-600/20 rounded-lg flex items-center justify-center mr-3 group-hover:bg-tosen-primary-500 transition-colors duration-300"
                                >
                                    <svg
                                        class="w-5 h-5 text-tosen-primary-400 group-hover:text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <div
                                        class="font-medium text-white group-hover:text-tosen-primary-400 transition-colors"
                                    >
                                        Telepon
                                    </div>
                                    <div class="text-sm">0800-1234-5678</div>
                                </div>
                            </li>
                            <li class="flex items-center group">
                                <div
                                    class="w-10 h-10 bg-tosen-primary-600/20 rounded-lg flex items-center justify-center mr-3 group-hover:bg-tosen-primary-500 transition-colors duration-300"
                                >
                                    <svg
                                        class="w-5 h-5 text-tosen-primary-400 group-hover:text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <div
                                        class="font-medium text-white group-hover:text-tosen-primary-400 transition-colors"
                                    >
                                        Lokasi
                                    </div>
                                    <div class="text-sm">
                                        Jakarta, Indonesia
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Enhanced Copyright -->
                <div
                    class="border-t border-tosen-gray-700 pt-8 text-center text-tosen-gray-500"
                >
                    <div
                        class="flex flex-col md:flex-row justify-center items-center space-y-2 md:space-y-0 md:space-x-4"
                    >
                        <p class="flex items-center text-sm text-tosen-primary-50">
                            &copy; 2025 TOSEN-TOGA Presence. Seluruh hak cipta
                            dilindungi.
                        </p>
                        <div class="flex items-center space-x-2">
                            <span
                                class="w-2 h-2 bg-tosen-primary-400 rounded-full animate-pulse"
                            ></span>
                            <span class="text-sm"
                                >Ritme presensi berpalet teal untuk UMKM
                                Indonesia</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
