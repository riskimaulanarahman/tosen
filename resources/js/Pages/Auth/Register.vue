<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import Input from "@/Components/ui/Input.vue";
import Button from "@/Components/ui/Button.vue";

defineProps<{
    canLogin: boolean;
}>();

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
    <Head title="Daftar" />

    <AuthLayout
        title="Buat Akun Owner"
        description="Mulai kelola outlet dan tim Anda dalam satu dashboard terpusat"
    >
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
                class="w-full"
                :disabled="form.processing"
            >
                <span v-if="form.processing">Membuat akun...</span>
                <span v-else>Daftar Sekarang</span>
            </Button>
        </form>

        <template #footer>
            <div class="space-y-4 text-center">
                <div class="text-text-2 text-sm">
                    Sudah punya akun?
                    <Link
                        :href="route('login')"
                        class="ml-1 font-medium text-accent transition duration-fast hover:text-primary-400"
                    >
                        Masuk di sini
                    </Link>
                </div>
                <p class="text-xs text-text-muted">
                    Dengan mendaftar, Anda menyetujui kebijakan privasi dan
                    ketentuan layanan Absensi.
                </p>
            </div>
        </template>
    </AuthLayout>
</template>
