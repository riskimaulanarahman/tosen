<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";

const page = usePage();
const props = page.props;

const email = ref(props.email || "");
const otp = ref("");
const newPassword = ref("");
const confirmPassword = ref("");
const isOtpSent = ref(!!props.email); // Auto-set to true if email is provided
const isLoading = ref(false);
const message = ref(props.message || ""); // Show message from controller
const messageType = ref(props.messageType || (props.message ? "success" : ""));
const otpInfo = ref(props.otpInfo || null);
const cooldownSeconds = ref(0);
let countdownInterval = null;

onMounted(() => {
    // Show success message if coming from registration
    if (props.message) {
        messageType.value = "success";
        // Auto-hide message after 5 seconds
        setTimeout(() => {
            message.value = "";
        }, 5000);
    }
});

const sendOtp = async () => {
    if (!email.value) {
        message.value = "Please enter your email address";
        messageType.value = "error";
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.post(route("verification.send"), {
            email: email.value,
        });

        isOtpSent.value = true;
        message.value = "OTP sent to your email address";
        messageType.value = "success";
    } catch (error) {
        if (error.response && error.response.data) {
            if (error.response.data.cooldown) {
                // Start countdown for cooldown
                cooldownSeconds.value = error.response.data.remainingSeconds;
                startCountdown();
                message.value = `Please wait ${error.response.data.remainingSeconds} seconds before requesting a new OTP`;
            } else {
                message.value =
                    error.response.data.message || "Failed to send OTP";
            }
        } else {
            message.value = "Network error. Please try again.";
        }
        messageType.value = "error";
    } finally {
        isLoading.value = false;
    }
};

const resendOtp = async () => {
    isLoading.value = true;
    try {
        const response = await axios.post(route("verification.send"), {
            email: email.value,
        });

        if (response.data.cooldown) {
            // Start countdown
            cooldownSeconds.value = response.data.remainingSeconds;
            startCountdown();
            message.value = `Please wait ${response.data.remainingSeconds} seconds before requesting a new OTP`;
        } else {
            message.value = "New OTP sent to your email address";
            messageType.value = "success";
            // Refresh OTP info
            window.location.reload();
        }
    } catch (error) {
        if (error.response && error.response.data) {
            message.value =
                error.response.data.message || "Failed to resend OTP";
        } else {
            message.value = "Network error. Please try again.";
        }
        messageType.value = "error";
    } finally {
        isLoading.value = false;
    }
};

const startCountdown = () => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

    countdownInterval = setInterval(() => {
        if (cooldownSeconds.value > 0) {
            cooldownSeconds.value--;
        } else {
            clearInterval(countdownInterval);
        }
    }, 1000);
};

