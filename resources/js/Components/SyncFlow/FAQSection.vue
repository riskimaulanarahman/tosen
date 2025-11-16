<script setup lang="ts">
import { ref } from "vue";

interface FAQ {
    id: number;
    category: string;
    question: string;
    answer: string;
    relatedQuestions?: number[];
}

const faqs: FAQ[] = [
    {
        id: 1,
        category: "Getting Started",
        question: "Berapa lama waktu yang dibutuhkan untuk setup SyncFlow?",
        answer: "Setup awal SyncFlow hanya membutuhkan waktu 5-10 menit. Anda bisa registrasi, invite tim, dan mulai proyek pertama dalam hari yang sama. Kami juga menyediakan template proyek yang bisa langsung digunakan untuk mempercepat proses.",
        relatedQuestions: [2, 3],
    },
    {
        id: 2,
        category: "Getting Started",
        question: "Apakah SyncFlow cocok untuk industri saya?",
        answer: "SyncFlow dirancang untuk berbagai industri: technology, manufacturing, creative agency, e-commerce, construction, dan lainnya. Dengan 100+ template dan custom workflows, Anda bisa menyesuaikan sesuai kebutuhan spesifik industri Anda.",
        relatedQuestions: [1, 7],
    },
    {
        id: 3,
        category: "Getting Started",
        question: "Apakah ada training untuk tim saya?",
        answer: "Ya, kami menyediakan berbagai opsi training: tutorial interaktif dalam aplikasi, video library, knowledge base, dan untuk paket Enterprise, kami menyediakan on-site training dan dedicated customer success manager.",
        relatedQuestions: [1, 2],
    },
    {
        id: 4,
        category: "Pricing & Billing",
        question: "Apakah saya bisa mencoba SyncFlow gratis?",
        answer: "Ya! Kami menawarkan trial gratis 14 hari tanpa perlu kartu kredit. Anda bisa mengakses semua features Professional plan selama periode trial. Tidak ada komitmen untuk berlangganan setelah trial berakhir.",
        relatedQuestions: [5, 6],
    },
    {
        id: 5,
        category: "Pricing & Billing",
        question: "Metode pembayaran apa saja yang tersedia?",
        answer: "Kami menerima berbagai metode pembayaran: transfer bank, kartu kredit/debit, e-wallet (GoPay, OVO, Dana), dan untuk Enterprise, kami juga menerima pembayaran invoice dengan termin 30 hari.",
        relatedQuestions: [4, 6],
    },
    {
        id: 6,
        category: "Pricing & Billing",
        question: "Apakah saya bisa refund jika tidak puas?",
        answer: "Ya, kami menawarkan 30 hari money-back guarantee. Jika Anda tidak puas dengan SyncFlow untuk alasan apa pun dalam 30 hari pertama, kami akan refund 100% tanpa pertanyaan.",
        relatedQuestions: [4, 5],
    },
    {
        id: 7,
        category: "Features & Functionality",
        question:
            "Apakah SyncFlow bisa integrate dengan tools yang saya gunakan sekarang?",
        answer: "SyncFlow mengintegrasikan dengan 50+ tools populer: Slack, Microsoft Teams, Google Workspace, Jira, GitHub, dan lainnya. Kami juga menyediakan API untuk custom integrasi dan webhooks untuk real-time data sync.",
        relatedQuestions: [2, 8],
    },
    {
        id: 8,
        category: "Features & Functionality",
        question: "Berapa batasan untuk users dan projects?",
        answer: "Batasan tergantung paket Anda: Starter (10 users), Professional (50 users), Enterprise (unlimited users). Semua paket memiliki unlimited projects. Anda bisa upgrade kapan saja sesuai kebutuhan.",
        relatedQuestions: [7, 9],
    },
    {
        id: 9,
        category: "Features & Functionality",
        question: "Apakah data saya aman di SyncFlow?",
        answer: "Keamanan adalah prioritas kami. Kami menggunakan 256-bit encryption, compliance dengan GDPR dan ISO 27001, regular security audits, dan data centers dengan tier-4 security. Data Anda juga di-backup secara real-time ke multiple locations.",
        relatedQuestions: [8, 10],
    },
    {
        id: 10,
        category: "Technical Support",
        question: "Bagaimana cara mendapatkan support jika ada masalah?",
        answer: "Kami menyediakan multi-channel support: email support (semua paket), live chat (Professional+), phone support (Enterprise), dan comprehensive knowledge base 24/7. Response time: 24 jam untuk Starter, 4 jam untuk Professional, 1 jam untuk Enterprise.",
        relatedQuestions: [9, 11],
    },
    {
        id: 11,
        category: "Technical Support",
        question: "Apakah ada mobile app untuk SyncFlow?",
        answer: "Ya, SyncFlow memiliki mobile apps untuk iOS dan Android yang bisa di-download dari App Store dan Google Play. Mobile apps memiliki hampir semua features desktop version dengan offline capability untuk critical tasks.",
        relatedQuestions: [10, 12],
    },
    {
        id: 12,
        category: "Technical Support",
        question: "Bagaimana jika saya ingin custom features?",
        answer: "Untuk Enterprise customers, kami menawarkan custom development services. Tim kami bisa membantu mengembangkan features spesifik sesuai kebutuhan bisnis Anda dengan biaya yang terjangkau.",
        relatedQuestions: [11, 7],
    },
];

