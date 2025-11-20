<script setup>
import { useForm } from "@inertiajs/vue3";
import { Head, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";

const props = defineProps({
    outlets: Array,
});

const form = useForm({
    name: "",
    email: "",
    outlet_id: "",
    base_salary: "",
});

const submit = () => {
    form.post(route("employees.store"), {
        onSuccess: () => {
            handleSuccess("Karyawan berhasil ditambahkan!");
        },
        onError: (errors) => {
            handleError(errors);
        },
    });
};
</script>

<template>
    <Head title="Create Employee" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Tambah Karyawan Baru
                    </h1>
                    <p class="text-muted">Tambahkan karyawan baru ke sistem</p>
                </div>
                <div class="flex items-center space-x-4">
                    <Link :href="route('employees.index')">
                        <Button variant="secondary">
                            <svg
                                class="w-4 h-4 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m0 0M14.5 4.5L21 6m0 0-4.5-4.5H4a2 2 0 01-4.5 4.5L6 6m0 0 4.5 4.5m0 0 4.5 4.5l1.25-1.25 6 0 6 0 1.25 1.25 7.5 7.5zm-2.5 0A2.5 2.5 0 01-5 0 2.5 2.5 0 7.5 0zm5-8.75A2.5 2.5 0 01-2.5 0 2.5 2.5 0 7.5 0zM19.25 3.5a2.5 2.5 0 01-5 0 2.5 2.5 0 7.5 0zM20.75 8.25A2.5 2.5 0 01-5 0 2.5 2.5 0 7.5 0z"
                                />
                            </svg>
                            Kembali
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Form -->
            <Card>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <!-- Name Field -->
                            <div>
                                <label
                                    class="block text-text-3 text-sm font-medium mb-2"
                                >
                                    Nama Lengkap
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                    placeholder="Masukkan nama karyawan"
                                    required
                                />
                                <div
                                    v-if="form.errors.name"
                                    class="mt-1 text-sm text-error"
                                >
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <!-- Email Field -->
                            <div>
                                <label
                                    class="block text-text-3 text-sm font-medium mb-2"
                                >
                                    Email
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="w-full px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                    placeholder="karyawan@contoh.com"
                                    required
                                />
                                <div
                                    v-if="form.errors.email"
                                    class="mt-1 text-sm text-error"
                                >
                                    {{ form.errors.email }}
                                </div>
                            </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <div>
                            <label class="block text-text-3 text-sm font-medium mb-2">
                                Gaji Pokok (per bulan)
                            </label>
                            <input
                                v-model.number="form.base_salary"
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="Contoh: 2000000"
                            />
                            <div v-if="form.errors.base_salary" class="mt-1 text-sm text-error">
                                {{ form.errors.base_salary }}
                            </div>
                            <p class="text-xs text-muted mt-1">
                                Opsional. Jika kosong, sistem pakai basic rate periode/outlet.
                            </p>
                        </div>

                        <!-- Outlet Assignment -->
                        <div>
                            <label
                                class="block text-text-3 text-sm font-medium mb-2"
                            >
                                    Penempatan Outlet
                                </label>
                                <select
                                    v-model="form.outlet_id"
                                    class="w-full px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                    required
                                >
                                    <option value="">Pilih Outlet</option>
                                    <option
                                        v-for="outlet in outlets"
                                        :key="outlet.id"
                                        :value="outlet.id"
                                    >
                                        {{ outlet.name }} - {{ outlet.address }}
                                    </option>
                                </select>
                                <div
                                    v-if="form.errors.outlet_id"
                                    class="mt-1 text-sm text-error"
                                >
                                    {{ form.errors.outlet_id }}
                                </div>
                            </div>

                            <!-- Information Card -->
                            <div
                                class="bg-surface-1 border border-border rounded-lg p-4"
                            >
                                <h3
                                    class="text-lg font-semibold text-text mb-3"
                                >
                                    Informasi Penting
                                </h3>
                                <ul class="space-y-2 text-sm text-text-3">
                                    <li class="flex items-start">
                                        <svg
                                            class="w-4 h-4 mr-2 mt-0.5 text-primary flex-shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 016 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        Karyawan akan menerima email OTP untuk
                                        verifikasi akun
                                    </li>
                                    <li class="flex items-start">
                                        <svg
                                            class="w-4 h-4 mr-2 mt-0.5 text-primary flex-shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 016 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        Password akan dibuat saat verifikasi OTP
                                        pertama kali
                                    </li>
                                    <li class="flex items-start">
                                        <svg
                                            class="w-4 h-4 mr-2 mt-0.5 text-primary flex-shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 016 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        Karyawan bisa melakukan absensi setelah
                                        email terverifikasi
                                    </li>
                                    <li class="flex items-start">
                                        <svg
                                            class="w-4 h-4 mr-2 mt-0.5 text-primary flex-shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 016 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        Lokasi absensi akan dibatasi sesuai
                                        radius outlet yang ditentukan
                                    </li>
                                </ul>
                            </div>

                            <!-- Outlet Preview -->
                            <div
                                v-if="form.outlet_id"
                                class="bg-surface-1 border border-border rounded-lg p-4"
                            >
                                <h3
                                    class="text-lg font-semibold text-text mb-3"
                                >
                                    Detail Outlet
                                </h3>
                                <div
                                    v-for="outlet in outlets"
                                    :key="outlet.id"
                                    v-show="outlet.id == form.outlet_id"
                                    class="space-y-2 text-sm"
                                >
                                    <div>
                                        <span class="text-text-3">Nama:</span>
                                        <span class="text-text ml-2">{{
                                            outlet.name
                                        }}</span>
                                    </div>
                                    <div>
                                        <span class="text-text-3">Alamat:</span>
                                        >
                                        <span class="text-text ml-2">{{
                                            outlet.address
                                        }}</span>
                                    </div>
                                    <div>
                                        <span class="text-text-3">Radius:</span>
                                        >
                                        <span class="text-text ml-2"
                                            >{{ outlet.radius }} meter</span
                                        >
                                    </div>
                                    <div>
                                        <span class="text-text-3"
                                            >Koordinat:</span
                                        >
                                        >
                                        <span class="text-text ml-2"
                                            >{{ outlet.latitude }},
                                            {{ outlet.longitude }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div
                        class="flex items-center justify-end space-x-4 border-t border-border pt-6"
                    >
                        <Link :href="route('employees.index')">
                            <Button variant="secondary"> Batal </Button>
                        </Link>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            variant="primary"
                        >
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Karyawan</span>
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    </AuthenticatedLayout>
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
