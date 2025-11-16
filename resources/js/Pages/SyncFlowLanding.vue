<script setup lang="ts">
import { ref, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import SyncFlowLayout from "@/Layouts/SyncFlowLayout.vue";
import HeroSection from "@/Components/SyncFlow/HeroSection.vue";
import ProblemSolution from "@/Components/SyncFlow/ProblemSolution.vue";
import FeaturesSection from "@/Components/SyncFlow/FeaturesSection.vue";
import HowItWorks from "@/Components/SyncFlow/HowItWorks.vue";
import TestimonialsSection from "@/Components/SyncFlow/TestimonialsSection.vue";
import PricingSection from "@/Components/SyncFlow/PricingSection.vue";
import FAQSection from "@/Components/SyncFlow/FAQSection.vue";
import CTAFinal from "@/Components/SyncFlow/CTAFinal.vue";
import FooterSection from "@/Components/SyncFlow/FooterSection.vue";

// Form state
const trialForm = ref({
    name: "",
    email: "",
    company: "",
    phone: "",
    employees: "",
    industry: "",
});

const demoForm = ref({
    name: "",
    email: "",
    company: "",
    phone: "",
    employees: "",
    preferred_date: "",
    preferred_time: "",
    notes: "",
});

// Loading states
const isSubmittingTrial = ref(false);
const isSubmittingDemo = ref(false);

// Form validation
const validateTrialForm = () => {
    if (
        !trialForm.value.name ||
        !trialForm.value.email ||
        !trialForm.value.company
    ) {
        return false;
    }
    return true;
};

const validateDemoForm = () => {
    if (
        !demoForm.value.name ||
        !demoForm.value.email ||
        !demoForm.value.company ||
        !demoForm.value.preferred_date
    ) {
        return false;
    }
    return true;
};

// Form submission handlers
const submitTrialForm = async () => {
    if (validateTrialForm()) {
        isSubmittingTrial.value = true;
        try {
            const response = await fetch("/api/syncflow/trial", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content"),
                },
                body: JSON.stringify(trialForm.value),
            });

            if (response.ok) {
                const data = await response.json();
                showSuccessNotification(data.message);
                // Reset form
                trialForm.value = {
                    name: "",
                    email: "",
                    company: "",
                    phone: "",
                    employees: "",
                    industry: "",
                };
            } else {
                showErrorNotification(
                    "Maaf, terjadi kesalahan. Silakan coba lagi."
                );
            }
        } catch (error) {
            showErrorNotification(
                "Maaf, terjadi kesalahan. Silakan coba lagi."
            );
        } finally {
            isSubmittingTrial.value = false;
        }
    }
};

const submitDemoForm = async () => {
    if (validateDemoForm()) {
        isSubmittingDemo.value = true;
        try {
            const response = await fetch("/api/syncflow/demo", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content"),
                },
                body: JSON.stringify(demoForm.value),
            });

            if (response.ok) {
                const data = await response.json();
                showSuccessNotification(data.message);
                // Reset form
                demoForm.value = {
                    name: "",
                    email: "",
                    company: "",
                    phone: "",
                    employees: "",
                    preferred_date: "",
                    preferred_time: "",
                    notes: "",
                };
            } else {
                showErrorNotification(
                    "Maaf, terjadi kesalahan. Silakan coba lagi."
                );
            }
        } catch (error) {
            showErrorNotification(
                "Maaf, terjadi kesalahan. Silakan coba lagi."
            );
        } finally {
            isSubmittingDemo.value = false;
        }
    }
};

// Notification helpers
const showSuccessNotification = (message: string) => {
    // Create success notification element
    const notification = document.createElement("div");
    notification.className =
        "fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-slide-down";
    notification.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
};

const showErrorNotification = (message: string) => {
    // Create error notification element
    const notification = document.createElement("div");
    notification.className =
        "fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-slide-down";
    notification.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L9 10.586 7.707 9.293a1 1 0 001.414-1.414l-1.414 1.414a1 1 0 011.414 0L12.586 10.414a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
            </svg>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
};

// Smooth scroll for anchor links
const scrollToSection = (sectionId: string) => {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: "smooth" });
    }
};

onMounted(() => {
    // Initialize analytics
    if (typeof gtag !== "undefined") {
        gtag("config", "GA_MEASUREMENT_ID", {
            page_title: "SyncFlow Landing Page",
            page_location: window.location.href,
        });
    }
});
</script>

<template>
    <SyncFlowLayout>
        <!-- Hero Section -->
        <HeroSection
            :trial-form="trialForm"
            :demo-form="demoForm"
            :is-submitting-trial="isSubmittingTrial"
            :is-submitting-demo="isSubmittingDemo"
            @submit-trial="submitTrialForm"
            @submit-demo="submitDemoForm"
        />

        <!-- Problem & Solution Section -->
        <ProblemSolution />

        <!-- Features Section -->
        <FeaturesSection />

        <!-- How It Works Section -->
        <HowItWorks />

        <!-- Testimonials Section -->
        <TestimonialsSection />

        <!-- Pricing Section -->
        <PricingSection />

        <!-- FAQ Section -->
        <FAQSection />

        <!-- Final CTA Section -->
        <CTAFinal
            :trial-form="trialForm"
            :demo-form="demoForm"
            :is-submitting-trial="isSubmittingTrial"
            :is-submitting-demo="isSubmittingDemo"
            @submit-trial="submitTrialForm"
            @submit-demo="submitDemoForm"
        />

        <!-- Footer Section -->
        <FooterSection />
    </SyncFlowLayout>
</template>