const verifyOtp = async () => {
    if (!email.value || !otp.value || !newPassword.value) {
        message.value = "Please fill all fields";
        messageType.value = "error";
        return;
    }

    if (newPassword.value !== confirmPassword.value) {
        message.value = "Passwords do not match";
        messageType.value = "error";
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.post(route("verification.verify"), {
            email: email.value,
            otp: otp.value,
            password: newPassword.value,
            password_confirmation: confirmPassword.value,
        });

        message.value = "Email verified successfully! Redirecting...";
        messageType.value = "success";
        setTimeout(() => {
            router.visit(route("dashboard"), {
                method: "get",
                preserveState: false,
                onSuccess: () => {
                    console.log("Successfully redirected to dashboard");
                },
                onError: (errors) => {
                    console.error("Redirect failed:", errors);
                    message.value = "Redirect failed. Please try again.";
                    messageType.value = "error";
                },
            });
        }, 1500);
    } catch (error) {
        if (error.response && error.response.data) {
            if (error.response.data.errors) {
                // Handle validation errors
                const errors = error.response.data.errors;
                let errorMessage = "Validation errors:";

                if (errors.otp) errorMessage += ` OTP: ${errors.otp[0]}`;
                if (errors.email) errorMessage += ` Email: ${errors.email[0]}`;
                if (errors.password)
                    errorMessage += ` Password: ${errors.password[0]}`;

                message.value = errorMessage;
            } else {
                message.value = error.response.data.message || "Invalid OTP";
            }
        } else {
            message.value = "Network error. Please try again.";
        }
        messageType.value = "error";
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Head title="Email Verification" />

    <div
        class="min-h-screen bg-canvas flex items-center justify-center px-4 text-text"
    >
        <div
            class="max-w-md w-full rounded-2xl border border-border/80 bg-surface-1 p-8 shadow-xl"
        >
            <!-- Header -->
            <div class="text-center mb-8">
                <h1
                    class="text-3xl font-bold text-text mb-2"
                    style="font-family: 'Oswald', sans-serif"
                >
                    Email Verification
                </h1>
                <p class="text-text-2 mb-4">
                    Complete your account setup to access the system
                </p>

                <!-- Information Box -->
                <div
                    class="bg-info/10 border border-info/30 rounded-lg p-4 mb-6"
                >
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <svg
                                class="w-5 h-5 text-info mt-0.5"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="text-left">
                            <h3 class="font-semibold text-text mb-1">
                                First Time Login?
                            </h3>
                            <p class="text-text-2 text-sm">
                                If you were just added as an employee, you need
                                to verify your email and set your password
                                before logging in.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Display -->
            <div
                v-if="message"
                :class="[
                    'p-3 mb-4 rounded-lg border text-sm',
                    messageType === 'success'
                        ? 'bg-success/10 border-success/30 text-success'
                        : 'bg-error/10 border-error/30 text-error',
                ]"
            >
                {{ message }}
            </div>

            <!-- Step 1: Email Input -->
            <div v-if="!isOtpSent">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-text mb-2">
                        Email Address
                    </label>
                    <input
                        v-model="email"
                        type="email"
                        class="w-full rounded-lg border border-border bg-surface-2 px-3 py-2 text-text placeholder-text-muted transition duration-fast focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-400"
                        placeholder="Enter your email address"
                        required
                    />
                </div>

                <button
                    @click="sendOtp"
                    :disabled="isLoading || !email"
                    class="w-full rounded-lg bg-primary px-4 py-2 font-semibold text-white transition duration-fast hover:bg-primary-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface-1 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span v-if="isLoading">Sending...</span>
                    <span v-else>Send OTP</span>
                </button>
            </div>

            <!-- Step 2: OTP and Password -->
            <div v-else>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-text mb-2">
                        Email Address
                    </label>
                    <input
                        v-model="email"
                        type="email"
                        class="w-full rounded-lg border border-border bg-surface-2 px-3 py-2 text-text placeholder-text-muted transition duration-fast focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-400"
                        placeholder="Enter your email address"
                        required
                    />
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-text mb-2">
                        OTP Code
                    </label>
                    <input
                        v-model="otp"
                        type="text"
                        maxlength="6"
                        class="w-full rounded-lg border border-border bg-surface-2 px-3 py-2 text-center text-lg font-mono tracking-[0.35em] text-text transition duration-fast focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-400"
                        placeholder="000000"
                        required
                    />
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-text mb-2">
                        New Password
                    </label>
                    <input
                        v-model="newPassword"
                        type="password"
                        class="w-full rounded-lg border border-border bg-surface-2 px-3 py-2 text-text placeholder-text-muted transition duration-fast focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-400"
                        placeholder="Enter your new password"
                        required
                    />
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-text mb-2">
                        Confirm Password
                    </label>
                    <input
                        v-model="confirmPassword"
                        type="password"
                        class="w-full rounded-lg border border-border bg-surface-2 px-3 py-2 text-text placeholder-text-muted transition duration-fast focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-400"
                        placeholder="Confirm your new password"
                        required
                    />
                </div>

                <div class="space-y-3">
                    <button
                        @click="verifyOtp"
                        :disabled="isLoading || !email || !otp || !newPassword"
                        class="w-full rounded-lg bg-primary px-4 py-2 font-semibold text-white transition duration-fast hover:bg-primary-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface-1 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span v-if="isLoading">Verifying...</span>
                        <span v-else>Verify Email & Set Password</span>
                    </button>

                    <button
                        @click="
                            () => {
                                isOtpSent = false;
                                message = '';
                            }
                        "
                        class="w-full rounded-lg bg-surface-2 px-4 py-2 font-semibold text-text transition duration-fast hover:bg-surface-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface-1"
                    >
                        Back to Email
                    </button>
                </div>
            </div>

            <!-- OTP Status Information -->
            <div v-if="otpInfo && otpInfo.exists" class="mt-6">
                <div
                    class="bg-success/10 border border-success/30 rounded-lg p-4"
                >
                    <div class="flex items-center justify-center space-x-2">
                        <svg
                            class="w-5 h-5 text-success"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <p class="text-success text-sm font-medium">
                            OTP already sent! Expires at
                            {{ otpInfo.expiresAt }} ({{
                                otpInfo.remainingMinutes
                            }}
                            min remaining)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Resend OTP Section -->
            <div
                v-if="otpInfo && otpInfo.exists && isOtpSent"
                class="mt-4 text-center"
            >
                <button
                    @click="resendOtp"
                    :disabled="cooldownSeconds > 0 || isLoading"
                    class="text-accent hover:text-primary-400 text-sm font-medium transition-colors duration-fast disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="cooldownSeconds > 0">
                        Resend OTP in {{ cooldownSeconds }}s
                    </span>
                    <span v-else> Resend OTP </span>
                </button>
            </div>

            <!-- Help Text -->
            <div class="mt-6 text-center">
                <p class="text-text-2 text-sm">
                    OTP will expire in 10 minutes.
                </p>
            </div>

            <!-- Logout Link -->
            <div class="mt-4 text-center">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-text-2 text-sm underline transition duration-fast hover:text-accent"
                >
                    Cancel and Logout
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap");

h1 {
    font-family: "Oswald", sans-serif;
}

body {
    font-family: "Roboto", sans-serif;
}
</style>