const categories = ref([...new Set(faqs.map((faq) => faq.category))]);
const selectedCategory = ref("All");
const expandedFAQs = ref<number[]>([]);
const searchQuery = ref("");

const filteredFAQs = computed(() => {
    let filtered = faqs;

    if (selectedCategory.value !== "All") {
        filtered = filtered.filter(
            (faq) => faq.category === selectedCategory.value
        );
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
            (faq) =>
                faq.question.toLowerCase().includes(query) ||
                faq.answer.toLowerCase().includes(query)
        );
    }

    return filtered;
});

const toggleFAQ = (id: number) => {
    const index = expandedFAQs.value.indexOf(id);
    if (index > -1) {
        expandedFAQs.value.splice(index, 1);
    } else {
        expandedFAQs.value.push(id);
    }
};

const isExpanded = (id: number) => {
    return expandedFAQs.value.includes(id);
};

const expandAll = () => {
    expandedFAQs.value = filteredFAQs.value.map((faq: FAQ) => faq.id);
};

const collapseAll = () => {
    expandedFAQs.value = [];
};

const getCategoryIcon = (category: string) => {
    const icons: { [key: string]: string } = {
        "Getting Started": "M13 10V3L4 14h7v7l9-11h-7z",
        "Pricing & Billing":
            "M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z",
        "Features & Functionality": "M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4",
        "Technical Support":
            "M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z",
    };
    return (
        icons[category] ||
        "M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
    );
};
</script>

