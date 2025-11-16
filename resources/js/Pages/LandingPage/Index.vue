<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import LandingLayout from "@/Layouts/LandingLayout.vue";

// Form state
const trialForm = ref({
    name: "",
    email: "",
    phone: "",
    company_name: "",
    employee_count: "",
    business_type: "",
});

const demoForm = ref({
    name: "",
    email: "",
    phone: "",
    company_name: "",
    employee_count: "",
    preferred_date: "",
    preferred_time: "",
    notes: "",
});

// UI state
const showTrialModal = ref(false);
const showDemoModal = ref(false);
const submittingTrial = ref(false);
const submittingDemo = ref(false);

// FAQ state
const expandedFaq = ref(null);

// Form submission
const submitTrial = async () => {
    submittingTrial.value = true;
    try {
        const response = await axios.post("/trial-request", trialForm.value);
        if (response.data.success) {
            showTrialModal.value = false;
            // Reset form
            trialForm.value = {
                name: "",
                email: "",
                phone: "",
                company_name: "",
                employee_count: "",
                business_type: "",
            };
            // Show success message
            alert(response.data.message);
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            // Handle validation errors
            const errorMessages = Object.values(
                error.response.data.errors
            ).flat();
            alert(errorMessages.join("\n"));
        } else {
            alert("Terjadi kesalahan. Silakan coba lagi.");
        }
    } finally {
        submittingTrial.value = false;
    }
};

const submitDemo = async () => {
    submittingDemo.value = true;
    try {
        const response = await axios.post("/demo-request", demoForm.value);
        if (response.data.success) {
            showDemoModal.value = false;
            // Reset form
            demoForm.value = {
                name: "",
                email: "",
                phone: "",
                company_name: "",
                employee_count: "",
                preferred_date: "",
                preferred_time: "",
                notes: "",
            };
            // Show success message
            alert(response.data.message);
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            // Handle validation errors
            const errorMessages = Object.values(
                error.response.data.errors
            ).flat();
            alert(errorMessages.join("\n"));
        } else {
            alert("Terjadi kesalahan. Silakan coba lagi.");
        }
    } finally {
        submittingDemo.value = false;
    }
};

// Toggle FAQ
const toggleFaq = (index) => {
    expandedFaq.value = expandedFaq.value === index ? null : index;
};

// Smooth scroll
const scrollToSection = (sectionId) => {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: "smooth" });
    }
};
</script>

