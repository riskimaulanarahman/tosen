<script setup lang="ts">
import { ref } from "vue";

interface PricingPlan {
    id: string;
    name: string;
    description: string;
    price: {
        monthly: number;
        yearly: number;
    };
    features: string[];
    limitations: string[];
    highlighted: boolean;
    popular: boolean;
    cta: string;
}

const billingCycle = ref<"monthly" | "yearly">("yearly");

const plans: PricingPlan[] = [
    {
        id: "starter",
        name: "Starter",
        description: "Perfect untuk small teams yang baru mulai",
        price: {
            monthly: 299000,
            yearly: 2990000,
        },
        features: [
            "Hingga 10 users",
            "Unlimited projects",
            "Basic analytics dashboard",
            "Email support",
            "Mobile app access",
            "2GB storage per user",
            "Basic integrations",
        ],
        limitations: [
            "No advanced reporting",
            "Limited API access",
            "No custom workflows",
        ],
        highlighted: false,
        popular: false,
        cta: "Mulai Gratis",
    },
    {
        id: "professional",
        name: "Professional",
        description: "Ideal untuk growing businesses yang butuh lebih features",
        price: {
            monthly: 799000,
            yearly: 7990000,
        },
        features: [
            "Hingga 50 users",
            "Unlimited projects",
            "Advanced analytics & reporting",
            "Priority email & chat support",
            "Mobile & desktop apps",
            "10GB storage per user",
            "Advanced integrations",
            "Custom workflows",
            "Time tracking",
            "Budget management",
            "Client portal access",
        ],
        limitations: ["No dedicated account manager", "No custom SLA"],
        highlighted: true,
        popular: true,
        cta: "Mulai Trial Gratis",
    },
    {
        id: "enterprise",
        name: "Enterprise",
        description: "Complete solution untuk large organizations",
        price: {
            monthly: 1999000,
            yearly: 19990000,
        },
        features: [
            "Unlimited users",
            "Unlimited projects",
            "Enterprise analytics",
            "24/7 phone & email support",
            "All platform access",
            "Unlimited storage",
            "All integrations + custom",
            "Advanced workflows & automations",
            "Advanced time & resource tracking",
            "Advanced budget & financial management",
            "White-label client portal",
            "Dedicated account manager",
            "Custom SLA",
            "On-premise deployment option",
            "Advanced security features",
            "Custom training & onboarding",
        ],
        limitations: [],
        highlighted: false,
        popular: false,
        cta: "Hubungi Sales",
    },
];

const toggleBillingCycle = () => {
    billingCycle.value =
        billingCycle.value === "monthly" ? "yearly" : "monthly";
};

const formatPrice = (price: number) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const getDisplayPrice = (plan: PricingPlan) => {
    return billingCycle.value === "monthly"
        ? plan.price.monthly
        : plan.price.yearly;
};

const getMonthlyEquivalent = (plan: PricingPlan) => {
    const yearlyPrice = plan.price.yearly;
    return Math.floor(yearlyPrice / 12);
};

const getSavings = (plan: PricingPlan) => {
    const yearlyTotal = plan.price.yearly;
    const monthlyTotal = plan.price.monthly * 12;
    return Math.floor(((monthlyTotal - yearlyTotal) / monthlyTotal) * 100);
};
</script>

