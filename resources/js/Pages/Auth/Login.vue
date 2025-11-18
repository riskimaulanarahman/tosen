<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import Input from "@/Components/ui/Input.vue";
import Button from "@/Components/ui/Button.vue";
import { getAppName } from "@/utils/appInfo";

declare function route(name: string, params?: Record<string, any>): string;

defineProps({
    canResetPassword: Boolean,
    status: String,
    message: String,
    messageType: String,
});

const appName = getAppName();
const pageTitle = "Masuk";
const layoutTitle = `Masuk ke ${appName}`;
const layoutDescription = `${appName} membantu memusatkan absensi, shift, dan payroll agar tim Anda bergerak lebih cepat.`;
const submitLabel = `Masuk ke ${appName}`;
const registerPrompt = `Belum punya akun ${appName}?`;

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? "on" : "",
    })).post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head :title="pageTitle" />

    <AuthLayout :title="layoutTitle" :description="layoutDescription">
        <!-- Status Message -->
        <div v-if="status" class="mb-6">
            <div
                class="flex items-center gap-2 rounded-2xl border-l-4 border-primary-500 bg-primary-50 px-4 py-3 text-sm text-primary-800 shadow-sm"
            >
                <svg
                    class="w-5 h-5 text-primary-600"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"
                    />
                </svg>
                {{ status }}
            </div>
        </div>

        <!-- Dynamic Message -->
        <div v-if="message" class="mb-6">
            <div
                :class="[
                    'flex items-center gap-2 px-4 py-3 rounded-2xl text-sm border-l-4 shadow-sm',
                    messageType === 'warning'
                        ? 'bg-warning-50 border-warning-500 text-warning-700'
                        : messageType === 'error'
                        ? 'bg-error-50 border-error-500 text-error-700'
                        : 'bg-primary-50 border-primary-500 text-primary-700',
                ]"
            >
                <svg
                    v-if="messageType === 'warning'"
                    class="w-5 h-5 text-warning-600"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd"
                    />
                </svg>
                <svg
                    v-else-if="messageType === 'error'"
                    class="w-5 h-5 text-error-600"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                    />
                </svg>
                <svg
                    v-else
                    class="w-5 h-5 text-primary-600"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"
                    />
                </svg>
                {{ message }}
            </div>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="submit" class="space-y-6">
            <Input
                id="email"
                type="email"
                v-model="form.email"
                label="Email Perusahaan"
                placeholder="nama@perusahaan.com"
                :error="form.errors.email"
                required
                autofocus
                autocomplete="email"
            />

            <Input
                id="password"
                type="password"
                v-model="form.password"
                label="Password"
                placeholder="Masukkan password Anda"
                :error="form.errors.password"
                show-password-toggle
                required
                autocomplete="current-password"
            />

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input
                        id="remember"
                        type="checkbox"
                        v-model="form.remember"
                        class="w-4 h-4 text-primary-600 border-primary-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                    <label
                        for="remember"
                        class="ml-2 text-sm text-text-2 cursor-pointer"
                    >
                        Ingat saya di perangkat ini
                    </label>
                </div>

                <a
                    href="#"
                    class="text-sm font-medium text-primary-600 hover:text-primary-500 transition-colors duration-fast underline-offset-4 hover:underline"
                >
                    Butuh bantuan masuk?
                </a>
            </div>

            <Button
                type="submit"
                variant="primary"
                size="lg"
                :disabled="form.processing"
                class="w-full justify-center"
            >
                <span v-if="form.processing" class="flex items-center gap-2">
                    <svg
                        class="animate-spin h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    Menghubungkan ke dashboard...
                </span>
                <span v-else class="flex items-center justify-center gap-2">
                    {{ submitLabel }}
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5M8 7l5 5-5 5"
                        ></path>
                    </svg>
                </span>
            </Button>
        </form>

        <template #footer>
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <a
                        href="#"
                        class="flex items-center justify-center gap-2 rounded-2xl border border-border bg-surface-0 px-4 py-4 text-text transition-all duration-fast hover:border-primary-200 hover:text-primary-600"
                    >
                        <svg
                            class="w-5 h-5 text-primary-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 0v4m0 4h8m0 0v-4m0 4l-8-4m8 0l-8 4M8 7v8m0 0l8-4m-8 4v8"
                            ></path>
                        </svg>
                        <span class="text-sm font-medium"
                            >Masuk via SSO Internal</span
                        >
                    </a>
                    <a
                        href="#"
                        class="flex items-center justify-center gap-2 rounded-2xl border border-border bg-surface-0 px-4 py-4 text-text transition-all duration-fast hover:border-accent hover:text-accent"
                    >
                        <svg
                            class="w-5 h-5 text-accent"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 012 2v-2a2 2 0 01-2-2H6a2 2 0 00-2 2v2m8 0V9a2 2 0 012-2h2a2 2 0 012 2v2z"
                            ></path>
                        </svg>
                        <span class="text-sm font-medium"
                            >Hubungi Dukungan</span
                        >
                    </a>
                </div>

                <div class="text-center">
                    <Link
                        :href="route('verification.otp.notice')"
                        class="text-primary-600 hover:text-primary-500 transition-colors duration-fast text-sm font-medium inline-flex items-center justify-center gap-2"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            ></path>
                        </svg>
                        Belum menerima email verifikasi?
                    </Link>
                </div>

                <div v-if="canResetPassword" class="text-center">
                    <Link
                        :href="route('password.request')"
                        class="text-primary-600 hover:text-primary-500 transition-colors duration-fast text-sm font-medium inline-flex items-center justify-center gap-2"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                            ></path>
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"
                            ></path>
                        </svg>
                        Lupa password?
                    </Link>
                </div>

                <div
                    class="text-center bg-surface-0 rounded-2xl p-6 border border-border shadow-sm"
                >
                    <p class="text-text-2 text-sm mb-3">
                        {{ registerPrompt }}
                    </p>
                    <Link :href="route('register')" class="inline-flex w-full">
                        <Button
                            variant="accent"
                            size="md"
                            class="w-full justify-center"
                        >
                            Daftar gratis sekarang
                        </Button>
                    </Link>
                </div>
            </div>
        </template>
    </AuthLayout>
</template>
