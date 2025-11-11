<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

const props = defineProps({
    payrollPeriods: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
const periods = computed(() => props.payrollPeriods || { data: [], links: [] });

const breadcrumbItems = [
    { name: "Dashboard", href: route("dashboard") },
    { name: "Manajemen Penggajian" },
];

const filterForm = ref({
    outlet_id: props.filters?.outlet_id || "",
    status: props.filters?.status || "active",
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
});

const formatCurrency = (value) => {
    if (value === null || value === undefined) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(Number(value));
};

const applyFilters = () => {
    router.get(route("payroll.index"), filterForm.value, {
        preserveScroll: true,
        preserveState: true,
    });
};

const resetFilters = () => {
    filterForm.value = {
        outlet_id: "",
        status: "active",
        date_from: "",
        date_to: "",
    };
    applyFilters();
};

const deletePeriod = (period) => {
    if (!period?.id) return;
    if (
        confirm(
            `Hapus periode "${period.name}"? Tindakan ini tidak dapat dibatalkan.`
        )
    ) {
        router.delete(route("payroll.destroy", period.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Manajemen Penggajian" />

    <AuthenticatedLayout>
        <template #breadcrumb>
            <Breadcrumb :items="breadcrumbItems" />
        </template>

        <div class="space-y-6">
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Manajemen Penggajian
                    </h1>
                    <p class="text-muted">
                        Kelola periode payroll, proses perhitungan, dan status
                        pembayaran
                    </p>
                </div>
                <Link :href="route('payroll.create.page')">
                    <Button>Tambah Periode</Button>
                </Link>
            </div>

            <div
                v-if="flash?.success"
                class="bg-success-900/20 border border-success-700 text-success-100 p-4 rounded-lg"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash?.error"
                class="bg-danger-900/20 border border-danger-700 text-danger-100 p-4 rounded-lg"
            >
                {{ flash.error }}
            </div>

            <Card>
                <div class="p-4 space-y-4">
                    <h2 class="text-lg font-semibold text-text">
                        Filter Periode
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm text-muted mb-1"
                                >ID Outlet</label
                            >
                            <input
                                v-model="filterForm.outlet_id"
                                type="number"
                                min="1"
                                placeholder="Contoh: 1"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                        </div>
                        <div>
                            <label class="block text-sm text-muted mb-1"
                                >Status</label
                            >
                            <select
                                v-model="filterForm.status"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            >
                                <option value="">Semua</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="paid">Paid</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-muted mb-1"
                                >Tanggal Mulai</label
                            >
                            <input
                                v-model="filterForm.date_from"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                        </div>
                        <div>
                            <label class="block text-sm text-muted mb-1"
                                >Tanggal Akhir</label
                            >
                            <input
                                v-model="filterForm.date_to"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                        </div>
                        <div class="flex items-end gap-2">
                            <Button class="flex-1" @click="applyFilters"
                                >Terapkan</Button
                            >
                            <Button
                                variant="secondary"
                                class="flex-1"
                                @click="resetFilters"
                                >Reset</Button
                            >
                        </div>
                    </div>
                </div>
            </Card>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <Card class="xl:col-span-2">
                    <div class="p-6 space-y-4">
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between gap-2"
                        >
                            <div>
                                <h2 class="text-xl font-bold text-text">
                                    Daftar Periode Payroll
                                </h2>
                                <p class="text-sm text-muted">
                                    Menampilkan
                                    {{ periods.data?.length || 0 }} periode
                                    terbaru
                                </p>
                            </div>
                            <Link
                                :href="route('payroll.statistics')"
                                class="text-primary text-sm font-medium hover:text-primary-600 transition-colors"
                            >
                                Statistik Penggajian →
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table
                                class="min-w-full divide-y divide-border text-sm"
                            >
                                <thead>
                                    <tr
                                        class="text-left text-xs uppercase text-muted bg-surface-1"
                                    >
                                        <th class="px-6 py-3">Periode</th>
                                        <th class="px-6 py-3">Durasi</th>
                                        <th class="px-6 py-3">Tarif</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3 text-right">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-surface-1 divide-y divide-border"
                                >
                                    <tr
                                        v-if="(periods.data || []).length === 0"
                                    >
                                        <td
                                            colspan="5"
                                            class="px-6 py-8 text-center text-muted"
                                        >
                                            <div
                                                class="flex flex-col items-center"
                                            >
                                                <svg
                                                    class="w-12 h-12 text-gray-400 mb-3"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8c-1.657 0-3-1.343-3-3S10.343 2 12 2s3 1.343 3 3 3 3-1.343 3-3-1.657z"
                                                    />
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 2v6m0 0l-3 3m3-3l3 3m-6 0h6"
                                                    />
                                                </svg>
                                                <p class="text-lg font-medium">
                                                    Belum ada periode payroll
                                                </p>
                                                <p class="text-sm mt-1">
                                                    Silakan buat periode payroll
                                                    terlebih dahulu
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="period in periods.data"
                                        :key="period.id"
                                        class="hover:bg-surface-2 transition-colors"
                                    >
                                        <td class="px-6 py-4">
                                            <div>
                                                <p
                                                    class="font-semibold text-text"
                                                >
                                                    {{ period.name }}
                                                </p>
                                                <p class="text-xs text-muted">
                                                    Mulai
                                                    {{ period.start_date }} s/d
                                                    {{ period.end_date }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-surface-2 text-text"
                                            >
                                                {{
                                                    period.duration_days || 0
                                                }}
                                                hari
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <p
                                                    class="font-medium text-text"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            period.basic_rate
                                                        )
                                                    }}
                                                </p>
                                                <p class="text-xs text-muted">
                                                    Overtime x{{
                                                        Number(
                                                            period.overtime_rate ||
                                                                0
                                                        ).toFixed(2)
                                                    }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                                :class="
                                                    period.status ===
                                                    'completed'
                                                        ? 'bg-success-100 text-success-800'
                                                        : period.status ===
                                                          'active'
                                                        ? 'bg-primary-100 text-primary-800'
                                                        : period.status ===
                                                          'paid'
                                                        ? 'bg-info-100 text-info-800'
                                                        : 'bg-gray-100 text-gray-600'
                                                "
                                            >
                                                <span
                                                    class="w-2 h-2 rounded-full mr-2"
                                                    :class="
                                                        period.status ===
                                                        'completed'
                                                            ? 'bg-success-500'
                                                            : period.status ===
                                                              'active'
                                                            ? 'bg-primary-500'
                                                            : period.status ===
                                                              'paid'
                                                            ? 'bg-info-500'
                                                            : 'bg-gray-500'
                                                    "
                                                ></span>
                                                {{ period.status }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-4 text-right space-x-2"
                                        >
                                            <Link
                                                :href="
                                                    route(
                                                        'payroll.show',
                                                        period.id
                                                    )
                                                "
                                            >
                                                <Button
                                                    size="sm"
                                                    variant="secondary"
                                                    >Detail</Button
                                                >
                                            </Link>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                @click="deletePeriod(period)"
                                                :disabled="
                                                    period.status === 'paid'
                                                "
                                            >
                                                Hapus
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-if="periods.links"
                            class="flex flex-wrap gap-2 pt-4 justify-center"
                        >
                            <Link
                                v-for="(link, index) in periods.links"
                                :key="index"
                                :href="link.url || '#'"
                                class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                                :class="[
                                    link.active
                                        ? 'bg-primary text-white border-primary hover:bg-primary-600'
                                        : 'bg-surface-1 text-text border-border hover:bg-surface-2',
                                    !link.url &&
                                        'opacity-50 cursor-not-allowed',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </Card>

                <div class="space-y-6">
                    <Card class="hover:shadow-lg transition-shadow">
                        <div class="p-6 space-y-3">
                            <h3 class="text-lg font-semibold text-text">Aksi Cepat</h3>
                            <p class="text-sm text-muted">
                                Gunakan halaman khusus untuk membuat periode baru atau menjalankan proses payroll secara menyeluruh.
                            </p>
                            <div class="space-y-2">
                                <Link :href="route('payroll.create.page')">
                                    <Button variant="secondary" class="w-full">
                                        Buat / Generate Payroll
                                    </Button>
                                </Link>
                                <Link :href="route('payroll.statistics')">
                                    <Button variant="ghost" class="w-full">
                                        Statistik Payroll
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
