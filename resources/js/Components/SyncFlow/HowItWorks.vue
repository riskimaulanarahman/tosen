<script setup lang="ts">
import { ref } from "vue";

interface Step {
    number: number;
    title: string;
    description: string;
    details: string[];
    icon: string;
}

const steps: Step[] = [
    {
        number: 1,
        title: "Sign Up & Onboarding",
        description:
            "Daftar gratis dan mulai setup workspace Anda dalam hitungan menit",
        details: [
            "Registrasi 2 menit tanpa kartu kredit",
            "Import data dari tools existing",
            "Tutorial interaktif untuk tim Anda",
            "Setup otomatis untuk 50+ templates",
        ],
        icon: "M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z",
    },
    {
        number: 2,
        title: "Create Your First Project",
        description:
            "Mulai proyek pertama dengan template yang sudah dioptimasi untuk industri Anda",
        details: [
            "Pilih dari 100+ template proyek",
            "Custom workflow sesuai kebutuhan",
            "Set milestones dan dependencies",
            "Invite tim dengan role-based access",
        ],
        icon: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
    },
    {
        number: 3,
        title: "Collaborate in Real-Time",
        description:
            "Tim Anda dapat bekerja bersama dengan update langsung dan komunikasi terintegrasi",
        details: [
            "Real-time task updates",
            "Integrated chat per task",
            "File sharing dengan version control",
            "Activity tracking & notifications",
        ],
        icon: "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z",
    },
    {
        number: 4,
        title: "Track Progress & Analytics",
        description:
            "Monitor performa proyek dengan dashboard analytics dan laporan otomatis",
        details: [
            "Real-time KPI dashboard",
            "Custom report generation",
            "Performance insights",
            "Predictive analytics",
        ],
        icon: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z",
    },
];

const activeStep = ref(0);

const setActiveStep = (index: number) => {
    activeStep.value = index;
};
</script>

<template>
    <section class="syncflow-how-it-works py-20 lg:py-32 bg-gray-50">
        <div class="syncflow-container">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <h2
                    class="syncflow-text-3xl lg:text-4xl font-bold text-syncflow-dark mb-6"
                >
                    Mulai Transformasi Bisnis Anda dalam 4 Langkah Sederhana
                </h2>
                <p class="syncflow-text-lg text-gray-600 max-w-3xl mx-auto">
                    Dari registrasi hingga proyek produktif, SyncFlow memudahkan
                    setiap tahap dengan guidance yang jelas
                </p>
            </div>

            <!-- Steps Progress -->
            <div class="mb-12">
                <div
                    class="flex flex-col lg:flex-row justify-between items-center space-y-4 lg:space-y-0"
                >
                    <div
                        v-for="(step, index) in steps"
                        :key="index"
                        class="flex items-center cursor-pointer group"
                        @click="setActiveStep(index)"
                    >
                        <div
                            class="flex items-center justify-center w-12 h-12 rounded-full border-2 transition-all duration-300"
                            :class="{
                                'bg-syncflow-primary border-syncflow-primary text-white':
                                    activeStep === index,
                                'border-syncflow-primary text-syncflow-primary hover:bg-syncflow-primary/10':
                                    activeStep !== index,
                            }"
                        >
                            <span class="font-semibold">{{ step.number }}</span>
                        </div>
                        <div class="ml-4">
                            <h3
                                class="font-semibold transition-colors"
                                :class="{
                                    'text-syncflow-primary':
                                        activeStep === index,
                                    'text-gray-700 group-hover:text-syncflow-primary':
                                        activeStep !== index,
                                }"
                            >
                                {{ step.title }}
                            </h3>
                        </div>
                        <!-- Connector Line -->
                        <div
                            v-if="index < steps.length - 1"
                            class="hidden lg:block w-24 h-0.5 bg-gray-300 mx-4"
                            :class="{
                                'bg-syncflow-primary': activeStep > index,
                            }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Step Details -->
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Step Content -->
                <div>
                    <div
                        v-for="(step, index) in steps"
                        :key="index"
                        class="space-y-6"
                        :class="{
                            block: activeStep === index,
                            hidden: activeStep !== index,
                        }"
                    >
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div
                                    class="flex items-center justify-center w-16 h-16 rounded-lg transition-colors"
                                    :class="{
                                        'bg-syncflow-primary text-white':
                                            activeStep === index,
                                        'bg-gray-200 text-gray-600':
                                            activeStep !== index,
                                    }"
                                >
                                    <svg
                                        class="w-8 h-8"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="step.icon"
                                        ></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3
                                    class="syncflow-text-2xl font-bold text-syncflow-dark mb-3"
                                >
                                    {{ step.title }}
                                </h3>
                                <p class="syncflow-text-lg text-gray-600 mb-6">
                                    {{ step.description }}
                                </p>
                                <div class="space-y-3">
                                    <div
                                        v-for="(
                                            detail, detailIndex
                                        ) in step.details"
                                        :key="detailIndex"
                                        class="flex items-center space-x-3"
                                    >
                                        <svg
                                            class="w-5 h-5 text-syncflow-success flex-shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        <span class="text-gray-700">{{
                                            detail
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step Visual -->
                <div class="relative">
                    <div class="bg-white rounded-2xl shadow-xl p-8">
                        <div class="aspect-w-16 aspect-h-9">
                            <img
                                :src="`/images/syncflow-step-${
                                    activeStep + 1
                                }.svg`"
                                :alt="steps[activeStep].title"
                                class="w-full h-auto rounded-lg"
                                loading="lazy"
                            />
                        </div>
                    </div>

                    <!-- Step Navigation -->
                    <div class="flex justify-between mt-6">
                        <button
                            @click="setActiveStep(Math.max(0, activeStep - 1))"
                            :disabled="activeStep === 0"
                            class="flex items-center space-x-2 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 19l-7-7 7-7"
                                ></path>
                            </svg>
                            <span>Previous</span>
                        </button>
                        <button
                            @click="
                                setActiveStep(
                                    Math.min(steps.length - 1, activeStep + 1)
                                )
                            "
                            :disabled="activeStep === steps.length - 1"
                            class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-syncflow-primary text-white hover:bg-syncflow-primary-dark disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <span>Next</span>
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                ></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Timeline Summary -->
            <div
                class="mt-20 bg-gradient-to-r from-syncflow-primary to-syncflow-primary-dark rounded-2xl p-8 text-white"
            >
                <div class="text-center mb-8">
                    <h3 class="syncflow-text-2xl font-bold mb-4">
                        Timeline Implementasi
                    </h3>
                    <p class="text-white/90">
                        Dari nol hingga produktif dalam waktu singkat
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="syncflow-text-2xl font-bold mb-2">
                            Hari 1
                        </div>
                        <div class="text-white/80">Registrasi & Setup</div>
                    </div>
                    <div class="text-center">
                        <div class="syncflow-text-2xl font-bold mb-2">
                            Hari 2-3
                        </div>
                        <div class="text-white/80">Onboarding Tim</div>
                    </div>
                    <div class="text-center">
                        <div class="syncflow-text-2xl font-bold mb-2">
                            Hari 4-7
                        </div>
                        <div class="text-white/80">Proyek Pertama</div>
                    </div>
                    <div class="text-center">
                        <div class="syncflow-text-2xl font-bold mb-2">
                            Hari 8+
                        </div>
                        <div class="text-white/80">Produktif Penuh</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.syncflow-how-it-works {
    position: relative;
}

.syncflow-how-it-works::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
}

@media (max-width: 768px) {
    .syncflow-how-it-works {
        padding: 3rem 0;
    }
}
</style>
