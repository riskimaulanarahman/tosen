<script setup lang="ts">
import { ref } from "vue";

interface CTAForm {
    name: string;
    email: string;
    company: string;
    phone: string;
    employees: string;
    message: string;
}

const emit = defineEmits(["submit-trial", "submit-demo"]);

const isSubmittingTrial = ref(false);
const isSubmittingDemo = ref(false);

const trialForm = ref<CTAForm>({
    name: "",
    email: "",
    company: "",
    phone: "",
    employees: "",
    message: "",
});

const demoForm = ref<CTAForm>({
    name: "",
    email: "",
    company: "",
    phone: "",
    employees: "",
    message: "",
});

const validateTrialForm = () => {
    return (
        trialForm.value.name && trialForm.value.email && trialForm.value.company
    );
};

const validateDemoForm = () => {
    return (
        demoForm.value.name && demoForm.value.email && demoForm.value.company
    );
};

const handleTrialSubmit = () => {
    if (validateTrialForm()) {
        isSubmittingTrial.value = true;
        emit("submit-trial", trialForm.value);
        setTimeout(() => {
            isSubmittingTrial.value = false;
            trialForm.value = {
                name: "",
                email: "",
                company: "",
                phone: "",
                employees: "",
                message: "",
            };
        }, 2000);
    }
};

const handleDemoSubmit = () => {
    if (validateDemoForm()) {
        isSubmittingDemo.value = true;
        emit("submit-demo", demoForm.value);
        setTimeout(() => {
            isSubmittingDemo.value = false;
            demoForm.value = {
                name: "",
                email: "",
                company: "",
                phone: "",
                employees: "",
                message: "",
            };
        }, 2000);
    }
};

const benefits = [
    "14 hari trial penuh - tidak ada batasan fitur",
    "Tidak perlu kartu kredit untuk memulai",
    "Setup dalam 5 menit atau kurang",
    "Dedicated onboarding support",
    "Cancel kapan saja tanpa penalty",
];

const stats = [
    { value: "500+", label: "Bisnis Percaya" },
    { value: "40%", label: "Peningkatan Produktivitas" },
    { value: "4.9/5", label: "Rating Pengguna" },
    { value: "48jam", label: "Implementasi" },
];
</script>

