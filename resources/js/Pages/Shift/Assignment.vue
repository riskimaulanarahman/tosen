<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

const props = defineProps({
    employeeShifts: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    employees: {
        type: Array,
        default: () => [],
    },
    shifts: {
        type: Array,
        default: () => [],
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
    { name: "Penugasan Shift" },
];

const filterForm = ref({
    outlet_id: props.filters?.outlet_id || "",
});

const assignmentForm = useForm({
    user_id: "",
    shift_id: "",
    start_date: "",
    end_date: "",
    notes: "",
});

const assignments = computed(
    () => props.employeeShifts || { data: [], links: [] }
);

const activeCount = computed(
    () =>
        (assignments.value.data || []).filter(
            (item) => item.is_active
        ).length
);

const applyFilters = () => {
    router.get(route("shifts.assign.index"), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filterForm.value.outlet_id = "";
    applyFilters();
};

const submitAssignment = () => {
    assignmentForm.post(route("shifts.assign.employee"), {
        preserveScroll: true,
        onSuccess: () => assignmentForm.reset("notes"),
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
    <Head title="Penugasan Shift" />

    <AuthenticatedLayout>
        <template #breadcrumb>
            <Breadcrumb :items="breadcrumbItems" />
        </template>

        <div class="space-y-6">
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <h1 class="text-3xl font-bold text-text">
                        Penugasan Shift
                    </h1>
                    <p class="text-muted">
                        Atur penugasan karyawan dan pantau riwayatnya
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
                            Filter Penugasan
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                        </div>
                    </div>
                </Card>

                <Card>
                    <div class="p-4 space-y-3">
                        <h3 class="text-lg font-semibold text-text">
                            Penugasan Baru
                        </h3>
                        <div class="space-y-3">
                            <select
                                v-model="assignmentForm.user_id"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            >
                                <option value="">Pilih Karyawan</option>
                                <option
                                    v-for="employee in employees"
                                    :key="employee.id"
                                    :value="employee.id"
                                >
                                    {{ employee.name }} · Outlet #{{
                                        employee.outlet_id || "-"
                                    }}
                                </option>
                            </select>
                            <select
                                v-model="assignmentForm.shift_id"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            >
                                <option value="">Pilih Shift</option>
                                <option
                                    v-for="shift in shifts"
                                    :key="shift.id"
                                    :value="shift.id"
                                >
                                    {{ shift.name }}
                                </option>
                            </select>
                            <input
                                v-model="assignmentForm.start_date"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                            <input
                                v-model="assignmentForm.end_date"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                placeholder="Opsional"
                            />
                            <textarea
                                v-model="assignmentForm.notes"
                                rows="2"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                placeholder="Catatan tambahan"
                            ></textarea>
                            <Button
                                class="w-full"
                                :disabled="assignmentForm.processing"
                                @click="submitAssignment"
                            >
                                {{
                                    assignmentForm.processing
                                        ? "Menyimpan..."
                                        : "Assign Shift"
                                }}
                            </Button>
                            <div
                                v-if="Object.keys(assignmentForm.errors).length"
                                class="text-xs text-danger"
                            >
                                <div
                                    v-for="(error, key) in assignmentForm.errors"
                                    :key="key"
                                >
                                    {{ error }}
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <div class="p-4">
                        <p class="text-sm text-muted">Total Penugasan</p>
                        <p class="text-3xl font-bold text-text">
                            {{ assignments.data?.length || 0 }}
                        </p>
                    </div>
                </Card>
                <Card>
                    <div class="p-4">
                        <p class="text-sm text-muted">Penugasan Aktif</p>
                        <p class="text-3xl font-bold text-success">
                            {{ activeCount }}
                        </p>
                    </div>
                </Card>
                <Card>
                    <div class="p-4">
                        <p class="text-sm text-muted">Total Karyawan</p>
                        <p class="text-3xl font-bold text-info">
                            {{ employees.length }}
                        </p>
                    </div>
                </Card>
            </div>

            <Card>
                <div class="p-4 space-y-4">
                    <h2 class="text-lg font-semibold text-text">
                        Riwayat Penugasan
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-muted">
                                    <th class="px-4 py-3">Karyawan</th>
                                    <th class="px-4 py-3">Shift</th>
                                    <th class="px-4 py-3">Periode</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-if="(assignments.data || []).length === 0">
                                    <td
                                        colspan="4"
                                        class="px-4 py-6 text-center text-muted"
                                    >
                                        Belum ada penugasan.
                                    </td>
                                </tr>
                                <tr
                                    v-for="assignment in assignments.data"
                                    :key="assignment.id"
                                >
                                    <td class="px-4 py-3">
                                        <p class="font-semibold">
                                            {{
                                                assignment.user?.name ||
                                                `Karyawan #${assignment.user_id}`
                                            }}
                                        </p>
                                        <p class="text-xs text-muted">
                                            {{ assignment.user?.email || "-" }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold">
                                            {{ assignment.shift?.name || "-" }}
                                        </p>
                                        <p class="text-xs text-muted">
                                            {{
                                                assignment.shift?.start_time
                                                    ? assignment.shift.start_time.slice(0, 5)
                                                    : "-"
                                            }}
                                            -
                                            {{
                                                assignment.shift?.end_time
                                                    ? assignment.shift.end_time.slice(0, 5)
                                                    : "-"
                                            }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ formatDate(assignment.start_date) }}
                                        -
                                        {{
                                            assignment.end_date
                                                ? formatDate(assignment.end_date)
                                                : "Berjalan"
                                        }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex px-2 py-1 rounded-full text-xs"
                                            :class="assignment.is_active ? 'bg-success-100 text-success-800' : 'bg-gray-100 text-gray-600'"
                                        >
                                            {{ assignment.is_active ? "Aktif" : "Selesai" }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="assignments.links"
                        class="flex flex-wrap gap-2 pt-2"
                    >
                        <Link
                            v-for="(link, index) in assignments.links"
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
