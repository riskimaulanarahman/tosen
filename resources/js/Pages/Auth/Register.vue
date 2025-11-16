<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import Input from "@/Components/ui/Input.vue";
import Button from "@/Components/ui/Button.vue";
import { getAppName } from "@/utils/appInfo";

declare function route(name: string, params?: Record<string, any>): string;

defineProps<{
    canLogin: boolean;
}>();

const appName = getAppName();
const pageTitle = "Daftar";
const layoutTitle = `Buat Akun ${appName}`;
const layoutDescription = `Satukan absensi, shift, dan payroll dalam satu dashboard ${appName}.`;
const termsCopy = `Dengan mendaftar, Anda menyetujui kebijakan privasi dan ketentuan layanan ${appName}.`;

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head :title="pageTitle" />

    <AuthLayout :title="layoutTitle" :description="layoutDescription">
        <form @submit.prevent="submit" class="space-y-6">
            <Input
                id="name"
                v-model="form.name"
                label="Nama Lengkap"
                placeholder="Masukkan nama lengkap Anda"
                :error="form.errors.name"
                required
                autofocus
                autocomplete="name"
            />

            <Input
                id="email"
                v-model="form.email"
                type="email"
                label="Email"
                placeholder="contoh@perusahaan.com"
                :error="form.errors.email"
                required
                autocomplete="username"
            />

            <Input
                id="password"
                v-model="form.password"
                type="password"
                label="Password"
                placeholder="Buat password yang aman"
                :error="form.errors.password"
                show-password-toggle
                required
                autocomplete="new-password"
            />

            <Input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                label="Konfirmasi Password"
                placeholder="Ulangi password Anda"
                :error="form.errors.password_confirmation"
                show-password-toggle
                required
                autocomplete="new-password"
            />

            <Button
                type="submit"
                variant="primary"
                size="lg"
                class="w-full justify-center"
                :disabled="form.processing"
            >
                <span v-if="form.processing">Membuat akun...</span>
                <span v-else>Aktifkan akses gratis</span>
            </Button>
        </form>

        <template #footer>
            <div class="space-y-4 text-center">
                <div class="text-text-2 text-sm">
                    Sudah punya akun?
                    <Link
                        :href="route('login')"
                        class="ml-1 font-medium text-primary-600 transition duration-fast hover:text-primary-500"
                    >
                        Masuk di sini
                    </Link>
                </div>
                <p class="text-xs text-text-3">
                    {{ termsCopy }}
                </p>
            </div>
        </template>
    </AuthLayout>
</template>