<template>
    <section class="syncflow-faq py-20 lg:py-32 bg-white">
        <div class="syncflow-container">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <h2
                    class="syncflow-text-3xl lg:text-4xl font-bold text-syncflow-dark mb-6"
                >
                    Pertanyaan Yang Sering Diajukan
                </h2>
                <p class="syncflow-text-lg text-gray-600 max-w-3xl mx-auto">
                    Temukan jawaban untuk pertanyaan umum tentang SyncFlow.
                    Tidak menemukan yang Anda cari? Hubungi support kami.
                </p>
            </div>

            <!-- Search Bar -->
            <div class="max-w-2xl mx-auto mb-12">
                <div class="relative">
                    <div
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    >
                        <svg
                            class="h-5 w-5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            ></path>
                        </svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari pertanyaan..."
                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-syncflow-primary focus:border-transparent"
                    />
                </div>
            </div>

            <!-- Category Filter -->
            <div class="flex flex-wrap justify-center gap-3 mb-12">
                <button
                    @click="selectedCategory = 'All'"
                    class="px-4 py-2 rounded-full border transition-colors"
                    :class="{
                        'bg-syncflow-primary text-white border-syncflow-primary':
                            selectedCategory === 'All',
                        'bg-white text-gray-700 border-gray-300 hover:border-syncflow-primary':
                            selectedCategory !== 'All',
                    }"
                >
                    All Categories
                </button>
                <button
                    v-for="category in categories"
                    :key="category"
                    @click="selectedCategory = category"
                    class="px-4 py-2 rounded-full border transition-colors"
                    :class="{
                        'bg-syncflow-primary text-white border-syncflow-primary':
                            selectedCategory === category,
                        'bg-white text-gray-700 border-gray-300 hover:border-syncflow-primary':
                            selectedCategory !== category,
                    }"
                >
                    {{ category }}
                </button>
            </div>

            <!-- Expand/Collapse All -->
            <div class="flex justify-center mb-8">
                <button
                    @click="expandAll"
                    class="px-4 py-2 text-syncflow-primary hover:text-syncflow-primary-dark mr-4"
                >
                    Expand All
                </button>
                <button
                    @click="collapseAll"
                    class="px-4 py-2 text-syncflow-primary hover:text-syncflow-primary-dark"
                >
                    Collapse All
                </button>
            </div>

            <!-- FAQ Items -->
            <div class="max-w-4xl mx-auto space-y-4">
                <div
                    v-for="faq in filteredFAQs"
                    :key="faq.id"
                    class="bg-white border border-gray-200 rounded-lg overflow-hidden transition-all duration-300 hover:shadow-md"
                >
                    <button
                        @click="toggleFAQ(faq.id)"
                        class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
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
                                        :d="getCategoryIcon(faq.category)"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-syncflow-dark">
                                    {{ faq.question }}
                                </h3>
                                <span class="text-sm text-gray-500">{{
                                    faq.category
                                }}</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <svg
                                class="w-5 h-5 text-gray-400 transition-transform duration-300"
                                :class="{
                                    'transform rotate-180': isExpanded(faq.id),
                                }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                ></path>
                            </svg>
                        </div>
                    </button>

                    <div
                        class="px-6 pb-4 transition-all duration-300"
                        :class="{
                            'max-h-96 opacity-100': isExpanded(faq.id),
                            'max-h-0 opacity-0 overflow-hidden': !isExpanded(
                                faq.id
                            ),
                        }"
                    >
                        <div class="pt-2 text-gray-600 leading-relaxed">
                            {{ faq.answer }}
                        </div>

                        <!-- Related Questions -->
                        <div
                            v-if="
                                faq.relatedQuestions &&
                                faq.relatedQuestions.length > 0
                            "
                            class="mt-4 pt-4 border-t border-gray-200"
                        >
                            <h4
                                class="text-sm font-semibold text-syncflow-dark mb-2"
                            >
                                Pertanyaan Terkait:
                            </h4>
                            <div class="space-y-1">
                                <button
                                    v-for="relatedId in faq.relatedQuestions"
                                    :key="relatedId"
                                    @click="toggleFAQ(relatedId)"
                                    class="block text-sm text-syncflow-primary hover:text-syncflow-primary-dark text-left"
                                >
                                    →
                                    {{
                                        faqs.find((f) => f.id === relatedId)
                                            ?.question
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Results -->
            <div v-if="filteredFAQs.length === 0" class="text-center py-12">
                <svg
                    class="w-16 h-16 text-gray-300 mx-auto mb-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">
                    Tidak ada pertanyaan ditemukan
                </h3>
                <p class="text-gray-500 mb-4">
                    Coba kata kunci lain atau hubungi support kami
                </p>
                <button class="syncflow-btn-primary">Hubungi Support</button>
            </div>

            <!-- Still Have Questions -->
            <div
                class="mt-16 bg-gradient-to-r from-syncflow-primary to-syncflow-primary-dark rounded-2xl p-8 text-white text-center"
            >
                <h3 class="syncflow-text-2xl font-bold mb-4">
                    Masih Punya Pertanyaan?
                </h3>
                <p class="text-white/90 mb-8 max-w-2xl mx-auto">
                    Tim support kami siap membantu 24/7. Jangan ragu untuk
                    menghubungi kami untuk pertanyaan apa pun tentang SyncFlow.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button class="syncflow-btn-accent syncflow-btn-lg">
                        <svg
                            class="w-5 h-5 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            ></path>
                        </svg>
                        Email Support
                    </button>
                    <button
                        class="syncflow-btn-secondary syncflow-btn-lg bg-white/20 border-white/30 text-white hover:bg-white/30"
                    >
                        <svg
                            class="w-5 h-5 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                            ></path>
                        </svg>
                        Live Chat
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.syncflow-faq {
    position: relative;
}

.syncflow-faq::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
}

@media (max-width: 768px) {
    .syncflow-faq {
        padding: 3rem 0;
    }
}
</style>
