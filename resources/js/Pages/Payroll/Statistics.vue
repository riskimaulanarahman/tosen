<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";

const props = defineProps({
    statistics: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filterForm = ref({
    outlet_id: props.filters?.outlet_id || "",
    start_date: props.filters?.start_date || "",
    end_date: props.filters?.end_date || "",
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
    router.get(route("payroll.statistics"), filterForm.value, {
        preserveScroll: true,
        preserveState: true,
    });
};

const resetFilters = () => {
    filterForm.value = {
        outlet_id: "",
        start_date: "",
        end_date: "",
    };
    applyFilters();
};
</script>

<template>
    <Head title="Statistik Payroll" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Statistik Penggajian
                    </h1>
                    <p class="text-muted">
                        Ikhtisar performa payroll berdasarkan outlet dan periode
                    </p>
                </div>
                <Button @click="applyFilters">Perbarui Data</Button>
            </div>

            <Card>
                <div class="p-4 space-y-4">
                    <h2 class="text-lg font-semibold text-text">Filter Data</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm text-muted mb-1"
                                >ID Outlet</label
                            >
                            <input
                                v-model="filterForm.outlet_id"
                                type="number"
                                min="1"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                placeholder="Contoh: 1"
                            />
                        </div>
                        <div>
                            <label class="block text-sm text-muted mb-1"
                                >Mulai</label
                            >
                            <input
                                v-model="filterForm.start_date"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                        </div>
                        <div>
                            <label class="block text-sm text-muted mb-1"
                                >Selesai</label
                            >
                            <input
                                v-model="filterForm.end_date"
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div
                                class="flex-shrink-0 bg-primary-100 rounded-lg p-3 mr-4"
                            >
                                <svg
                                    class="w-6 h-6 text-primary"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 016-6h2a6 6 0 016 6v1zm0 0h6v2a2 2 0 002 2h-8a2 2 0 01-2-2v-2h8a2 2 0 012 2V2z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-muted">Total Karyawan</p>
                                <p class="text-3xl font-bold text-text">
                                    {{ statistics.total_employees || 0 }}
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>
                <Card class="hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div
                                class="flex-shrink-0 bg-success-100 rounded-lg p-3 mr-4"
                            >
                                <svg
                                    class="w-6 h-6 text-success"
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
                            </div>
                            <div>
                                <p class="text-sm text-muted">Total Net Pay</p>
                                <p class="text-3xl font-bold text-success">
                                    {{
                                        formatCurrency(statistics.total_net_pay)
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>
                <Card class="hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div
                                class="flex-shrink-0 bg-info-100 rounded-lg p-3 mr-4"
                            >
                                <svg
                                    class="w-6 h-6 text-info"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V9a2 2 0 00-2-2h-1m-6 0l2 2m0 0l2-2m-2 2h6"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-muted">
                                    Rata-rata Net Pay
                                </p>
                                <p class="text-3xl font-bold text-info">
                                    {{
                                        formatCurrency(
                                            statistics.average_net_pay
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <Card class="hover:shadow-lg transition-shadow">
                <div class="p-6 space-y-4">
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between"
                    >
                        <h2 class="text-xl font-bold text-text">
                            Ringkasan Periode
                        </h2>
                        <p class="text-sm text-muted">
                            Periode:
                            {{
                                statistics.payroll_period?.start_date || "-"
                            }}
                            s/d
                            {{ statistics.payroll_period?.end_date || "-" }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div
                            class="bg-surface-2 border border-border rounded-lg p-6 space-y-4"
                        >
                            <h3 class="text-lg font-semibold text-text mb-4">
                                Pendapatan
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-muted"
                                        >Total Gross Pay</span
                                    >
                                    <span class="font-semibold text-text">
                                        {{
                                            formatCurrency(
                                                statistics.total_gross_pay
                                            )
                                        }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-muted"
                                        >Total Lembur</span
                                    >
                                    <span class="font-semibold text-text">
                                        {{
                                            formatCurrency(
                                                statistics.total_overtime_pay
                                            )
                                        }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-muted"
                                        >Total Pajak</span
                                    >
                                    <span class="font-semibold text-danger">
                                        -{{
                                            formatCurrency(
                                                statistics.total_tax_deduction
                                            )
                                        }}
                                    </span>
                                </div>
                                <div class="border-t border-border pt-3 mt-3">
                                    <div
                                        class="flex justify-between items-center"
                                    >
                                        <span
                                            class="text-sm font-medium text-text"
                                            >Total Net Pay</span
                                        >
                                        <span
                                            class="font-bold text-lg text-success"
                                        >
                                            {{
                                                formatCurrency(
                                                    statistics.total_net_pay
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="bg-surface-2 border border-border rounded-lg p-6 space-y-4"
                        >
                            <h3 class="text-lg font-semibold text-text mb-4">
                                Statistik
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-muted"
                                        >Total Records</span
                                    >
                                    <span class="font-semibold text-text">
                                        {{ statistics.total_records || 0 }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-muted"
                                        >Payroll / Karyawan</span
                                    >
                                    <span class="font-semibold text-text">
                                        {{
                                            statistics.total_employees
                                                ? (statistics.total_records ||
                                                      0) /
                                                  statistics.total_employees
                                                : 0
                                        }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-muted"
                                        >Periode</span
                                    >
                                    <span class="font-semibold text-text">
                                        {{ filterForm.start_date || "-" }} →
                                        {{ filterForm.end_date || "-" }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
