<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import Button from "@/Components/ui/Button.vue";

interface Props {
    title?: string;
    description?: string;
}

withDefaults(defineProps<Props>(), {
    title: "Selamat Datang",
    description: "Masuk ke akun Anda untuk melanjutkan",
});
</script>

<template>
    <div class="min-h-screen bg-canvas flex">
        <!-- Left Side - Brand/Illustration -->
        <div
            class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary to-primary-600 flex-col justify-between p-12 relative overflow-hidden"
        >
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div
                    class="absolute top-10 left-10 w-32 h-32 border-2 border-accent rounded-lg transform rotate-12"
                ></div>
                <div
                    class="absolute bottom-20 right-20 w-40 h-40 border-2 border-accent rounded-full transform -rotate-6"
                ></div>
                <div
                    class="absolute top-1/2 left-1/3 w-24 h-24 border-2 border-accent rounded-lg transform rotate-45"
                ></div>
            </div>

            <!-- Content -->
            <div class="relative z-10 flex flex-col justify-center h-full">
                <div class="max-w-md mx-auto">
                    <!-- Logo -->
                    <div class="flex items-center space-x-3 mb-8">
                        <div
                            class="w-12 h-12 bg-accent rounded-xl flex items-center justify-center"
                        >
                            <svg
                                class="w-8 h-8 text-white"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"
                                />
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-white">
                            TOSEN TOGA
                        </h1>
                    </div>

                    <!-- Hero Content -->
                    <div class="space-y-6">
                        <h2
                            class="text-4xl font-display font-bold text-white leading-tight"
                        >
                            TOSEN TOGA
                            <span class="text-accent">Presence</span> &
                            Terpercaya
                        </h2>
                        <p
                            class="text-xl text-white opacity-90 leading-relaxed"
                        >
                            Kelola kehadiran karyawan dengan mudah, cepat, dan
                            akurat. Dilengkapi fitur tracking real-time dan
                            laporan komprehensif.
                        </p>
                    </div>

                    <!-- Features -->
                    <div class="grid grid-cols-2 gap-4 mt-12">
                        <div class="flex items-center space-x-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-1/40"
                            >
                                <svg
                                    class="w-6 h-6 text-accent"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <span class="text-white font-medium"
                                >Real-time</span
                            >
                        </div>
                        <div class="flex items-center space-x-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-1/40"
                            >
                                <svg
                                    class="w-6 h-6 text-accent"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                    ></path>
                                </svg>
                            </div>
                            <span class="text-white font-medium">Aman</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-1/40"
                            >
                                <svg
                                    class="w-6 h-6 text-accent"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                    ></path>
                                </svg>
                            </div>
                            <span class="text-white font-medium">Laporan</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-1/40"
                            >
                                <svg
                                    class="w-6 h-6 text-accent"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"
                                    ></path>
                                </svg>
                            </div>
                            <span class="text-white font-medium">Mobile</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom decoration -->
            <div class="relative z-10 text-center">
                <p class="text-white opacity-75">
                    © 2024 TOSEN TOGA Presence. All rights reserved.
                </p>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div
            class="w-full lg:w-1/2 flex items-center justify-center p-8 lg:p-12"
        >
            <div class="w-full max-w-md">
                <!-- Mobile Logo (hidden on desktop) -->
                <div class="lg:hidden flex items-center justify-center mb-8">
                    <div
                        class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center"
                    >
                        <svg
                            class="w-6 h-6 text-white"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"
                            />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-text ml-3">
                        TOSEN TOGA
                    </h1>
                </div>

                <!-- Form Card -->
                <div class="bg-surface-1 rounded-2xl shadow-xl p-8">
                    <!-- Header -->
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-text mb-2">
                            {{ title }}
                        </h2>
                        <p class="text-text-2">{{ description }}</p>
                    </div>

                    <!-- Form Content -->
                    <slot />
                </div>

                <!-- Footer Links -->
                <div class="mt-6 text-center">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </div>
</template>