<template>
    <section class="syncflow-pricing py-20 lg:py-32 bg-gray-50">
        <div class="syncflow-container">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <h2
                    class="syncflow-text-3xl lg:text-4xl font-bold text-syncflow-dark mb-6"
                >
                    Harga Yang Sesuai Untuk Setiap Tahap Pertumbuhan Bisnis Anda
                </h2>
                <p
                    class="syncflow-text-lg text-gray-600 max-w-3xl mx-auto mb-8"
                >
                    Mulai gratis dan upgrade sesuai kebutuhan. Tidak ada biaya
                    tersembunyi.
                </p>

                <!-- Billing Toggle -->
                <div class="flex items-center justify-center space-x-4">
                    <span
                        class="font-medium transition-colors"
                        :class="{
                            'text-syncflow-primary': billingCycle === 'monthly',
                            'text-gray-500': billingCycle !== 'monthly',
                        }"
                    >
                        Bulanan
                    </span>
                    <button
                        @click="toggleBillingCycle"
                        class="relative inline-flex h-8 w-16 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-syncflow-primary focus:ring-offset-2"
                        :class="{
                            'bg-syncflow-primary': billingCycle === 'yearly',
                            'bg-gray-300': billingCycle === 'monthly',
                        }"
                    >
                        <span
                            class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform"
                            :class="{
                                'translate-x-9': billingCycle === 'yearly',
                                'translate-x-1': billingCycle === 'monthly',
                            }"
                        ></span>
                    </button>
                    <span
                        class="font-medium transition-colors"
                        :class="{
                            'text-syncflow-primary': billingCycle === 'yearly',
                            'text-gray-500': billingCycle !== 'yearly',
                        }"
                    >
                        Tahunan
                        <span
                            class="syncflow-text-sm text-syncflow-accent font-semibold ml-2"
                        >
                            (Hemat 20%)
                        </span>
                    </span>
                </div>
            </div>

            <!-- Pricing Cards -->
            <div class="grid lg:grid-cols-3 gap-8 mb-16">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="relative bg-white rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl"
                    :class="{
                        'ring-2 ring-syncflow-primary transform scale-105':
                            plan.highlighted,
                        'border border-gray-200': !plan.highlighted,
                    }"
                >
                    <!-- Popular Badge -->
                    <div
                        v-if="plan.popular"
                        class="absolute top-0 right-0 bg-syncflow-accent text-white px-4 py-1 text-sm font-semibold rounded-bl-lg"
                    >
                        Paling Populer
                    </div>

                    <div class="p-8">
                        <!-- Plan Header -->
                        <div class="text-center mb-8">
                            <h3
                                class="syncflow-text-2xl font-bold text-syncflow-dark mb-2"
                            >
                                {{ plan.name }}
                            </h3>
                            <p class="text-gray-600 mb-6">
                                {{ plan.description }}
                            </p>

                            <!-- Price -->
                            <div class="mb-4">
                                <div v-if="billingCycle === 'monthly'">
                                    <span
                                        class="syncflow-text-4xl font-bold text-syncflow-dark"
                                    >
                                        {{ formatPrice(getDisplayPrice(plan)) }}
                                    </span>
                                    <span class="text-gray-600">/bulan</span>
                                </div>
                                <div v-else>
                                    <div class="mb-2">
                                        <span
                                            class="syncflow-text-4xl font-bold text-syncflow-dark"
                                        >
                                            {{
                                                formatPrice(
                                                    getDisplayPrice(plan)
                                                )
                                            }}
                                        </span>
                                        <span class="text-gray-600"
                                            >/tahun</span
                                        >
                                    </div>
                                    <div
                                        class="text-sm text-green-600 font-medium"
                                    >
                                        {{
                                            formatPrice(
                                                getMonthlyEquivalent(plan)
                                            )
                                        }}/bulan (hemat {{ getSavings(plan) }}%)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-syncflow-dark mb-4">
                                Apa yang Anda dapatkan:
                            </h4>
                            <ul class="space-y-3">
                                <li
                                    v-for="feature in plan.features"
                                    :key="feature"
                                    class="flex items-start space-x-3"
                                >
                                    <svg
                                        class="w-5 h-5 text-syncflow-success flex-shrink-0 mt-0.5"
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
                                        feature
                                    }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Limitations -->
                        <div v-if="plan.limitations.length > 0" class="mb-8">
                            <h4 class="font-semibold text-syncflow-dark mb-4">
                                Batasan:
                            </h4>
                            <ul class="space-y-3">
                                <li
                                    v-for="limitation in plan.limitations"
                                    :key="limitation"
                                    class="flex items-start space-x-3"
                                >
                                    <svg
                                        class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5"
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
                                    <span class="text-gray-500">{{
                                        limitation
                                    }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- CTA Button -->
                        <button
                            class="w-full py-3 px-6 rounded-lg font-semibold transition-colors"
                            :class="{
                                'bg-syncflow-accent text-white hover:bg-syncflow-accent-dark':
                                    plan.highlighted,
                                'bg-syncflow-primary text-white hover:bg-syncflow-primary-dark':
                                    !plan.highlighted,
                            }"
                        >
                            {{ plan.cta }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="bg-white rounded-2xl p-8 shadow-lg">
                <div class="grid lg:grid-cols-2 gap-8">
                    <div>
                        <h3
                            class="syncflow-text-xl font-bold text-syncflow-dark mb-4"
                        >
                            Pertanyaan Umum Tentang Harga
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <h4
                                    class="font-semibold text-syncflow-dark mb-2"
                                >
                                    Apakah ada biaya setup?
                                </h4>
                                <p class="text-gray-600">
                                    Tidak, tidak ada biaya setup untuk semua
                                    paket. Anda bisa mulai langsung.
                                </p>
                            </div>
                            <div>
                                <h4
                                    class="font-semibold text-syncflow-dark mb-2"
                                >
                                    Bisakah saya ganti paket kapan saja?
                                </h4>
                                <p class="text-gray-600">
                                    Ya, Anda bisa upgrade atau downgrade kapan
                                    saja tanpa penalty.
                                </p>
                            </div>
                            <div>
                                <h4
                                    class="font-semibold text-syncflow-dark mb-2"
                                >
                                    Apakah data saya aman?
                                </h4>
                                <p class="text-gray-600">
                                    Ya, kami menggunakan enkripsi 256-bit dan
                                    compliance dengan standar internasional.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3
                            class="syncflow-text-xl font-bold text-syncflow-dark mb-4"
                        >
                            Money-Back Guarantee
                        </h3>
                        <div
                            class="bg-green-50 border border-green-200 rounded-lg p-6"
                        >
                            <div class="flex items-center space-x-3 mb-3">
                                <svg
                                    class="w-8 h-8 text-green-600"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <h4 class="font-semibold text-green-800">
                                    30 Hari Jaminan Kepuasan
                                </h4>
                            </div>
                            <p class="text-green-700">
                                Jika Anda tidak puas dengan SyncFlow dalam 30
                                hari pertama, kami akan refund 100% tanpa
                                pertanyaan.
                            </p>
                        </div>
                        <div class="mt-6 text-center">
                            <button
                                class="syncflow-btn-primary syncflow-btn-lg"
                            >
                                Mulai Trial Gratis 14 Hari
                            </button>
                            <p class="text-sm text-gray-500 mt-2">
                                Tidak perlu kartu kredit
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.syncflow-pricing {
    position: relative;
}

.syncflow-pricing::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
}

@media (max-width: 768px) {
    .syncflow-pricing {
        padding: 3rem 0;
    }
}
</style>