<template>
    <section
        class="syncflow-cta-final py-20 lg:py-32 bg-gradient-to-br from-syncflow-primary to-syncflow-primary-dark relative overflow-hidden"
    >
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div
                class="absolute top-20 left-10 w-32 h-32 border-2 border-white rounded-lg transform rotate-12"
            ></div>
            <div
                class="absolute bottom-20 right-20 w-40 h-40 border-2 border-white rounded-full transform -rotate-6"
            ></div>
            <div
                class="absolute top-1/2 left-1/3 w-24 h-24 border-2 border-white rounded-lg transform rotate-45"
            ></div>
        </div>

        <div class="syncflow-container relative z-10">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <h2
                    class="syncflow-text-3xl lg:text-4xl font-bold text-white mb-6"
                >
                    Siap Untuk Transformasi Manajemen Proyek Anda?
                </h2>
                <p
                    class="syncflow-text-xl text-white/90 max-w-3xl mx-auto mb-8"
                >
                    Bergabunglah dengan 500+ bisnis yang telah meningkatkan
                    produktivitas hingga 40% dengan SyncFlow
                </p>

                <!-- Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                    <div
                        v-for="(stat, index) in stats"
                        :key="index"
                        class="text-center"
                    >
                        <div
                            class="syncflow-text-3xl font-bold text-syncflow-accent mb-2"
                        >
                            {{ stat.value }}
                        </div>
                        <div class="text-white/80">{{ stat.label }}</div>
                    </div>
                </div>
            </div>

            <!-- CTA Forms -->
            <div class="grid lg:grid-cols-2 gap-12 max-w-6xl mx-auto">
                <!-- Trial Form -->
                <div class="bg-white rounded-2xl p-8 shadow-2xl">
                    <div class="text-center mb-8">
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 bg-syncflow-accent rounded-full mb-4"
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
                        <h3
                            class="syncflow-text-2xl font-bold text-syncflow-dark mb-2"
                        >
                            Mulai Trial Gratis 14 Hari
                        </h3>
                        <p class="text-gray-600">
                            Akses semua fitur Professional tanpa batasan
                        </p>
                    </div>

                    <form @submit.prevent="handleTrialSubmit" class="space-y-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Nama Lengkap</label
                            >
                            <input
                                v-model="trialForm.name"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-syncflow-primary focus:border-transparent"
                                placeholder="John Doe"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Email Perusahaan</label
                            >
                            <input
                                v-model="trialForm.email"
                                type="email"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-syncflow-primary focus:border-transparent"
                                placeholder="john@company.com"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Nama Perusahaan</label
                            >
                            <input
                                v-model="trialForm.company"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-syncflow-primary focus:border-transparent"
                                placeholder="PT. Example"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Nomor Telepon (Opsional)</label
                            >
                            <input
                                v-model="trialForm.phone"
                                type="tel"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-syncflow-primary focus:border-transparent"
                                placeholder="+62 812-3456-7890"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Jumlah Karyawan</label
                            >
                            <select
                                v-model="trialForm.employees"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-syncflow-primary focus:border-transparent"
                            >
                                <option value="">Pilih jumlah karyawan</option>
                                <option value="1-10">1-10</option>
                                <option value="11-50">11-50</option>
                                <option value="51-200">51-200</option>
                                <option value="201+">201+</option>
                            </select>
                        </div>

                        <button
                            type="submit"
                            :disabled="isSubmittingTrial"
                            class="w-full syncflow-btn-accent syncflow-btn-lg py-4 font-semibold"
                        >
                            <span v-if="!isSubmittingTrial"
                                >Mulai Trial Gratis</span
                            >
                            <span
                                v-else
                                class="flex items-center justify-center"
                            >
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
                    </form>
                </div>

                <!-- Demo Form -->
                <div class="bg-white rounded-2xl p-8 shadow-2xl">
                    <div class="text-center mb-8">
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 bg-syncflow-primary rounded-full mb-4"
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
                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                ></path>
                            </svg>
                        </div>
                        <h3
                            class="syncflow-text-2xl font-bold text-syncflow-dark mb-2"
                        >
                            Jadwalkan Demo Personal
                        </h3>
                        <p class="text-gray-600">
                            Konsultasi 1-on-1 dengan expert kami
                        </p>
                    </div>

                    <form @submit.prevent="handleDemoSubmit" class="space-y-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Nama Lengkap</label
                            >
                            <input
                                v-model="demoForm.name"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-syncflow-primary focus:border-transparent"
                                placeholder="John Doe"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Email Perusahaan</label
                            >
                            <input
                                v-model="demoForm.email"
                                type="email"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-syncflow-primary focus:border-transparent"
                                placeholder="john@company.com"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Nama Perusahaan</label
                            >
                            <input
                                v-model="demoForm.company"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-syncflow-primary focus:border-transparent"
                                placeholder="PT. Example"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Nomor Telepon</label
                            >
                            <input
                                v-model="demoForm.phone"
                                type="tel"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-syncflow-primary focus:border-transparent"
                                placeholder="+62 812-3456-7890"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Jumlah Karyawan</label
                            >
                            <select
                                v-model="demoForm.employees"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-syncflow-primary focus:border-transparent"
                            >
                                <option value="">Pilih jumlah karyawan</option>
                                <option value="1-10">1-10</option>
                                <option value="11-50">11-50</option>
                                <option value="51-200">51-200</option>
                                <option value="201+">201+</option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Pesan/Kebutuhan Khusus</label
                            >
                            <textarea
                                v-model="demoForm.message"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-syncflow-primary focus:border-transparent"
                                placeholder="Ceritakan tentang kebutuhan proyek Anda..."
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            :disabled="isSubmittingDemo"
                            class="w-full syncflow-btn-primary syncflow-btn-lg py-4 font-semibold"
                        >
                            <span v-if="!isSubmittingDemo">Jadwalkan Demo</span>
                            <span
                                v-else
                                class="flex items-center justify-center"
                            >
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
                    </form>
                </div>
            </div>

            <!-- Benefits -->
            <div class="mt-16 text-center">
                <h3 class="syncflow-text-xl font-semibold text-white mb-6">
                    Mengapa Memilih SyncFlow?
                </h3>
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4"
                >
                    <div
                        v-for="(benefit, index) in benefits"
                        :key="index"
                        class="flex items-center space-x-2 text-white/90"
                    >
                        <svg
                            class="w-5 h-5 text-syncflow-accent flex-shrink-0"
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

            <!-- Trust Indicators -->
            <div class="mt-12 text-center">
                <div
                    class="flex flex-wrap justify-center items-center gap-8 opacity-60"
                >
                    <div class="text-white/80 text-sm">🔒 100% Secure</div>
                    <div class="text-white/80 text-sm">✓ GDPR Compliant</div>
                    <div class="text-white/80 text-sm">🏆 Award Winning</div>
                    <div class="text-white/80 text-sm">🌟 4.9/5 Rating</div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.syncflow-cta-final {
    position: relative;
}

@media (max-width: 768px) {
    .syncflow-cta-final {
        padding: 3rem 0;
    }
}
</style>
