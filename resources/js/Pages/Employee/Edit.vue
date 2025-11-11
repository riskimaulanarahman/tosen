<script setup>
import { useForm } from "@inertiajs/vue3";
import { Head, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";

const props = defineProps({
    employee: Object,
    outlets: Array,
});

const form = useForm({
    name: props.employee.name,
    email: props.employee.email,
    password: "",
    password_confirmation: "",
    outlet_id: props.employee.outlet_id,
});

const submit = () => {
    form.put(route("employees.update", props.employee.id), {
        onSuccess: () => {
            window.handleSuccess(`Karyawan ${form.name} berhasil diperbarui.`);
        },
        onError: (errors) => {
            window.handleError(errors);
        },
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Edit Employee" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Edit Karyawan
                    </h1>
                    <p class="text-muted">
                        Perbarui informasi karyawan: {{ employee.name }}
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <Link :href="route('employees.show', employee.id)">
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
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                />
                            </svg>
                            Lihat Detail
                        </Button>
                    </Link>
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
                                    d="M10 19l-7-7m0 0l7-7m-7h18"
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

                            <!-- Password Field -->
                            <div>
                                <label
                                    class="block text-text-3 text-sm font-medium mb-2"
                                >
                                    Password Baru (Opsional)
                                </label>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    class="w-full px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                    placeholder="Kosongkan jika tidak ingin mengubah"
                                />
                                <div
                                    v-if="form.errors.password"
                                    class="mt-1 text-sm text-error"
                                >
                                    {{ form.errors.password }}
                                </div>
                                <p class="mt-1 text-sm text-muted">
                                    Kosongkan jika tidak ingin mengubah password
                                </p>
                            </div>

                            <!-- Password Confirmation Field -->
                            <div>
                                <label
                                    class="block text-text-3 text-sm font-medium mb-2"
                                >
                                    Konfirmasi Password Baru
                                </label>
                                <input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="w-full px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                                    placeholder="Konfirmasi password baru"
                                />
                                <div
                                    v-if="form.errors.password_confirmation"
                                    class="mt-1 text-sm text-error"
                                >
                                    {{ form.errors.password_confirmation }}
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
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

                            <!-- Employee Information -->
                            <div
                                class="bg-surface-1 border border-border rounded-lg p-4"
                            >
                                <h3
                                    class="text-lg font-semibold text-text mb-3"
                                >
                                    Informasi Karyawan
                                </h3>
                                <div class="space-y-2 text-sm">
                                    <div>
                                        <span class="text-text-3"
                                            >ID Karyawan:</span
                                        >
                                        <span class="text-text ml-2"
                                            >#{{ employee.id }}</span
                                        >
                                    </div>
                                    <div>
                                        <span class="text-text-3"
                                            >Status Email:</span
                                        >
                                        <span class="ml-2">
                                            <span
                                                v-if="
                                                    employee.email_verified_at
                                                "
                                                class="text-success"
                                            >
                                                ✓ Terverifikasi
                                            </span>
                                            <span v-else class="text-warning">
                                                ⚠ Belum Verifikasi
                                            </span>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-text-3"
                                            >Tanggal Bergabung:</span
                                        >
                                        <span class="text-text ml-2">
                                            {{
                                                new Date(
                                                    employee.created_at
                                                ).toLocaleDateString("id-ID")
                                            }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-text-3"
                                            >Total Absensi:</span
                                        >
                                        <span class="text-text ml-2">
                                            {{
                                                employee.attendances_count || 0
                                            }}
                                            kali
                                        </span>
                                    </div>
                                </div>
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
                                        <span class="text-text ml-2">{{
                                            outlet.address
                                        }}</span>
                                    </div>
                                    <div>
                                        <span class="text-text-3">Radius:</span>
                                        <span class="text-text ml-2"
                                            >{{ outlet.radius }} meter</span
                                        >
                                    </div>
                                    <div>
                                        <span class="text-text-3"
                                            >Koordinat:</span
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
                            <span v-if="form.processing">Memperbarui...</span>
                            <span v-else>Simpan Perubahan</span>
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
