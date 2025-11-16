<script setup lang="ts">
import { ref } from "vue";

const currentYear = new Date().getFullYear();

const footerLinks = {
    product: [
        { name: "Features", href: "#features" },
        { name: "Pricing", href: "#pricing" },
        { name: "Integrations", href: "#integrations" },
        { name: "API Documentation", href: "#api" },
        { name: "What's New", href: "#whats-new" },
    ],
    company: [
        { name: "About Us", href: "#about" },
        { name: "Careers", href: "#careers" },
        { name: "Press", href: "#press" },
        { name: "Partners", href: "#partners" },
        { name: "Contact", href: "#contact" },
    ],
    resources: [
        { name: "Blog", href: "#blog" },
        { name: "Help Center", href: "#help" },
        { name: "Community", href: "#community" },
        { name: "Webinars", href: "#webinars" },
        { name: "Case Studies", href: "#case-studies" },
    ],
    legal: [
        { name: "Privacy Policy", href: "#privacy" },
        { name: "Terms of Service", href: "#terms" },
        { name: "Cookie Policy", href: "#cookies" },
        { name: "GDPR Compliance", href: "#gdpr" },
        { name: "Security", href: "#security" },
    ],
};

const socialLinks = [
    {
        name: "Facebook",
        icon: "M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z",
        href: "#",
    },
    {
        name: "Twitter",
        icon: "M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z",
        href: "#",
    },
    {
        name: "LinkedIn",
        icon: "M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z",
        href: "#",
    },
    {
        name: "Instagram",
        icon: "M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1112.324 0 6.162 6.162 0 01-12.324 0zM12 16a4 4 0 110-8 4 4 0 010 8zm4.965-10.405a1.44 1.44 0 112.881.001 1.44 1.44 0 01-2.881-.001z",
        href: "#",
    },
];

const newsletterEmail = ref("");

const subscribeNewsletter = () => {
    if (newsletterEmail.value) {
        // Handle newsletter subscription
        console.log("Subscribing to newsletter:", newsletterEmail.value);
        newsletterEmail.value = "";
    }
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
};
</script>

<template>
    <footer class="syncflow-footer bg-gray-900 text-white">
        <!-- Main Footer Content -->
        <div class="syncflow-container py-16">
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 mb-12"
            >
                <!-- Company Info -->
                <div class="lg:col-span-2">
                    <div class="mb-6">
                        <h3 class="syncflow-text-2xl font-bold text-white mb-2">
                            SyncFlow
                        </h3>
                        <p class="text-gray-400 mb-4">
                            Platform manajemen proyek yang membantu bisnis
                            Indonesia meningkatkan produktivitas tim hingga 40%
                            dengan kolaborasi real-time dan otomasi pelaporan.
                        </p>
                    </div>

                    <!-- Newsletter -->
                    <div class="mb-6">
                        <h4 class="font-semibold text-white mb-3">
                            Subscribe Newsletter
                        </h4>
                        <p class="text-gray-400 text-sm mb-3">
                            Dapatkan tips, insights, dan update terbaru tentang
                            manajemen proyek
                        </p>
                        <form
                            @submit.prevent="subscribeNewsletter"
                            class="flex flex-col sm:flex-row gap-2"
                        >
                            <input
                                v-model="newsletterEmail"
                                type="email"
                                placeholder="Email Anda"
                                class="flex-1 px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-syncflow-primary"
                                required
                            />
                            <button
                                type="submit"
                                class="px-4 py-2 bg-syncflow-primary text-white rounded-lg hover:bg-syncflow-primary-dark transition-colors"
                            >
                                Subscribe
                            </button>
                        </form>
                    </div>

                    <!-- Social Links -->
                    <div>
                        <h4 class="font-semibold text-white mb-3">Follow Us</h4>
                        <div class="flex space-x-4">
                            <a
                                v-for="social in socialLinks"
                                :key="social.name"
                                :href="social.href"
                                :aria-label="social.name"
                                class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-syncflow-primary transition-colors"
                            >
                                <svg
                                    class="w-5 h-5 text-white"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path :d="social.icon"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Product Links -->
                <div>
                    <h4 class="font-semibold text-white mb-4">Product</h4>
                    <ul class="space-y-2">
                        <li
                            v-for="link in footerLinks.product"
                            :key="link.name"
                        >
                            <a
                                :href="link.href"
                                class="text-gray-400 hover:text-white transition-colors"
                            >
                                {{ link.name }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Company Links -->
                <div>
                    <h4 class="font-semibold text-white mb-4">Company</h4>
                    <ul class="space-y-2">
                        <li
                            v-for="link in footerLinks.company"
                            :key="link.name"
                        >
                            <a
                                :href="link.href"
                                class="text-gray-400 hover:text-white transition-colors"
                            >
                                {{ link.name }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Resources Links -->
                <div>
                    <h4 class="font-semibold text-white mb-4">Resources</h4>
                    <ul class="space-y-2">
                        <li
                            v-for="link in footerLinks.resources"
                            :key="link.name"
                        >
                            <a
                                :href="link.href"
                                class="text-gray-400 hover:text-white transition-colors"
                            >
                                {{ link.name }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Legal Links -->
            <div class="border-t border-gray-800 pt-8 mb-8">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <a
                        v-for="link in footerLinks.legal"
                        :key="link.name"
                        :href="link.href"
                        class="text-gray-400 hover:text-white transition-colors text-sm"
                    >
                        {{ link.name }}
                    </a>
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="border-t border-gray-800 pt-8">
                <div
                    class="flex flex-col md:flex-row justify-between items-center"
                >
                    <div class="text-gray-400 text-sm mb-4 md:mb-0">
                        © {{ currentYear }} SyncFlow. All rights reserved.
                    </div>
                    <div class="flex items-center space-x-6 text-sm">
                        <div class="flex items-center space-x-2">
                            <span class="text-gray-400">Made with</span>
                            <span class="text-red-500">❤️</span>
                            <span class="text-gray-400">in Indonesia</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg
                                class="w-4 h-4 text-green-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                            <span class="text-gray-400"
                                >ISO 27001 Certified</span
                            >
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg
                                class="w-4 h-4 text-blue-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                            <span class="text-gray-400">GDPR Compliant</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back to Top Button -->
        <button
            @click="scrollToTop"
            class="fixed bottom-8 right-8 w-12 h-12 bg-syncflow-primary text-white rounded-full shadow-lg hover:bg-syncflow-primary-dark transition-all duration-300 flex items-center justify-center z-50"
            aria-label="Back to top"
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
                    d="M5 10l7-7m0 0l7 7m-7-7v18"
                ></path>
            </svg>
        </button>
    </footer>
</template>

<style scoped>
.syncflow-footer {
    position: relative;
}

.syncflow-footer::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #374151, transparent);
}

@media (max-width: 768px) {
    .syncflow-footer {
        padding: 3rem 0;
    }
}
</style>
