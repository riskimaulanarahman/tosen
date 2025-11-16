<script setup lang="ts">
import { ref } from "vue";

interface Testimonial {
    id: number;
    name: string;
    position: string;
    company: string;
    industry: string;
    avatar: string;
    content: string;
    rating: number;
    results: {
        metric: string;
        value: string;
    }[];
}

const testimonials: Testimonial[] = [
    {
        id: 1,
        name: "Andi Pratama",
        position: "Project Manager",
        company: "PT. Teknologi Maju",
        industry: "Technology",
        avatar: "/images/testimonials/andi-pratama.jpg",
        content:
            "SyncFlow mengubah cara tim kami bekerja. Produktivitas meningkat 40% dan meeting status berkurang drastis. Dashboard analytics-nya sangat membantu untuk pengambilan keputusan cepat.",
        rating: 5,
        results: [
            { metric: "Produktivitas Tim", value: "+40%" },
            { metric: "Meeting Status", value: "-60%" },
            { metric: "Project On-Time", value: "95%" },
        ],
    },
    {
        id: 2,
        name: "Sarah Wijaya",
        position: "CEO",
        company: "Creative Agency Indonesia",
        industry: "Creative Agency",
        avatar: "/images/testimonials/sarah-wijaya.jpg",
        content:
            "Sebagai agency dengan banyak proyek simultan, SyncFlow membantu kami tetap terorganisir. Client visibility jauh lebih baik dan reporting menjadi otomatis.",
        rating: 5,
        results: [
            { metric: "Client Satisfaction", value: "+35%" },
            { metric: "Reporting Time", value: "-80%" },
            { metric: "Project Capacity", value: "+50%" },
        ],
    },
    {
        id: 3,
        name: "Budi Santoso",
        position: "Operations Director",
        company: "PT. Manufaktur Sukses",
        industry: "Manufacturing",
        avatar: "/images/testimonials/budi-santoso.jpg",
        content:
            "Implementasi SyncFlow sangat mudah dan tim kami adaptasi dengan cepat. Automasi workflow menghemat berjam-jam kerja manual setiap minggunya.",
        rating: 5,
        results: [
            { metric: "Manual Tasks", value: "-70%" },
            { metric: "Process Efficiency", value: "+45%" },
            { metric: "Error Reduction", value: "85%" },
        ],
    },
    {
        id: 4,
        name: "Maya Putri",
        position: "CTO",
        company: "StartupKu",
        industry: "Startup",
        avatar: "/images/testimonials/maya-putri.jpg",
        content:
            "SyncFlow memberikan kami enterprise-grade features dengan harga yang terjangkau. Integration dengan tools existing sangat seamless dan support team-nya luar biasa.",
        rating: 5,
        results: [
            { metric: "Development Speed", value: "+30%" },
            { metric: "Team Collaboration", value: "+60%" },
            { metric: "Time to Market", value: "-25%" },
        ],
    },
    {
        id: 5,
        name: "Hendra Kusuma",
        position: "Business Owner",
        company: "PT. Konstruksi Mandiri",
        industry: "Construction",
        avatar: "/images/testimonials/hendra-kusuma.jpg",
        content:
            "SyncFlow membantu kami mengelola multiple construction sites dengan efisien. Real-time tracking dan resource management sangat valuable untuk bisnis kami.",
        rating: 5,
        results: [
            { metric: "Site Efficiency", value: "+35%" },
            { metric: "Resource Utilization", value: "+25%" },
            { metric: "Cost Savings", value: "20%" },
        ],
    },
    {
        id: 6,
        name: "Lisa Permata",
        position: "Marketing Director",
        company: "E-Commerce Indonesia",
        industry: "E-commerce",
        avatar: "/images/testimonials/lisa-permata.jpg",
        content:
            "Campaign management jadi jauh lebih terstruktur dengan SyncFlow. Tim marketing bisa collaborate secara real-time dan hasil campaign lebih terukur.",
        rating: 5,
        results: [
            { metric: "Campaign ROI", value: "+50%" },
            { metric: "Team Productivity", value: "+40%" },
            { metric: "Time to Launch", value: "-30%" },
        ],
    },
];

const activeTestimonial = ref(0);
const autoplayInterval = ref<number | null>(null);

