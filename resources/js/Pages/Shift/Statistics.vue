<script setup>
import { computed, ref } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";

const props = defineProps({
    statistics: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const filterForm = ref({
    outlet_id: props.filters?.outlet_id || "",
    start_date: props.filters?.start_date || "",
    end_date: props.filters?.end_date || "",
});

const summary = computed(() => {
    if (!props.statistics || props.statistics.length === 0) {
        return {
            totalAssignments: 0,
            uniqueEmployees: 0,
            avgCoverage: 0,
        };
    }

    const totalAssignments = props.statistics.reduce(
        (sum, item) => sum + (item.total_assignments || 0),
        0
    );
    const uniqueEmployees = props.statistics.reduce(
        (sum, item) => sum + (item.unique_employees || 0),
        0
    );
    const avgCoverage =
        props.statistics.reduce(
            (sum, item) => sum + (item.coverage_percentage || 0),
            0
        ) / props.statistics.length;

    return {
        totalAssignments,
        uniqueEmployees,
        avgCoverage: Number.isFinite(avgCoverage) ? avgCoverage : 0,
    };
});

const applyFilters = () => {
    router.get(route("shifts.statistics"), filterForm.value, {
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
    <Head title="Statistik Shift" />

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
                        Statistik Shift
                    </h1>
                    <p class="text-muted">
                        Analisis performa shift berdasarkan outlet dan periode
                    </p>
                </div>
                <Button @click="applyFilters">Perbarui Statistik</Button>
            </div>

            <div
                v-if="flash?.error"
                class="bg-danger-900/20 border border-danger-700 text-danger-100 p-4 rounded-lg"
            >
                {{ flash.error }}
            </div>

            <Card>
                <div class="p-4 space-y-4">
                    <h2 class="text-lg font-semibold text-text">Filter Data</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm mb-1 text-muted"
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
                            <label class="block text-sm mb-1 text-muted"
                                >Tanggal Mulai</label
                            >
                            <input
                                v-model="filterForm.start_date"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                        </div>
                        <div>
                            <label class="block text-sm mb-1 text-muted"
                                >Tanggal Akhir</label
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
                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 001-1v-1m3-2V8a2 2 0 00-2 2v6a2 2 0 002 2zm6-4V7a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-muted">
                                    Total Penugasan
                                </p>
                                <p class="text-3xl font-bold text-text">
                                    {{ summary.totalAssignments }}
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
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 016-6h2a6 6 0 016 6v1zm0 0h6v2a2 2 0 002 2h-8a2 2 0 01-2-2v-2h8a2 2 0 012 2v2z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-muted">Karyawan Unik</p>
                                <p class="text-3xl font-bold text-info">
                                    {{ summary.uniqueEmployees }}
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
                                        d="M9 12l2 2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-muted">
                                    Rata-rata Coverage
                                </p>
                                <p class="text-3xl font-bold text-success">
                                    {{ summary.avgCoverage.toFixed(1) }}%
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <Card>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-text">
                            Detail Statistik Per Shift
                        </h2>
                        <p class="text-sm text-muted">
                            Periode: {{ filterForm.start_date || "-" }} s/d
                            {{ filterForm.end_date || "-" }}
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table
                            class="min-w-full divide-y divide-border text-sm"
                        >
                            <thead>
                                <tr
                                    class="text-left text-xs uppercase text-muted bg-surface-1"
                                >
                                    <th class="px-6 py-3">Shift</th>
                                    <th class="px-6 py-3">Penugasan</th>
                                    <th class="px-6 py-3">Karyawan</th>
                                    <th class="px-6 py-3">
                                        Assignment / Minggu
                                    </th>
                                    <th class="px-6 py-3">Coverage</th>
                                </tr>
                            </thead>
                            <tbody class="bg-surface-1 divide-y divide-border">
                                <tr v-if="(statistics || []).length === 0">
                                    <td
                                        colspan="5"
                                        class="px-6 py-8 text-center text-muted"
                                    >
                                        <div class="flex flex-col items-center">
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
                                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002 2v6a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v6z"
                                                />
                                            </svg>
                                            <p class="text-lg font-medium">
                                                Belum ada data statistik
                                            </p>
                                            <p class="text-sm mt-1">
                                                Silakan pilih filter yang
                                                berbeda
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-for="item in statistics"
                                    :key="item.shift?.id || item.id"
                                    class="hover:bg-surface-2 transition-colors"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div
                                                class="w-2 h-2 rounded-full mr-3"
                                                :style="{
                                                    backgroundColor:
                                                        item.shift
                                                            ?.color_code ||
                                                        '#3B82F6',
                                                }"
                                            ></div>
                                            <div>
                                                <p
                                                    class="font-semibold text-text"
                                                >
                                                    {{
                                                        item.shift?.name ||
                                                        "Shift"
                                                    }}
                                                </p>
                                                <p class="text-xs text-muted">
                                                    {{
                                                        item.shift
                                                            ?.description ||
                                                        "Tidak ada deskripsi"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800"
                                        >
                                            {{ item.total_assignments }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-info-100 text-info-800"
                                        >
                                            {{ item.unique_employees }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-medium text-text">
                                            {{
                                                Number(
                                                    item.avg_assignments_per_week ||
                                                        0
                                                ).toFixed(1)
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-full bg-surface-2 rounded-full h-2"
                                            >
                                                <div
                                                    class="bg-success h-2 rounded-full"
                                                    :style="{
                                                        width: `${Math.min(
                                                            item.coverage_percentage ||
                                                                0,
                                                            100
                                                        )}%`,
                                                    }"
                                                ></div>
                                            </div>
                                            <span
                                                class="text-xs text-text font-semibold"
                                            >
                                                {{
                                                    Number(
                                                        item.coverage_percentage ||
                                                            0
                                                    ).toFixed(1)
                                                }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
