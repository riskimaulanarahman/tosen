<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import Button from "@/Components/ui/Button.vue";
import Card from "@/Components/ui/Card.vue";

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Welcome to TOSEN TOGA Presence" />

    <PublicLayout>
        <!-- Hero Section -->
        <section class="relative py-20 lg:py-32 overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-5">
                <div
                    class="absolute top-20 left-10 w-32 h-32 border-2 border-accent rounded-lg transform rotate-12"
                ></div>
                <div
                    class="absolute bottom-20 right-20 w-40 h-40 border-2 border-accent rounded-full transform -rotate-6"
                ></div>
                <div
                    class="absolute top-1/2 left-1/3 w-24 h-24 border-2 border-accent rounded-lg transform rotate-45"
                ></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <!-- Main Heading -->
                    <h1
                        class="text-5xl lg:text-6xl font-display font-bold text-text mb-6 leading-tight"
                    >
                        TOSEN TOGA <span class="text-accent">Presence</span>
                    </h1>

                    <p
                        class="text-xl lg:text-2xl text-text-2 mb-12 max-w-3xl mx-auto leading-relaxed"
                    >
                        Kelola kehadiran karyawan dengan mudah, cepat, dan
                        akurat. Dilengkapi fitur tracking real-time dan laporan
                        komprehensif.
                    </p>

                    <!-- CTA Buttons -->
                    <div
                        class="flex flex-col sm:flex-row gap-4 justify-center mb-16"
                    >
                        <Link v-if="canLogin" href="/login">
                            <Button
                                variant="secondary"
                                size="lg"
                                class="w-full sm:w-auto"
                            >
                                Masuk ke Sistem
                            </Button>
                        </Link>
                        <Link v-if="canRegister" href="/register">
                            <Button
                                variant="primary"
                                size="lg"
                                class="w-full sm:w-auto"
                            >
                                Daftar Sekarang
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 lg:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2
                        class="text-3xl lg:text-4xl font-display font-bold text-text mb-4"
                    >
                        Fitur Unggulan
                    </h2>
                    <p class="text-lg text-text-2 max-w-2xl mx-auto">
                        Solusi lengkap untuk manajemen absensi bisnis Anda
                    </p>
                </div>

                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8"
                >
                    <!-- Feature 1: Real-time -->
                    <Card hover class="text-center p-6">
                        <div
                            class="w-16 h-16 bg-primary rounded-xl flex items-center justify-center mx-auto mb-4"
                        >
                            <svg
                                class="w-8 h-8 text-white"
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
                        <h3 class="text-xl font-bold text-text mb-2">
                            Real-time Tracking
                        </h3>
                        <p class="text-text-2">
                            Monitor kehadiran karyawan secara langsung dengan
                            update real-time dari berbagai lokasi.
                        </p>
                    </Card>

                    <!-- Feature 2: Geofencing -->
                    <Card hover class="text-center p-6">
                        <div
                            class="w-16 h-16 bg-primary rounded-xl flex items-center justify-center mx-auto mb-4"
                        >
                            <svg
                                class="w-8 h-8 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                ></path>
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                ></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-text mb-2">
                            Geofencing
                        </h3>
                        <p class="text-text-2">
                            Tetapkan zona absensi otomatis dengan teknologi
                            geofencing yang presisi.
                        </p>
                    </Card>

                    <!-- Feature 3: Multi-Outlet -->
                    <Card hover class="text-center p-6">
                        <div
                            class="w-16 h-16 bg-primary rounded-xl flex items-center justify-center mx-auto mb-4"
                        >
                            <svg
                                class="w-8 h-8 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                ></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-text mb-2">
                            Multi-Outlet
                        </h3>
                        <p class="text-text-2">
                            Kelola multiple outlet dalam satu sistem
                            terintegrasi dengan mudah.
                        </p>
                    </Card>

                    <!-- Feature 4: Reports -->
                    <Card hover class="text-center p-6">
                        <div
                            class="w-16 h-16 bg-primary rounded-xl flex items-center justify-center mx-auto mb-4"
                        >
                            <svg
                                class="w-8 h-8 text-white"
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
                        <h3 class="text-xl font-bold text-text mb-2">
                            Laporan Komprehensif
                        </h3>
                        <p class="text-text-2">
                            Generate laporan detail dengan analitik mendalam
                            untuk kebutuhan bisnis.
                        </p>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-20 lg:py-24 bg-surface-1">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2
                        class="text-3xl lg:text-4xl font-display font-bold text-text mb-4"
                    >
                        Dipercaya oleh Bisnis
                    </h2>
                    <p class="text-lg text-text-2 max-w-2xl mx-auto">
                        Lebih dari 100+ bisnis telah mempercayai sistem kami
                    </p>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div
                            class="text-4xl lg:text-5xl font-bold text-accent mb-2"
                        >
                            100+
                        </div>
                        <div class="text-text font-medium">
                            Outlet Terdaftar
                        </div>
                    </div>
                    <div class="text-center">
                        <div
                            class="text-4xl lg:text-5xl font-bold text-accent mb-2"
                        >
                            10K+
                        </div>
                        <div class="text-text font-medium">Karyawan Aktif</div>
                    </div>
                    <div class="text-center">
                        <div
                            class="text-4xl lg:text-5xl font-bold text-accent mb-2"
                        >
                            1M+
                        </div>
                        <div class="text-text font-medium">
                            Absensi per Bulan
                        </div>
                    </div>
                    <div class="text-center">
                        <div
                            class="text-4xl lg:text-5xl font-bold text-accent mb-2"
                        >
                            99.9%
                        </div>
                        <div class="text-text font-medium">Uptime</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="py-20 lg:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2
                        class="text-3xl lg:text-4xl font-display font-bold text-text mb-4"
                    >
                        Apa Kata Mereka
                    </h2>
                    <p class="text-lg text-text-2 max-w-2xl mx-auto">
                        Dengarkan langsung pengalaman pelanggan kami
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <Card class="p-6">
                        <div class="flex items-center mb-4">
                            <div
                                class="w-12 h-12 bg-surface-2 rounded-full flex items-center justify-center mr-4"
                            >
                                <span class="text-lg font-bold text-primary"
                                    >JD</span
                                >
                            </div>
                            <div>
                                <div class="font-bold text-text mb-1">
                                    John Doe
                                </div>
                                <div class="text-sm text-text-muted">
                                    CEO, TechCompany
                                </div>
                            </div>
                        </div>
                        <p class="text-text-2 italic">
                            "Sistem absensi ini telah mengubah cara kami
                            mengelola kehadiran karyawan. Sangat efisien dan
                            mudah digunakan."
                        </p>
                        <div class="flex mt-4">
                            <span class="text-accent">★★★★★</span>
                        </div>
                    </Card>

                    <Card class="p-6">
                        <div class="flex items-center mb-4">
                            <div
                                class="w-12 h-12 bg-surface-2 rounded-full flex items-center justify-center mr-4"
                            >
                                <span class="text-lg font-bold text-primary"
                                    >SM</span
                                >
                            </div>
                            <div>
                                <div class="font-bold text-text mb-1">
                                    Sarah Miller
                                </div>
                                <div class="text-sm text-text-muted">
                                    HR Manager, RetailStore
                                </div>
                            </div>
                        </div>
                        <p class="text-text-2 italic">
                            "Fitur geofencing sangat membantu memastikan
                            karyawan hadir di lokasi yang tepat. Highly
                            recommended!"
                        </p>
                        <div class="flex mt-4">
                            <span class="text-accent">★★★★★</span>
                        </div>
                    </Card>

                    <Card class="p-6">
                        <div class="flex items-center mb-4">
                            <div
                                class="w-12 h-12 bg-surface-2 rounded-full flex items-center justify-center mr-4"
                            >
                                <span class="text-lg font-bold text-primary"
                                    >RC</span
                                >
                            </div>
                            <div>
                                <div class="font-bold text-text mb-1">
                                    Robert Chen
                                </div>
                                <div class="text-sm text-text-muted">
                                    Operations Director, ServiceCo
                                </div>
                            </div>
                        </div>
                        <p class="text-text-2 italic">
                            "Dashboard analytics memberikan insight berharga
                            untuk pengambilan keputusan bisnis."
                        </p>
                        <div class="flex mt-4">
                            <span class="text-accent">★★★★★</span>
                        </div>
                    </Card>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section
            id="pricing"
            class="py-20 lg:py-24 bg-gradient-to-r from-primary to-primary-600"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2
                    class="text-3xl lg:text-4xl font-display font-bold text-white mb-6"
                >
                    Siap Memulai?
                </h2>
                <p class="text-xl text-white opacity-90 mb-8 max-w-2xl mx-auto">
                    Bergabunglah dengan bisnis yang telah mempercayai kami untuk
                    manajemen absensi yang lebih baik.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link v-if="canLogin" href="/login">
                        <Button
                            variant="secondary"
                            size="lg"
                            class="w-full sm:w-auto"
                        >
                            Coba Demo
                        </Button>
                    </Link>
                    <Link v-if="canRegister" href="/register">
                        <Button
                            variant="primary"
                            size="lg"
                            class="w-full sm:w-auto"
                        >
                            Daftar Sekarang
                        </Button>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
