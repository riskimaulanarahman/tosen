<script setup>
import { ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import Table from "@/Components/ui/Table.vue";
import Swal from "sweetalert2";

const props = defineProps({
    employees: Object,
    outlets: Array,
});

const search = ref("");
const selectedOutlet = ref("");
const selectedEmployees = ref([]);

const bulkForm = useForm({
    employee_ids: [],
    outlet_id: "",
});

const columns = [
    { key: "name", label: "Nama" },
    { key: "email", label: "Email" },
    { key: "outlet.name", label: "Outlet" },
    { key: "created_at", label: "Tanggal Dibuat" },
];

const filteredEmployees = () => {
    return props.employees.data.filter((employee) => {
        const matchesSearch =
            employee.name.toLowerCase().includes(search.value.toLowerCase()) ||
            employee.email.toLowerCase().includes(search.value.toLowerCase());
        const matchesOutlet =
            !selectedOutlet.value || employee.outlet_id == selectedOutlet.value;
        return matchesSearch && matchesOutlet;
    });
};

const toggleAll = () => {
    if (selectedEmployees.value.length === filteredEmployees().length) {
        selectedEmployees.value = [];
    } else {
        selectedEmployees.value = filteredEmployees().map((emp) => emp.id);
    }
};

const bulkAssign = () => {
    const selectedCount = selectedEmployees.value.length;

    Swal.fire({
        title: "Assign ke Outlet?",
        html: `Assign <strong>${selectedCount} karyawan</strong> ke outlet yang dipilih?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3b82f6",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Ya, Assign!",
        cancelButtonText: "Batal",
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            bulkForm.employee_ids = selectedEmployees.value;
            bulkForm.post(route("employees.bulk-assign"), {
                onSuccess: () => {
                    selectedEmployees.value = [];
                    bulkForm.reset();
                    Swal.fire({
                        title: "Berhasil!",
                        text: `${selectedCount} karyawan berhasil diassign ke outlet`,
                        icon: "success",
                        timer: 3000,
                        showConfirmButton: false,
                        toast: true,
                        position: "top-end",
                    });
                },
            });
        }
    });
};

const deleteEmployee = (employee) => {
    Swal.fire({
        title: "Hapus Karyawan?",
        html: `Apakah Anda yakin ingin menghapus karyawan <strong>${employee.name}</strong>?<br><small class="text-text-3">Email: ${employee.email}</small>`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            useForm({}).delete(route("employees.destroy", employee.id));
        }
    });
};

const resendEmail = (employee) => {
    Swal.fire({
        title: "Kirim Ulang Email?",
        html: `Kirim ulang email verifikasi ke:<br><strong>${employee.email}</strong><br><small class="text-text-3">Password dan OTP baru akan dibuat</small>`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#10b981",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Ya, Kirim!",
        cancelButtonText: "Batal",
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            useForm({}).post(route("employees.resend-email", employee.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: "Berhasil!",
                        text: `Email verifikasi telah dikirim ke ${employee.email}`,
                        icon: "success",
                        timer: 3000,
                        showConfirmButton: false,
                        toast: true,
                        position: "top-end",
                    });
                },
            });
        }
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
</script>

<template>
    <Head title="Employee Management" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Manajemen Karyawan
                    </h1>
                    <p class="text-muted">
                        Kelola semua karyawan dari outlet Anda
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <Link :href="route('employees.create')">
                        <Button variant="primary">
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
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            Tambah Karyawan
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari karyawan..."
                                class="pl-10 pr-4 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <svg
                                class="absolute left-3 top-2.5 w-5 h-5 text-text-3"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 11 14 0z"
                                />
                            </svg>
                        </div>

                        <!-- Outlet Filter -->
                        <select
                            v-model="selectedOutlet"
                            class="px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="">Semua Outlet</option>
                            <option
                                v-for="outlet in outlets"
                                :key="outlet.id"
                                :value="outlet.id"
                            >
                                {{ outlet.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Bulk Actions -->
                    <div
                        v-if="selectedEmployees.length > 0"
                        class="flex items-center space-x-4"
                    >
                        <select
                            v-model="bulkForm.outlet_id"
                            class="px-3 py-2 bg-surface-1 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="">Pilih Outlet</option>
                            <option
                                v-for="outlet in outlets"
                                :key="outlet.id"
                                :value="outlet.id"
                            >
                                {{ outlet.name }}
                            </option>
                        </select>
                        <Button
                            @click="bulkAssign"
                            :disabled="
                                bulkForm.processing || !bulkForm.outlet_id
                            "
                            variant="secondary"
                        >
                            <span v-if="bulkForm.processing">Memproses...</span>
                            <span v-else>Assign ke Outlet</span>
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Employee Table -->
            <Card>
                <div
                    v-if="filteredEmployees().length > 0"
                    class="overflow-x-auto"
                >
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-surface-2">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input
                                        type="checkbox"
                                        @change="toggleAll"
                                        :checked="
                                            selectedEmployees.length ===
                                            filteredEmployees().length
                                        "
                                        class="rounded border-border text-primary focus:ring-2 focus:ring-primary"
                                    />
                                </th>
                                <th
                                    v-for="column in columns"
                                    :key="column.key"
                                    class="px-6 py-3 text-left text-xs font-medium text-text-3 uppercase tracking-wider"
                                >
                                    {{ column.label }}
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-text-3 uppercase tracking-wider"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface-1 divide-y divide-border">
                            <tr
                                v-for="employee in filteredEmployees()"
                                :key="employee.id"
                                class="hover:bg-surface-2"
                            >
                                <td class="px-6 py-4">
                                    <input
                                        type="checkbox"
                                        v-model="selectedEmployees"
                                        :value="employee.id"
                                        class="rounded border-border text-primary focus:ring-2 focus:ring-primary"
                                    />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-text">
                                        {{ employee.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-text-3">
                                        {{ employee.email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-text-3">
                                        {{ employee.outlet?.name || "-" }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-text-3">
                                        {{ formatDate(employee.created_at) }}
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                >
                                    <div
                                        class="flex items-center justify-end space-x-2"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'employees.show',
                                                    employee.id
                                                )
                                            "
                                            class="text-primary hover:text-primary/2"
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
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="resendEmail(employee)"
                                            class="text-success hover:text-success/2"
                                            title="Kirim Ulang Email"
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
                                                    d="M3 8l7.89 7.89a3 3 0 110-4.24 0l-7.89-7.89a3 3 0 010-4.24 0z"
                                                />
                                            </svg>
                                        </button>
                                        <Link
                                            :href="
                                                route(
                                                    'employees.edit',
                                                    employee.id
                                                )
                                            "
                                            class="text-info hover:text-info/2"
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
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2V7a2 2 0 00-2-2z"
                                                />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="deleteEmployee(employee)"
                                            class="text-error hover:text-error/2"
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
                                                    d="M19 7l-.867 12.142A2 2 0 0116 21H8a2 2 0 01-1.997-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="text-center py-12">
                    <svg
                        class="mx-auto h-12 w-12 text-text-3"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 20h5v-2H3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M7 13H5a2 2 0 00-2 2v10a2 2 0 002 2h5m11 6H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-text-3">
                        Tidak ada karyawan
                    </h3>
                    <p class="mt-1 text-sm text-text-2">
                        Mulai dengan menambah karyawan pertama Anda.
                    </p>
                    <div class="mt-6">
                        <Link :href="route('employees.create')">
                            <Button variant="primary">
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
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                Tambah Karyawan
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="employees.links" class="mt-6">
                    <template v-for="(link, key) in employees.links" :key="key">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-2 text-sm border rounded-none',
                                link.active
                                    ? 'bg-primary text-white border-primary'
                                    : 'bg-surface-2 text-text-3 border-border hover:bg-surface-2 hover:text-text',
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-2 text-sm text-text-3 border border-border rounded-none"
                            v-html="link.label"
                        />
                    </template>
                </div>
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
