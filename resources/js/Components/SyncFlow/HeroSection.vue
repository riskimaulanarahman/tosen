<script setup lang="ts">
const props = defineProps<{
    trialForm?: {
        name?: string;
        email?: string;
        company?: string;
        phone?: string;
        employees?: string;
        industry?: string;
    };
    demoForm?: {
        name?: string;
        email?: string;
        company?: string;
        phone?: string;
        employees?: string;
        preferred_date?: string;
        preferred_time?: string;
        notes?: string;
    };
    isSubmittingTrial?: boolean;
    isSubmittingDemo?: boolean;
}>();

const emit = defineEmits(["submit-trial", "submit-demo"]);

// Form validation
const validateTrialForm = () => {
    if (
        !props.trialForm?.name ||
        !props.trialForm?.email ||
        !props.trialForm?.company
    ) {
        return false;
    }
    return true;
};

const validateDemoForm = () => {
    if (
        !props.demoForm?.name ||
        !props.demoForm?.email ||
        !props.demoForm?.company ||
        !props.demoForm?.preferred_date
    ) {
        return false;
    }
    return true;
};

// Form submission
const handleTrialSubmit = () => {
    if (validateTrialForm()) {
        emit("submit-trial");
    }
};

const handleDemoSubmit = () => {
    if (validateDemoForm()) {
        emit("submit-demo");
    }
};
</script>

<template>
    <section class="syncflow-hero relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div
                class="absolute top-20 left-10 w-32 h-32 border-2 border-syncflow-primary-300 rounded-lg transform rotate-12"
            ></div>
            <div
                class="absolute bottom-20 right-20 w-40 h-40 border-2 border-syncflow-primary-300 rounded-full transform -rotate-6"
            ></div>
            <div
                class="absolute top-1/2 left-1/3 w-24 h-24 border-2 border-syncflow-primary-300 rounded-lg transform rotate-45"
            ></div>
        </div>

        <div class="syncflow-container relative z-10">
            <div class="text-center py-20 lg:py-32">
                <!-- Main Headline -->
                <h1
                    class="syncflow-text-4xl font-bold text-white mb-6 leading-tight animate-fade-in"
                >
                    Transformasi Manajemen Proyek Anda:
                    <span class="block text-syncflow-accent"
                        >Dari Kegagalan Berulang Hingga Kesuksesan
                        Berkelanjutan</span
                    >
                </h1>

                <!-- Sub-headline -->
                <p
                    class="syncflow-text-lg text-white/90 mb-12 max-w-4xl mx-auto leading-relaxed animate-slide-up"
                >
                    Platform kolaborasi tim real-time dan otomasi pelaporan yang
                    membantu bisnis kecil dan menengah meningkatkan
                    produktivitas hingga 40% tanpa menambah overhead.
                </p>

                <!-- CTA Buttons -->
                <div
                    class="flex flex-col sm:flex-row gap-6 justify-center mb-16 animate-slide-up"
                >
                    <button
                        @click="handleTrialSubmit"
                        :disabled="isSubmittingTrial"
                        class="syncflow-btn-accent syncflow-btn-lg text-lg px-8 py-4"
                    >
                        <span v-if="!isSubmittingTrial"
                            >Mulai Trial Gratis 14 Hari</span
                        >
                        <span v-else class="flex items-center">
                            <svg
                                class="animate-spin h-5 w-5 mr-3"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                    fill="none"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            Memproses...
                        </span>
                    </button>
                    <button
                        @click="handleDemoSubmit"
                        :disabled="isSubmittingDemo"
                        class="syncflow-btn-secondary syncflow-btn-lg text-lg px-8 py-4"
                    >
                        <span v-if="!isSubmittingDemo"
                            >Jadwalkan Demo Personal</span
                        >
                        <span v-else class="flex items-center">
                            <svg
                                class="animate-spin h-5 w-5 mr-3"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                    fill="none"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            Memproses...
                        </span>
                    </button>
                </div>

                <!-- Trust Indicators -->
                <div
                    class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-8 text-white/80 animate-fade-in"
                >
                    <div class="flex items-center space-x-2">
                        <svg
                            class="w-5 h-5 text-syncflow-success"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"
                            ></path>
                        </svg>
                        <span>Dipercaya oleh 500+ Bisnis di Indonesia</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg
                            class="w-5 h-5 text-syncflow-success"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.95 1.693l-1.07 3.292z"
                            ></path>
                        </svg>
                        <span>4.9/5 Rating di G2 & Capterra</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg
                            class="w-5 h-5 text-syncflow-success"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002 2v-1H7a1 1 0 10-2 0v1H7zm0 5a1 1 0 000 2h8a1 1 0 100 2h8z"
                                clip-rule="evenodd"
                            ></path>
                        </svg>
                        <span>Implementasi dalam 48 Jam atau Kurang</span>
                    </div>
                </div>

                <!-- Product Screenshot -->
                <div class="mt-16 relative animate-float">
                    <div
                        class="relative rounded-2xl overflow-hidden shadow-2xl"
                    >
                        <picture>
                            <source
                                srcset="/images/syncflow-dashboard.webp"
                                type="image/webp"
                            />
                            <img
                                src="/images/syncflow-dashboard.jpg"
                                alt="SyncFlow Dashboard"
                                class="w-full h-auto rounded-2xl"
                                loading="lazy"
                                decoding="async"
                            />
                        </picture>
                        <!-- Overlay gradient -->
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-syncflow-primary/20 to-transparent pointer-events-none"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
