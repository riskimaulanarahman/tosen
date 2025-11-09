<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import Input from "@/Components/ui/Input.vue";
import Button from "@/Components/ui/Button.vue";

defineProps({
    canResetPassword: Boolean,
    status: String,
    message: String,
    messageType: String,
});

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
    <Head title="Masuk" />

    <AuthLayout
        title="Masuk"
        description="Masuk ke akun Anda untuk melanjutkan"
    >
        <!-- Status Message -->
        <div v-if="status" class="mb-6">
            <div
                class="bg-success/10 border border-success/20 text-success px-4 py-3 rounded-lg text-sm"
            >
                {{ status }}
            </div>
        </div>

        <!-- Dynamic Message -->
        <div v-if="message" class="mb-6">
            <div
                :class="[
                    'px-4 py-3 rounded-lg text-sm border',
                    messageType === 'warning'
                        ? 'bg-warning/10 border-warning/30 text-warning'
                        : messageType === 'error'
                        ? 'bg-error/10 border-error/30 text-error'
                        : 'bg-success/10 border-success/20 text-success',
                ]"
            >
                {{ message }}
            </div>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Email Field -->
            <Input
                id="email"
                type="email"
                v-model="form.email"
                label="Email"
                placeholder="Masukkan email Anda"
                :error="form.errors.email"
                required
                autofocus
                autocomplete="email"
            />

            <!-- Password Field -->
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

            <!-- Remember Me -->
            <div class="flex items-center">
                <input
                    id="remember"
                    type="checkbox"
                    v-model="form.remember"
                    class="w-4 h-4 text-primary bg-surface-2 border-border rounded focus:ring-2 focus:ring-primary-400 focus:border-transparent"
                />
                <label for="remember" class="ml-2 block text-sm text-text">
                    Ingat saya
                </label>
            </div>

            <!-- Submit Button -->
            <Button
                type="submit"
                variant="primary"
                size="lg"
                :disabled="form.processing"
                class="w-full"
            >
                <span v-if="form.processing">Sedang masuk...</span>
                <span v-else>Masuk</span>
            </Button>
        </form>

        <!-- Footer Links -->
        <template #footer>
            <div class="space-y-4">
                <!-- Email Verification Link -->
                <div class="text-center">
                    <Link
                        :href="route('verification.notice')"
                        class="text-text-2 hover:text-accent transition-colors duration-fast text-sm"
                    >
                        Need to verify your email?
                    </Link>
                </div>

                <!-- Forgot Password -->
                <div v-if="canResetPassword" class="text-center">
                    <Link
                        :href="route('password.request')"
                        class="text-text-2 hover:text-accent transition-colors duration-fast text-sm"
                    >
                        Lupa password?
                    </Link>
                </div>

                <!-- Register Link -->
                <div class="text-center">
                    <span class="text-text-2 text-sm"> Belum punya akun? </span>
                    <Link
                        :href="route('register')"
                        class="text-accent hover:text-primary-400 font-medium transition-colors duration-fast text-sm ml-1"
                    >
                        Daftar sekarang
                    </Link>
                </div>
            </div>
        </template>
    </AuthLayout>
</template>
