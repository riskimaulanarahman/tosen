<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

const props = defineProps({
    shiftSchedules: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const breadcrumbItems = [
    { name: "Dashboard", href: route("dashboard") },
    { name: "Manajemen Shift", href: route("shifts.index") },
    { name: "Jadwal Shift" },
];

const filterForm = ref({
    outlet_id: props.filters?.outlet_id || "",
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
});

const scheduleForm = useForm({
    outlet_id: props.filters?.outlet_id || "",
    start_date: props.filters?.date_from || "",
    end_date: props.filters?.date_to || "",
    notes: "",
});

const schedules = computed(
    () => props.shiftSchedules || { data: [], links: [] }
);

const applyFilters = () => {
    router.get(route("shifts.schedule"), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filterForm.value = {
        outlet_id: "",
        date_from: "",
        date_to: "",
    };
    applyFilters();
};

const submitSchedule = () => {
    scheduleForm.post(route("shifts.generate.schedule"), {
        preserveScroll: true,
        onSuccess: () => scheduleForm.reset("notes"),
    });
};

const formatDate = (value) => {
    if (!value) return "-";
    return new Date(value).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
</script>

<template>
    <Head title="Jadwal Shift" />

    <AuthenticatedLayout>
        <template #breadcrumb>
            <Breadcrumb :items="breadcrumbItems" />
        </template>

        <div class="space-y-6">
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <h1 class="text-3xl font-bold text-text">Jadwal Shift</h1>
                    <p class="text-muted">
                        Buat dan pantau jadwal shift berdasarkan outlet
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="secondary" @click="resetFilters">
                        Reset Filter
                    </Button>
                    <Button @click="applyFilters">Terapkan Filter</Button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card class="lg:col-span-2">
                    <div class="p-4 space-y-4">
                        <h2 class="text-lg font-semibold text-text">
                            Filter Jadwal
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm text-muted mb-1"
                                    >Outlet</label
                                >
                                <select
                                    v-model="filterForm.outlet_id"
                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
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
                            <div>
                                <label class="block text-sm text-muted mb-1"
                                    >Mulai</label
                                >
                                <input
                                    v-model="filterForm.date_from"
                                    type="date"
                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                />
                            </div>
                            <div>
                                <label class="block text-sm text-muted mb-1"
                                    >Selesai</label
                                >
                                <input
                                    v-model="filterForm.date_to"
                                    type="date"
                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                />
                            </div>
                        </div>
                    </div>
                </Card>

                <Card>
                    <div class="p-4 space-y-3">
                        <h3 class="text-lg font-semibold text-text">
                            Generate Jadwal Baru
                        </h3>
                        <div class="space-y-3">
                            <select
                                v-model="scheduleForm.outlet_id"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
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
                            <input
                                v-model="scheduleForm.start_date"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                            <input
                                v-model="scheduleForm.end_date"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                            <textarea
                                v-model="scheduleForm.notes"
                                rows="2"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                placeholder="Catatan tambahan"
                            ></textarea>
                            <Button
                                class="w-full"
                                :disabled="scheduleForm.processing"
                                @click="submitSchedule"
                            >
                                {{
                                    scheduleForm.processing
                                        ? "Memproses..."
                                        : "Generate Jadwal"
                                }}
                            </Button>
                            <div
                                v-if="Object.keys(scheduleForm.errors).length"
                                class="text-xs text-danger"
                            >
                                <div
                                    v-for="(error, key) in scheduleForm.errors"
                                    :key="key"
                                >
                                    {{ error }}
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <Card>
                <div class="p-4 space-y-4">
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-2"
                    >
                        <div>
                            <h2 class="text-lg font-semibold text-text">
                                Jadwal Terbaru
                            </h2>
                            <p class="text-sm text-muted">
                                Menampilkan jadwal berdasarkan filter aktif
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-muted">
                                    <th class="px-4 py-3">Shift</th>
                                    <th class="px-4 py-3">Outlet</th>
                                    <th class="px-4 py-3">Periode</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-if="(schedules.data || []).length === 0">
                                    <td
                                        colspan="4"
                                        class="px-4 py-6 text-center text-muted"
                                    >
                                        Jadwal belum tersedia untuk kriteria ini.
                                    </td>
                                </tr>
                                <tr
                                    v-for="schedule in schedules.data"
                                    :key="schedule.id"
                                >
                                    <td class="px-4 py-3">
                                        <p class="font-semibold">
                                            {{ schedule.shift?.name || "-" }}
                                        </p>
                                        <p class="text-xs text-muted">
                                            {{
                                                schedule.shift?.start_time
                                                    ? schedule.shift.start_time.slice(0, 5)
                                                    : "-"
                                            }}
                                            -
                                            {{
                                                schedule.shift?.end_time
                                                    ? schedule.shift.end_time.slice(0, 5)
                                                    : "-"
                                            }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ schedule.outlet?.name || `Outlet #${schedule.outlet_id}` }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ formatDate(schedule.effective_date) }}
                                        -
                                        {{
                                            schedule.end_date
                                                ? formatDate(schedule.end_date)
                                                : "Berjalan"
                                        }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex px-2 py-1 rounded-full text-xs"
                                            :class="schedule.is_active ? 'bg-success-100 text-success-800' : 'bg-gray-100 text-gray-600'"
                                        >
                                            {{ schedule.is_active ? "Aktif" : "Nonaktif" }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="schedules.links" class="flex flex-wrap gap-2 pt-2">
                        <Link
                            v-for="(link, index) in schedules.links"
                            :key="index"
                            :href="link.url || '#'"
                            class="px-3 py-1 rounded border border-border text-sm"
                            :class="[
                                link.active
                                    ? 'bg-primary text-white border-primary'
                                    : 'bg-surface-1 text-text',
                                !link.url && 'opacity-50 pointer-events-none',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
