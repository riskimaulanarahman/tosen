<script setup>
import { ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import OperationalStatus from "@/Components/OperationalStatus.vue";

const page = usePage();
const props = defineProps({
    outlets: Object,
    filters: Object,
});

const successMessage = ref(page.props.flash?.success);
const errorMessage = ref(page.props.flash?.error);

const searchParams = ref({
    search: props.filters.search || "",
});

const applySearch = () => {
    router.get(
        route("outlets.index"),
        {
            search: searchParams.value.search,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const clearSearch = () => {
    searchParams.value.search = "";
    applySearch();
};

const deleteOutlet = (outlet) => {
    if (confirm(`Apakah Anda yakin ingin menghapus outlet "${outlet.name}"?`)) {
        router.delete(route("outlets.destroy", outlet.id), {
            onSuccess: () => {
                successMessage.value = "Outlet berhasil dihapus.";
            },
            onError: (errors) => {
                errorMessage.value =
                    errors.cannot_delete || "Gagal menghapus outlet.";
            },
        });
    }
};

// Breadcrumb items
const breadcrumbItems = [
    { name: "Dashboard", href: route("dashboard") },
    { name: "Manajemen Outlet" },
];
</script>

<template>
    <Head title="Manajemen Outlet" />

    <AuthenticatedLayout>
        <template #breadcrumb>
            <Breadcrumb :items="breadcrumbItems" />
        </template>

        <div class="space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Manajemen Outlet
                    </h1>
                    <p class="text-muted">Kelola semua outlet bisnis Anda</p>
                </div>
                <Link :href="route('outlets.create')">
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
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                            />
                        </svg>
                        Tambah Outlet
                    </Button>
                </Link>
            </div>

            <!-- Search -->
            <Card>
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <input
                            v-model="searchParams.search"
                            type="text"
                            placeholder="Cari outlet..."
                            class="w-full px-3 py-2 bg-surface-2 border border-border text-text rounded-none focus:outline-none focus:ring-2 focus:ring-primary"
                            @keyup.enter="applySearch"
                        />
                    </div>
                    <div class="flex gap-2">
                        <Button @click="applySearch" variant="primary">
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
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                            Cari
                        </Button>
                        <Button @click="clearSearch" variant="secondary">
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                            Reset
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Success/Error Messages -->
            <div
                v-if="successMessage"
                class="bg-green-900/20 border border-green-600 rounded-lg p-4"
            >
                <div class="flex">
                    <svg
                        class="w-5 h-5 text-green-400 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <div class="text-green-400">{{ successMessage }}</div>
                </div>
            </div>

            <div
                v-if="errorMessage"
                class="bg-red-900/20 border border-red-600 rounded-lg p-4"
            >
                <div class="flex">
                    <svg
                        class="w-5 h-5 text-red-400 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <div class="text-red-400">{{ errorMessage }}</div>
                </div>
            </div>

            <!-- Outlets Table -->
            <Card v-if="props.outlets.data?.length > 0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-surface-2">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-text-2 uppercase tracking-wider"
                                >
                                    Nama Outlet
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-text-2 uppercase tracking-wider"
                                >
                                    Alamat
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-text-2 uppercase tracking-wider"
                                >
                                    Karyawan
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-text-2 uppercase tracking-wider"
                                >
                                    Status Operasional
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-text-2 uppercase tracking-wider"
                                >
                                    Radius
                                </th>
                                <th class="relative px-6 py-3">
                                    <span class="sr-only">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface-1 divide-y divide-border">
                            <tr
                                v-for="outlet in props.outlets.data"
                                :key="outlet.id"
                                class="hover:bg-surface-2"
                            >
                                <td class="px-6 py-4">
                                    <div>
                                        <div
                                            class="text-sm font-medium text-text"
                                        >
                                            {{ outlet.name }}
                                        </div>
                                        <div class="text-sm text-muted">
                                            Lat: {{ outlet.latitude }}, Lng:
                                            {{ outlet.longitude }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-text-2">
                                        {{ outlet.address }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-surface-3 text-text-2"
                                    >
                                        {{ outlet.employees_count || 0 }}
                                        karyawan
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <OperationalStatus :outlet="outlet" />
                                    <div class="text-sm text-muted mt-1">
                                        <div>
                                            Waktu:
                                            {{
                                                outlet.formatted_operational_hours ||
                                                "N/A"
                                            }}
                                        </div>
                                        <div>
                                            Hari:
                                            {{
                                                outlet.formatted_work_days ||
                                                "Not set"
                                            }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-text-2">
                                        {{ outlet.radius }}m
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
                                                route('outlets.show', outlet.id)
                                            "
                                            class="text-primary hover:text-primary-400"
                                        >
                                            Lihat
                                        </Link>
                                        <Link
                                            :href="
                                                route('outlets.edit', outlet.id)
                                            "
                                            class="text-text-2 hover:text-text"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="deleteOutlet(outlet)"
                                            class="text-error hover:text-error-400"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="props.outlets.links" class="mt-6">
                    <template
                        v-for="(link, key) in props.outlets.links"
                        :key="key"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-2 text-sm border rounded-none',
                                link.active
                                    ? 'bg-primary text-white border-primary'
                                    : 'bg-surface-2 text-text-2 border-border hover:bg-surface-3 hover:text-white',
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-2 text-sm text-muted border border-border rounded-none"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </Card>

            <!-- Empty State -->
            <Card v-else>
                <div class="text-center py-12">
                    <svg
                        class="mx-auto h-12 w-12 text-muted"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-text-2">
                        Belum ada outlet
                    </h3>
                    <p class="mt-1 text-sm text-text-3">
                        Mulai dengan membuat outlet pertama Anda.
                    </p>
                    <div class="mt-6">
                        <Link :href="route('outlets.create')">
                            <Button variant="primary">
                                Buat Outlet Pertama
                            </Button>
                        </Link>
                    </div>
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
