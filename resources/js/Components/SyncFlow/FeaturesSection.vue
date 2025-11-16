<script setup lang="ts">
import { ref } from "vue";

interface Feature {
    icon: string;
    title: string;
    description: string;
    benefits: string[];
}

interface FeaturesSectionProps {
    variant?: "default" | "detailed";
}

const props = withDefaults(defineProps<FeaturesSectionProps>(), {
    variant: "default",
});

const features: Feature[] = [
    {
        icon: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4",
        title: "Kolaborasi Real-Time",
        description:
            "Tim Anda dapat bekerja bersama secara simultan dengan update langsung dan notifikasi instant.",
        benefits: [
            "Update progress otomatis",
            "Chat terintegrasi per task",
            "File sharing dengan version control",
            "Activity tracking terperinci",
        ],
    },
    {
        icon: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z",
        title: "Dashboard Analytics",
        description:
            "Visualisasi data proyek yang komprehensif dengan insight actionable untuk pengambilan keputusan.",
        benefits: [
            "Real-time KPI monitoring",
            "Custom report generation",
            "Predictive analytics",
            "Performance benchmarking",
        ],
    },
    {
        icon: "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z",
        title: "Automasi Workflow",
        description:
            "Hilangkan tugas repetitif dengan automasi cerdas yang meningkatkan efisiensi tim.",
        benefits: [
            "Auto-assignment tasks",
            "Deadline reminders",
            "Status escalation rules",
            "Custom trigger actions",
        ],
    },
    {
        icon: "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z",
        title: "Resource Management",
        description:
            "Optimalkan alokasi sumber daya dengan tracking availability dan workload balancing.",
        benefits: [
            "Team capacity planning",
            "Skill-based assignment",
            "Workload distribution",
            "Resource utilization reports",
        ],
    },
    {
        icon: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
        title: "Laporan Otomatis",
        description:
            "Generate laporan proyek profesional dengan satu klik untuk stakeholder dan manajemen.",
        benefits: [
            "Custom template reports",
            "Scheduled delivery",
            "Multi-format export",
            "Executive summaries",
        ],
    },
    {
        icon: "M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z",
        title: "Enterprise Security",
        description:
            "Keamanan data tingkat enterprise dengan enkripsi end-to-end dan compliance standards.",
        benefits: [
            "256-bit encryption",
            "Role-based access control",
            "Audit trail logging",
            "GDPR & ISO compliance",
        ],
    },
];

const activeFeature = ref(0);

const setActiveFeature = (index: number) => {
    activeFeature.value = index;
};
</script>

<template>
    <section class="syncflow-features py-20 lg:py-32 bg-white">
        <div class="syncflow-container">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <h2
                    class="syncflow-text-3xl lg:text-4xl font-bold text-syncflow-dark mb-6"
                >
                    Fitur Lengkap Untuk Transformasi Bisnis Anda
                </h2>
                <p class="syncflow-text-lg text-gray-600 max-w-3xl mx-auto">
                    SyncFlow menyediakan semua tools yang Anda butuhkan untuk
                    mengelola proyek secara efisien dan meningkatkan
                    produktivitas tim
                </p>
            </div>

            <!-- Features Grid -->
            <div class="grid lg:grid-cols-2 xl:grid-cols-3 gap-8 mb-16">
                <div
                    v-for="(feature, index) in features"
                    :key="index"
                    class="group relative bg-white rounded-xl border border-gray-200 p-8 hover:shadow-xl transition-all duration-300 cursor-pointer"
                    :class="{
                        'ring-2 ring-syncflow-primary shadow-lg':
                            activeFeature === index,
                    }"
                    @mouseenter="setActiveFeature(index)"
                    @click="setActiveFeature(index)"
                >
                    <!-- Feature Icon -->
                    <div
                        class="flex items-center justify-center w-16 h-16 bg-syncflow-primary/10 rounded-lg mb-6 group-hover:bg-syncflow-primary/20 transition-colors"
                    >
                        <svg
                            class="w-8 h-8 text-syncflow-primary"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="feature.icon"
                            ></path>
                        </svg>
                    </div>

                    <!-- Feature Content -->
                    <h3
                        class="syncflow-text-xl font-semibold text-syncflow-dark mb-3 group-hover:text-syncflow-primary transition-colors"
                    >
                        {{ feature.title }}
                    </h3>
                    <p class="text-gray-600 mb-4">
                        {{ feature.description }}
                    </p>

                    <!-- Feature Benefits (shown on hover/active) -->
                    <div class="space-y-2">
                        <div
                            v-for="(benefit, benefitIndex) in feature.benefits"
                            :key="benefitIndex"
                            class="flex items-center space-x-2 text-sm text-gray-600"
                            :class="{
                                'opacity-100 translate-y-0':
                                    activeFeature === index,
                                'opacity-0 translate-y-2':
                                    activeFeature !== index,
                            }"
                            style="transition: all 0.3s ease"
                        >
                            <svg
                                class="w-4 h-4 text-syncflow-success flex-shrink-0"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                            <span>{{ benefit }}</span>
                        </div>
                    </div>

                    <!-- Hover Indicator -->
                    <div
                        class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                        <svg
                            class="w-5 h-5 text-syncflow-primary"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6"
                            ></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Detailed Feature View -->
            <div
                v-if="activeFeature !== null && features[activeFeature]"
                class="bg-gradient-to-r from-syncflow-primary to-syncflow-primary-dark rounded-2xl p-8 text-white"
            >
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <div class="flex items-center space-x-4 mb-6">
                            <div
                                class="flex items-center justify-center w-16 h-16 bg-white/20 rounded-lg"
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
                                        :d="features[activeFeature].icon"
                                    ></path>
                                </svg>
                            </div>
                            <h3 class="syncflow-text-2xl font-bold">
                                {{ features[activeFeature].title }}
                            </h3>
                        </div>
                        <p class="text-white/90 text-lg mb-6">
                            {{ features[activeFeature].description }}
                        </p>
                        <div class="space-y-3">
                            <div
                                v-for="(benefit, benefitIndex) in features[
                                    activeFeature
                                ].benefits"
                                :key="benefitIndex"
                                class="flex items-center space-x-3"
                            >
                                <svg
                                    class="w-5 h-5 text-syncflow-accent"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <span>{{ benefit }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <img
                            src="/images/syncflow-feature-illustration.svg"
                            :alt="features[activeFeature].title"
                            class="w-full h-auto rounded-lg"
                            loading="lazy"
                        />
                    </div>
                </div>
            </div>

            <!-- Integration Partners -->
            <div class="mt-20 text-center">
                <h3 class="syncflow-text-xl font-semibold text-gray-600 mb-8">
                    Terintegrasi Dengan Tools Yang Anda Gunakan
                </h3>
                <div
                    class="flex flex-wrap justify-center items-center gap-8 opacity-60"
                >
                    <!-- Integration logos would go here -->
                    <div
                        class="w-32 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-500"
                    >
                        Slack
                    </div>
                    <div
                        class="w-32 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-500"
                    >
                        Google Workspace
                    </div>
                    <div
                        class="w-32 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-500"
                    >
                        Microsoft Teams
                    </div>
                    <div
                        class="w-32 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-500"
                    >
                        Jira
                    </div>
                    <div
                        class="w-32 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-500"
                    >
                        GitHub
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.syncflow-features {
    position: relative;
}

.syncflow-features::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
}

@media (max-width: 768px) {
    .syncflow-features {
        padding: 3rem 0;
    }
}
</style>