const setActiveTestimonial = (index: number) => {
    activeTestimonial.value = index;
    resetAutoplay();
};

const nextTestimonial = () => {
    activeTestimonial.value =
        (activeTestimonial.value + 1) % testimonials.length;
};

const prevTestimonial = () => {
    activeTestimonial.value =
        activeTestimonial.value === 0
            ? testimonials.length - 1
            : activeTestimonial.value - 1;
};

const startAutoplay = () => {
    autoplayInterval.value = setInterval(nextTestimonial, 5000);
};

const stopAutoplay = () => {
    if (autoplayInterval.value) {
        clearInterval(autoplayInterval.value);
        autoplayInterval.value = null;
    }
};

const resetAutoplay = () => {
    stopAutoplay();
    startAutoplay();
};

// Start autoplay on mount
onMounted(() => {
    startAutoplay();
});

// Cleanup on unmount
onUnmounted(() => {
    stopAutoplay();
});

const renderStars = (rating: number) => {
    return Array(5)
        .fill(0)
        .map((_, index) => (index < rating ? "filled" : "empty"));
};
</script>

<template>
    <section class="syncflow-testimonials py-20 lg:py-32 bg-white">
        <div class="syncflow-container">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <h2
                    class="syncflow-text-3xl lg:text-4xl font-bold text-syncflow-dark mb-6"
                >
                    Dipercaya oleh 500+ Bisnis di Indonesia
                </h2>
                <p class="syncflow-text-lg text-gray-600 max-w-3xl mx-auto">
                    Lihat bagaimana bisnis seperti Anda mengalami transformasi
                    dengan SyncFlow
                </p>
            </div>

            <!-- Main Testimonial Display -->
            <div class="mb-16">
                <div
                    class="relative bg-gradient-to-br from-syncflow-primary to-syncflow-primary-dark rounded-2xl p-8 lg:p-12 text-white"
                >
                    <!-- Navigation Arrows -->
                    <button
                        @click="prevTestimonial"
                        class="absolute left-4 top-1/2 transform -translate-y-1/2 w-10 h-10 bg-white/20 rounded-full flex items-center justify-center hover:bg-white/30 transition-colors"
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
                    </button>
                    <button
                        @click="nextTestimonial"
                        class="absolute right-4 top-1/2 transform -translate-y-1/2 w-10 h-10 bg-white/20 rounded-full flex items-center justify-center hover:bg-white/30 transition-colors"
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
                                d="M9 5l7 7-7 7"
                            ></path>
                        </svg>
                    </button>

                    <div class="grid lg:grid-cols-2 gap-12 items-center">
                        <!-- Testimonial Content -->
                        <div>
                            <!-- Rating -->
                            <div class="flex items-center space-x-1 mb-6">
                                <svg
                                    v-for="(star, index) in renderStars(
                                        testimonials[activeTestimonial].rating
                                    )"
                                    :key="index"
                                    class="w-6 h-6"
                                    :class="
                                        star === 'filled'
                                            ? 'text-yellow-400'
                                            : 'text-white/30'
                                    "
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                    ></path>
                                </svg>
                            </div>

                            <!-- Quote -->
                            <blockquote
                                class="syncflow-text-xl lg:text-2xl font-medium mb-8 leading-relaxed"
                            >
                                "{{ testimonials[activeTestimonial].content }}"
                            </blockquote>

                            <!-- Author Info -->
                            <div class="flex items-center space-x-4 mb-8">
                                <img
                                    :src="
                                        testimonials[activeTestimonial].avatar
                                    "
                                    :alt="testimonials[activeTestimonial].name"
                                    class="w-16 h-16 rounded-full border-2 border-white/50"
                                    loading="lazy"
                                />
                                <div>
                                    <div class="font-semibold text-lg">
                                        {{
                                            testimonials[activeTestimonial].name
                                        }}
                                    </div>
                                    <div class="text-white/80">
                                        {{
                                            testimonials[activeTestimonial]
                                                .position
                                        }}
                                    </div>
                                    <div class="text-white/60">
                                        {{
                                            testimonials[activeTestimonial]
                                                .company
                                        }}
                                    </div>
                                </div>
                            </div>

                            <!-- Results -->
                            <div class="grid grid-cols-3 gap-4">
                                <div
                                    v-for="(result, index) in testimonials[
                                        activeTestimonial
                                    ].results"
                                    :key="index"
                                    class="text-center"
                                >
                                    <div
                                        class="syncflow-text-2xl font-bold text-syncflow-accent"
                                    >
                                        {{ result.value }}
                                    </div>
                                    <div class="text-sm text-white/80">
                                        {{ result.metric }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Company Logo/Visual -->
                        <div class="flex items-center justify-center">
                            <div
                                class="bg-white/10 backdrop-blur-sm rounded-xl p-8"
                            >
                                <div
                                    class="w-48 h-48 bg-white/20 rounded-lg flex items-center justify-center"
                                >
                                    <span
                                        class="text-white/60 text-lg font-semibold"
                                        >{{
                                            testimonials[activeTestimonial]
                                                .company
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testimonial Thumbnails -->
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-16">
                <div
                    v-for="(testimonial, index) in testimonials"
                    :key="testimonial.id"
                    class="relative cursor-pointer group"
                    @click="setActiveTestimonial(index)"
                >
                    <div
                        class="aspect-w-1 aspect-h-1 rounded-lg overflow-hidden border-2 transition-all duration-300"
                        :class="{
                            'border-syncflow-primary ring-2 ring-syncflow-primary/50':
                                activeTestimonial === index,
                            'border-gray-200 hover:border-syncflow-primary/50':
                                activeTestimonial !== index,
                        }"
                    >
                        <img
                            :src="testimonial.avatar"
                            :alt="testimonial.name"
                            class="w-full h-full object-cover"
                            loading="lazy"
                        />
                    </div>
                    <div
                        class="absolute inset-0 bg-syncflow-primary/20 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity"
                    ></div>
                </div>
            </div>

            <!-- Industry Stats -->
            <div class="bg-gray-50 rounded-2xl p-8">
                <h3
                    class="syncflow-text-2xl font-bold text-center text-syncflow-dark mb-8"
                >
                    Hasil Nyata Berdasarkan Industri
                </h3>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div
                            class="text-3xl font-bold text-syncflow-primary mb-2"
                        >
                            Technology
                        </div>
                        <div class="text-gray-600 mb-2">
                            45% Productivity Increase
                        </div>
                        <div class="text-sm text-gray-500">120+ Companies</div>
                    </div>
                    <div class="text-center">
                        <div
                            class="text-3xl font-bold text-syncflow-primary mb-2"
                        >
                            Manufacturing
                        </div>
                        <div class="text-gray-600 mb-2">35% Cost Reduction</div>
                        <div class="text-sm text-gray-500">85+ Companies</div>
                    </div>
                    <div class="text-center">
                        <div
                            class="text-3xl font-bold text-syncflow-primary mb-2"
                        >
                            Creative Agency
                        </div>
                        <div class="text-gray-600 mb-2">
                            50% Client Satisfaction
                        </div>
                        <div class="text-sm text-gray-500">95+ Companies</div>
                    </div>
                    <div class="text-center">
                        <div
                            class="text-3xl font-bold text-syncflow-primary mb-2"
                        >
                            E-commerce
                        </div>
                        <div class="text-gray-600 mb-2">
                            40% Faster Time to Market
                        </div>
                        <div class="text-sm text-gray-500">60+ Companies</div>
                    </div>
                </div>
            </div>

            <!-- Trust Badges -->
            <div class="mt-16 text-center">
                <div class="syncflow-text-lg text-gray-600 mb-8">
                    Dipercaya oleh leading brands di Indonesia
                </div>
                <div
                    class="flex flex-wrap justify-center items-center gap-8 opacity-60"
                >
                    <!-- Company logos would go here -->
                    <div
                        class="w-32 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-500"
                    >
                        Telkom Indonesia
                    </div>
                    <div
                        class="w-32 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-500"
                    >
                        Bank BCA
                    </div>
                    <div
                        class="w-32 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-500"
                    >
                        Gojek
                    </div>
                    <div
                        class="w-32 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-500"
                    >
                        Tokopedia
                    </div>
                    <div
                        class="w-32 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-500"
                    >
                        Traveloka
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.syncflow-testimonials {
    position: relative;
}

.syncflow-testimonials::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
}

@media (max-width: 768px) {
    .syncflow-testimonials {
        padding: 3rem 0;
    }
}
</style>