<template>
    <Head title="TOSEN-TOGA Presence - Platform Absensi Deep Teal untuk UMKM" />

    <LandingLayout>
        <!-- Hero Section -->
        <section
            class="tosen-hero hero-gradient relative text-white overflow-hidden"
        >
            <div
                class="absolute inset-0 bg-gradient-to-br from-tosen-primary-900/80 via-tosen-primary-700/70 to-black/60"
            ></div>
            <div class="relative container mx-auto px-4 py-20 lg:py-32">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="space-y-8">
                        <div class="space-y-4">
                            <h1
                                class="tosen-heading-1 text-white leading-tight"
                            >
                                TOSEN-TOGA Presence
                                <span class="tosen-text-secondary block"
                                    >Platform absensi deep teal gratis untuk
                                    UMKM</span
                                >
                            </h1>
                            <p class="text-xl text-white/90 leading-relaxed">
                                Satukan geofencing, bukti foto, dan dashboard
                                otomatis dalam satu platform. Tingkatkan
                                produktivitas dan efisiensi bisnis Anda.
                            </p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <button
                                @click="showTrialModal = true"
                                class="tosen-btn tosen-btn-lg tosen-btn-accent"
                            >
                                Aktifkan Akun Gratis
                            </button>
                            <button
                                @click="showDemoModal = true"
                                class="tosen-btn tosen-btn-lg tosen-btn-outline"
                            >
                                Lihat Demo
                            </button>
                        </div>

                        <div
                            class="flex items-center space-x-2 text-tosen-primary-100"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span class="font-semibold"
                                >100% Gratis & Terintegrasi</span
                            >
                        </div>
                    </div>

                    <div class="relative">
                        <div
                            class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 shadow-2xl border border-white/20"
                        >
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-white/90"
                                        >Produktivitas</span
                                    >
                                    <span class="tosen-text-secondary font-bold"
                                        >+40%</span
                                    >
                                </div>
                                <div
                                    class="w-full bg-white/20 rounded-full h-3"
                                >
                                    <div
                                        class="tosen-bg-secondary h-3 rounded-full"
                                        style="width: 85%"
                                    ></div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-white/90"
                                        >Efisiensi Waktu</span
                                    >
                                    <span class="tosen-text-secondary font-bold"
                                        >+60%</span
                                    >
                                </div>
                                <div
                                    class="w-full bg-white/20 rounded-full h-3"
                                >
                                    <div
                                        class="tosen-bg-secondary h-3 rounded-full"
                                        style="width: 75%"
                                    ></div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-white/90"
                                        >Penghematan Biaya</span
                                    >
                                    <span class="tosen-text-secondary font-bold"
                                        >+25%</span
                                    >
                                </div>
                                <div
                                    class="w-full bg-white/20 rounded-full h-3"
                                >
                                    <div
                                        class="tosen-bg-secondary h-3 rounded-full"
                                        style="width: 65%"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Trust Bar -->
        <section class="tosen-bg-light py-8 border-y border-gray-200">
            <div class="container mx-auto px-4">
                <div
                    class="text-center tosen-text-primary font-semibold text-lg mb-4"
                >
                    500+ UMKM memakai TOSEN-TOGA Presence • 95% akurasi
                    geofencing • 10.000+ karyawan aktif
                </div>
                <div class="text-center tosen-text-secondary">
                    Dipakai lintas industri—F&B, ritel, jasa, dan manufaktur
                </div>
            </div>
        </section>

        <!-- Problem & Solution Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="tosen-heading-2 mb-4">
                        Solusi Absensi UMKM Modern
                    </h2>
                </div>

                <div class="grid lg:grid-cols-2 gap-16">
                    <!-- Problems -->
                    <div class="space-y-8">
                        <h3 class="tosen-heading-3 tosen-text-error mb-6">
                            Tantangan yang Sering Dihadapi
                        </h3>

                        <div
                            class="bg-red-50 border border-red-200 rounded-lg p-6 tosen-card"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center"
                                >
                                    <svg
                                        class="w-6 h-6 text-red-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-red-900 mb-2">
                                        Buddy Punching
                                    </h4>
                                    <p class="text-red-700">
                                        Titip absen menghilangkan produktivitas
                                        dan membuat jadwal berantakan.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-orange-50 border border-orange-200 rounded-lg p-6"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex-shrink-0 w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center"
                                >
                                    <svg
                                        class="w-6 h-6 text-orange-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-orange-900 mb-2">
                                        Perhitungan Gaji Manual
                                    </h4>
                                    <p class="text-orange-700">
                                        Lembur, cuti, dan keterlambatan yang
                                        dicatat manual memunculkan selisih
                                        hingga 15%.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-yellow-50 border border-yellow-200 rounded-lg p-6"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex-shrink-0 w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center"
                                >
                                    <svg
                                        class="w-6 h-6 text-yellow-600"
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
                                    <h4 class="font-bold text-yellow-900 mb-2">
                                        Tim Lapangan Sulit Dipantau
                                    </h4>
                                    <p class="text-yellow-700">
                                        Tanpa bukti lokasi, Anda tidak yakin
                                        apakah tim berada di outlet atau di luar
                                        radius kerja.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Solution -->
                    <div class="space-y-8">
                        <div class="text-center">
                            <div
                                class="inline-flex items-center justify-center w-20 h-20 tosen-bg-success/20 rounded-full mb-4"
                            >
                                <svg
                                    class="w-10 h-10 text-green-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <h3 class="tosen-heading-3 tosen-text-success mb-6">
                                TOSEN-TOGA Presence: Solusi Lengkap
                            </h3>
                        </div>

                        <div
                            class="bg-gradient-to-br from-green-50 to-blue-50 border border-green-200 rounded-2xl p-8 tosen-card"
                        >
                            <h4 class="tosen-heading-4 tosen-text-success mb-6">
                                Satu Platform untuk Seluruh Kebutuhan
                            </h4>

                            <div class="space-y-6">
                                <div class="flex items-start space-x-4">
                                    <div
                                        class="flex-shrink-0 w-10 h-10 tosen-bg-success rounded-lg flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-5 h-5 text-white"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h5
                                            class="font-bold tosen-text-success"
                                        >
                                            Geofencing Anti-Curang
                                        </h5>
                                        <p class="tosen-text-success">
                                            Bukti lokasi plus selfie otomatis
                                            dengan akurasi 95%.
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start space-x-4">
                                    <div
                                        class="flex-shrink-0 w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-5 h-5 text-white"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h5 class="font-bold text-green-900">
                                            Integrasi Otomatis
                                        </h5>
                                        <p class="text-green-700">
                                            Data absensi langsung mengalir ke
                                            payroll tanpa perlu impor manual.
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start space-x-4">
                                    <div
                                        class="flex-shrink-0 w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-5 h-5 text-white"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
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
                                    </div>
                                    <div>
                                        <h5 class="font-bold text-green-900">
                                            Dashboard Real-Time
                                        </h5>
                                        <p class="text-green-700">
                                            Pantau semua outlet dengan insight
                                            yang mudah dibaca kapan pun.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-20 tosen-bg-light">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="tosen-heading-2 mb-4">
                        Fitur Utama untuk Meningkatkan Efisiensi
                    </h2>
                </div>

                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Feature 1: Geofencing -->
                    <div class="tosen-card p-8">
                        <div class="tosen-feature-icon">
                            <svg
                                class="w-8 h-8 text-blue-600"
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
                        <h3 class="tosen-heading-4 mb-4">Geofencing Presisi</h3>
                        <ul class="space-y-2 tosen-body-2 mb-6">
                            <li class="flex items-start">
                                <svg
                                    class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Validasi lokasi otomatis dengan radius adaptif
                            </li>
                            <li class="flex items-start">
                                <svg
                                    class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Selfie anti-buddy punching berbasis AI
                            </li>
                            <li class="flex items-start">
                                <svg
                                    class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Sinkron multi-outlet dan multi-shift
                            </li>
                        </ul>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h5 class="font-semibold tosen-text-primary mb-2">
                                Manfaat:
                            </h5>
                            <p class="text-sm tosen-text-primary">
                                Tekan kebocoran payroll hingga 15% dan
                                tingkatkan disiplin karyawan hingga 85%
                            </p>
                        </div>
                    </div>

                    <!-- Feature 2: Dashboard analitik real-time -->
                    <div class="tosen-card p-8">
                        <div class="tosen-feature-icon">
                            <svg
                                class="w-8 h-8 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                        <h3 class="tosen-heading-4 mb-4">
                            Dashboard Analitik Real-Time
                        </h3>
                        <ul class="space-y-2 tosen-body-2 mb-6">
                            <li class="flex items-start">
                                <svg
                                    class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Visualisasi data yang mudah dibaca
                            </li>
                            <li class="flex items-start">
                                <svg
                                    class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Peringatan otomatis untuk keterlambatan
                            </li>
                            <li class="flex items-start">
                                <svg
                                    class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Laporan sekali klik ke Excel atau PDF
                            </li>
                        </ul>
                        <div class="bg-green-50 rounded-lg p-4">
                            <h5 class="font-semibold tosen-text-primary mb-2">
                                Manfaat:
                            </h5>
                            <p class="text-sm tosen-text-primary">
                                Ambil keputusan berbasis data real-time dan
                                hemat 20+ jam laporan manual per bulan
                            </p>
                        </div>
                    </div>

                    <!-- Feature 3: Integrasi payroll otomatis -->
                    <div class="tosen-card p-8">
                        <div class="tosen-feature-icon">
                            <svg
                                class="w-8 h-8 text-orange-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="tosen-heading-4 mb-4">
                            Integrasi Payroll Otomatis
                        </h3>
                        <ul class="space-y-2 tosen-body-2 mb-6">
                            <li class="flex items-start">
                                <svg
                                    class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Perhitungan gaji otomatis dari data absensi
                            </li>
                            <li class="flex items-start">
                                <svg
                                    class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Slip gaji digital dengan branding perusahaan
                            </li>
                            <li class="flex items-start">
                                <svg
                                    class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Komponen gaji lengkap (BPJS, PPh21, insentif)
                            </li>
                        </ul>
                        <div class="bg-orange-50 rounded-lg p-4">
                            <h5 class="font-semibold tosen-text-primary mb-2">
                                Manfaat:
                            </h5>
                            <p class="text-sm tosen-text-primary">
                                Kurangi selisih gaji hingga 95% dan proses
                                payroll selesai 3 jam, bukan 3 hari
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-gray-900 mb-4">
                        Aktifkan TOSEN-TOGA Presence dalam 3 Langkah
                    </h2>
                </div>

                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Step 1 -->
                    <div class="text-center">
                        <div
                            class="w-20 h-20 bg-blue-500 text-white rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold"
                        >
                            1
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">
                            Daftar & Setup
                        </h3>
                        <p class="text-gray-600 mb-4">15 menit</p>
                        <ul class="text-left text-sm text-gray-600 space-y-2">
                            <li>• Daftar akun owner dan verifikasi OTP</li>
                            <li>• Tambahkan outlet beserta radius geofence</li>
                            <li>• Atur jam operasional dan kebijakan dasar</li>
                        </ul>
                    </div>

                    <!-- Step 2 -->
                    <div class="text-center">
                        <div
                            class="w-20 h-20 bg-green-500 text-white rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold"
                        >
                            2
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">
                            Tambah Karyawan
                        </h3>
                        <p class="text-gray-600 mb-4">10 menit</p>
                        <ul class="text-left text-sm text-gray-600 space-y-2">
                            <li>
                                • Unggah data karyawan via Excel atau input
                                manual
                            </li>
                            <li>• Buat kredensial untuk setiap karyawan</li>
                            <li>
                                • Karyawan verifikasi email dan buat password
                            </li>
                        </ul>
                    </div>

                    <!-- Step 3 -->
                    <div class="text-center">
                        <div
                            class="w-20 h-20 bg-orange-500 text-white rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold"
                        >
                            3
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">
                            Go Live
                        </h3>
                        <p class="text-gray-600 mb-4">30 menit</p>
                        <ul class="text-left text-sm text-gray-600 space-y-2">
                            <li>• Karyawan mengunduh aplikasi mobile</li>
                            <li>• Briefing cara absen geofencing</li>
                            <li>• Monitoring real-time dari dashboard owner</li>
                        </ul>
                    </div>
                </div>

                <div class="text-center mt-12">
                    <div
                        class="inline-block bg-gradient-to-r from-blue-500 to-purple-500 text-white px-8 py-4 rounded-lg"
                    >
                        <p class="text-xl font-bold">
                            Total Setup Time: 55 menit dari nol hingga go-live
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="py-20 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-gray-900 mb-4">
                        Cerita Sukses UMKM
                    </h2>
                </div>

                <div class="grid lg:grid-cols-2 gap-8 max-w-4xl mx-auto">
                    <!-- Testimonial 1 -->
                    <div class="bg-white rounded-xl shadow-lg p-8">
                        <div class="flex items-center mb-6">
                            <img
                                src="/images/testimonial-budi.jpg"
                                alt="Budi Santoso"
                                class="w-16 h-16 rounded-full mr-4"
                                onerror="this.src='https://picsum.photos/seed/budi/64/64.jpg'"
                            />
                            <div>
                                <h4 class="font-bold text-gray-900">
                                    Budi Santoso
                                </h4>
                                <p class="text-gray-600">
                                    Pemilik Kafe 'Kopi Tumbuh'
                                </p>
                            </div>
                        </div>
                        <div class="flex mb-4">
                            <svg
                                class="w-5 h-5 text-yellow-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                />
                            </svg>
                            <svg
                                class="w-5 h-5 text-yellow-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                />
                            </svg>
                            <svg
                                class="w-5 h-5 text-yellow-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                />
                            </svg>
                            <svg
                                class="w-5 h-5 text-yellow-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                />
                            </svg>
                            <svg
                                class="w-5 h-5 text-yellow-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                />
                            </svg>
                        </div>
                        <p class="text-gray-700 italic mb-6">
                            "Sebelum TOSEN-TOGA Presence, saya kehilangan Rp 15
                            juta/bulan karena buddy punching. Setelah 3 bulan,
                            produktivitas naik 40%, biaya lembur turun 25%, dan
                            saya fokus menumbuhkan bisnis."
                        </p>
                        <div class="border-t pt-4">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <p
                                        class="text-2xl font-bold text-green-600"
                                    >
                                        Rp 15jt
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        Hemat/bulan
                                    </p>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-blue-600">
                                        20 jam
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        Hemat/bulan
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-2xl font-bold text-orange-600"
                                    >
                                        40%
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        Produktivitas ↑
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="bg-white rounded-xl shadow-lg p-8">
                        <div class="flex items-center mb-6">
                            <img
                                src="/images/testimonial-siti.jpg"
                                alt="Siti Aminah"
                                class="w-16 h-16 rounded-full mr-4"
                                onerror="this.src='https://picsum.photos/seed/siti/64/64.jpg'"
                            />
                            <div>
                                <h4 class="font-bold text-gray-900">
                                    Siti Aminah
                                </h4>
                                <p class="text-gray-600">
                                    Manajer Garmen 'Konveksi Cepat'
                                </p>
                            </div>
                        </div>
                        <div class="flex mb-4">
                            <svg
                                class="w-5 h-5 text-yellow-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                />
                            </svg>
                            <svg
                                class="w-5 h-5 text-yellow-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                />
                            </svg>
                            <svg
                                class="w-5 h-5 text-yellow-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                />
                            </svg>
                            <svg
                                class="w-5 h-5 text-yellow-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                />
                            </svg>
                            <svg
                                class="w-5 h-5 text-yellow-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                />
                            </svg>
                        </div>
                        <p class="text-gray-700 italic mb-6">
                            "Dengan tiga cabang dan 75 karyawan, manajemen
                            kehadiran dulu mimpi buruk. TOSEN-TOGA Presence
                            memberi bukti lokasi, cuti & lembur otomatis, serta
                            menekan keluhan gaji hampir habis."
                        </p>
                        <div class="border-t pt-4">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <p
                                        class="text-2xl font-bold text-green-600"
                                    >
                                        98%
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        Akurasi Kehadiran
                                    </p>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-blue-600">
                                        90%
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        Keluhan ↓
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-2xl font-bold text-orange-600"
                                    >
                                        65%
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        Efisiensi ↑
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="py-20 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-gray-900 mb-4">
                        Pertanyaan Populer
                    </h2>
                </div>

                <div class="max-w-3xl mx-auto space-y-4">
                    <!-- FAQ 1 -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <button
                            @click="toggleFaq(0)"
                            class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
                        >
                            <span class="font-semibold text-gray-900"
                                >Apakah data saya aman?</span
                            >
                            <svg
                                class="w-5 h-5 text-gray-500 transform transition-transform"
                                :class="expandedFaq === 0 ? 'rotate-180' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <div
                            class="px-6 pb-4 text-gray-700 transition-all"
                            :class="
                                expandedFaq === 0
                                    ? 'max-h-96 opacity-100'
                                    : 'max-h-0 opacity-0 overflow-hidden'
                            "
                        >
                            <p class="pt-2">
                                Data Anda dilindungi dengan enkripsi AES-256,
                                backup harian, dan server berlokasi di
                                Indonesia. Kami mematuhi regulasi PDP dan audit
                                keamanan rutin.
                            </p>
                        </div>
                    </div>

                    <!-- FAQ 2 -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <button
                            @click="toggleFaq(1)"
                            class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
                        >
                            <span class="font-semibold text-gray-900"
                                >Bagaimana jika karyawan tidak ada sinyal?</span
                            >
                            <svg
                                class="w-5 h-5 text-gray-500 transform transition-transform"
                                :class="expandedFaq === 1 ? 'rotate-180' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <div
                            class="px-6 pb-4 text-gray-700 transition-all"
                            :class="
                                expandedFaq === 1
                                    ? 'max-h-96 opacity-100'
                                    : 'max-h-0 opacity-0 overflow-hidden'
                            "
                        >
                            <p class="pt-2">
                                TOSEN-TOGA Presence memiliki mode offline yang
                                menyimpan data di perangkat. Data akan otomatis
                                tersinkron saat koneksi kembali.
                            </p>
                        </div>
                    </div>

                    <!-- FAQ 3 -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <button
                            @click="toggleFaq(2)"
                            class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
                        >
                            <span class="font-semibold text-gray-900"
                                >Apakah sulit mengimpor data karyawan?</span
                            >
                            <svg
                                class="w-5 h-5 text-gray-500 transform transition-transform"
                                :class="expandedFaq === 2 ? 'rotate-180' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <div
                            class="px-6 pb-4 text-gray-700 transition-all"
                            :class="
                                expandedFaq === 2
                                    ? 'max-h-96 opacity-100'
                                    : 'max-h-0 opacity-0 overflow-hidden'
                            "
                        >
                            <p class="pt-2">
                                Tidak! Import data karyawan dari Excel dengan
                                template yang kami sediakan. Proses 100 karyawan
                                hanya butuh 2 menit.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA Section -->
        <section id="final-cta" class="tosen-hero py-20 text-white">
            <div class="container mx-auto px-4 text-center">
                <h2 class="tosen-heading-1 mb-6">
                    Mulai Transformasi Kehadiran Hari Ini
                </h2>
                <p class="text-xl mb-12 max-w-3xl mx-auto">
                    Bergabung dengan 500+ UMKM yang menjaga produktivitas dan
                    efisiensi bersama TOSEN-TOGA Presence.
                </p>

                <div
                    class="flex flex-col sm:flex-row gap-4 justify-center mb-8"
                >
                    <button
                        @click="showTrialModal = true"
                        class="tosen-btn tosen-btn-lg tosen-btn-secondary"
                    >
                        Aktifkan Akun Gratis
                    </button>
                    <button
                        @click="showDemoModal = true"
                        class="tosen-btn tosen-btn-lg tosen-btn-outline"
                    >
                        Jadwalkan Demo
                    </button>
                </div>

                <p class="text-white/90 text-lg">
                    Tanpa komitmen, batalkan kapan saja. Transformasi kehadiran
                    hanya satu klik lagi.
                </p>

                <div class="mt-8 text-white/80">
                    <p class="mb-2">Butuh bantuan? Hubungi kami di</p>
                    <p class="text-xl font-semibold">
                        0800-1234-5678 atau support@tosen-toga.id
                    </p>
                </div>
            </div>
        </section>

        <!-- Trial Modal -->
        <div
            v-if="showTrialModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        >
            <div class="bg-white rounded-xl max-w-md w-full p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">
                        Mulai Trial Gratis
                    </h3>
                    <button
                        @click="showTrialModal = false"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        <svg
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
                            />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitTrial" class="space-y-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Nama Lengkap</label
                        >
                        <input
                            v-model="trialForm.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="John Doe"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Email</label
                        >
                        <input
                            v-model="trialForm.email"
                            type="email"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="john@company.com"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >No. Telepon</label
                        >
                        <input
                            v-model="trialForm.phone"
                            type="tel"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="0812-3456-7890"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Nama Perusahaan</label
                        >
                        <input
                            v-model="trialForm.company_name"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="PT. Contoh Indonesia"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Jumlah Karyawan</label
                        >
                        <input
                            v-model="trialForm.employee_count"
                            type="number"
                            required
                            min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="10"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Jenis Bisnis</label
                        >
                        <select
                            v-model="trialForm.business_type"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                        >
                            <option value="">Pilih jenis bisnis</option>
                            <option value="f&b">F&B</option>
                            <option value="retail">Retail</option>
                            <option value="jasa">Jasa</option>
                            <option value="manufaktur">Manufaktur</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        :disabled="submittingTrial"
                        class="w-full py-3 bg-orange-500 hover:bg-orange-600 disabled:bg-gray-400 text-white font-bold rounded-lg transition-colors"
                    >
                        {{
                            submittingTrial
                                ? "Mengirim..."
                                : "Mulai Trial Gratis"
                        }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Demo Modal -->
        <div
            v-if="showDemoModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        >
            <div
                class="bg-white rounded-xl max-w-md w-full p-8 max-h-[90vh] overflow-y-auto"
            >
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">
                        Jadwalkan Demo Personal
                    </h3>
                    <button
                        @click="showDemoModal = false"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        <svg
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
                            />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitDemo" class="space-y-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Nama Lengkap</label
                        >
                        <input
                            v-model="demoForm.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="John Doe"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Email</label
                        >
                        <input
                            v-model="demoForm.email"
                            type="email"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="john@company.com"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >No. Telepon</label
                        >
                        <input
                            v-model="demoForm.phone"
                            type="tel"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="0812-3456-7890"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Nama Perusahaan</label
                        >
                        <input
                            v-model="demoForm.company_name"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="PT. Contoh Indonesia"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Jumlah Karyawan</label
                        >
                        <input
                            v-model="demoForm.employee_count"
                            type="number"
                            required
                            min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="10"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Tanggal Demo</label
                        >
                        <input
                            v-model="demoForm.preferred_date"
                            type="date"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Waktu Demo</label
                        >
                        <select
                            v-model="demoForm.preferred_time"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                        >
                            <option value="">Pilih waktu</option>
                            <option value="09:00">09:00</option>
                            <option value="10:00">10:00</option>
                            <option value="11:00">11:00</option>
                            <option value="13:00">13:00</option>
                            <option value="14:00">14:00</option>
                            <option value="15:00">15:00</option>
                            <option value="16:00">16:00</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Catatan (Opsional)</label
                        >
                        <textarea
                            v-model="demoForm.notes"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="Topik atau kebutuhan khusus untuk demo..."
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        :disabled="submittingDemo"
                        class="w-full py-3 bg-orange-500 hover:bg-orange-600 disabled:bg-gray-400 text-white font-bold rounded-lg transition-colors"
                    >
                        {{ submittingDemo ? "Mengirim..." : "Jadwalkan Demo" }}
                    </button>
                </form>
            </div>
        </div>
    </LandingLayout>
</template>
