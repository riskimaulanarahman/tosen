<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";

const props = defineProps({
    shift: {
        type: Object,
        required: true,
    },
});

const schedules = computed(() => props.shift?.shift_schedules || []);
const assignments = computed(() => props.shift?.employee_shifts || []);

const formatTime = (time) => {
    if (!time) return "-";
    const date = new Date(`1970-01-01T${time}`);
    return date.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
};

const formatDate = (value) => {
    if (!value) return "-";
    return new Date(value).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const badgeClass = computed(() => {
    if (!props.shift?.is_active) return "bg-gray-100 text-gray-800";
    if (props.shift?.is_overnight) return "bg-purple-100 text-purple-800";
    return "bg-success-100 text-success-800";
});
</script>

<template>
    <Head :title="`Detail Shift: ${shift.name}`" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-text" style="font-family: 'Oswald', sans-serif">
                        {{ shift.name }}
                    </h1>
                    <p class="text-muted">Pengaturan detail shift dan riwayat terkait</p>
                </div>
                <div class="flex gap-3">
                    <Link :href="route('shifts.index')">
                        <Button variant="secondary">Kembali</Button>
                    </Link>
                    <Link :href="route('shifts.statistics')" class="hidden sm:block">
                        <Button variant="primary">Lihat Statistik</Button>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card class="lg:col-span-2">
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-text">Informasi Shift</h2>
                            <span class="text-xs px-3 py-1 rounded-full" :class="badgeClass">
                                {{ shift.is_active ? "Aktif" : "Nonaktif" }}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-surface-2 border border-border rounded-lg p-4">
                                <p class="text-sm text-muted">Waktu Operasional</p>
                                <p class="text-xl font-semibold text-text">
                                    {{ formatTime(shift.start_time) }} - {{ formatTime(shift.end_time) }}
                                </p>
                                <p class="text-xs text-muted mt-1">
                                    Istirahat {{ shift.break_duration }} menit
                                </p>
                            </div>
                            <div class="bg-surface-2 border border-border rounded-lg p-4">
                                <p class="text-sm text-muted">Overnight</p>
                                <p class="text-xl font-semibold text-text">
                                    {{ shift.is_overnight ? "Ya" : "Tidak" }}
                                </p>
                                <p class="text-xs text-muted mt-1">
                                    Warna indikator: {{ shift.color_code }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-text mb-2">Deskripsi</p>
                            <p class="text-sm text-muted">
                                {{ shift.description || "Belum ada deskripsi untuk shift ini." }}
                            </p>
                        </div>
                    </div>
                </Card>

                <Card>
                    <div class="p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-text">Ringkasan</h2>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted">Total Jadwal</span>
                                <span class="font-semibold text-text">{{ schedules.length }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted">Penugasan Aktif</span>
                                <span class="font-semibold text-text">
                                    {{
                                        assignments.filter((assignment) => assignment.is_active).length
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted">Terakhir diperbarui</span>
                                <span class="font-semibold text-text">
                                    {{ formatDate(shift.updated_at) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <Card>
                <div class="p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-text">Jadwal Terkait</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-muted">
                                    <th class="px-4 py-3">Outlet</th>
                                    <th class="px-4 py-3">Periode</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-if="schedules.length === 0">
                                    <td colspan="4" class="px-4 py-6 text-center text-muted">
                                        Jadwal belum tersedia.
                                    </td>
                                </tr>
                                <tr v-for="schedule in schedules" :key="schedule.id">
                                    <td class="px-4 py-3">
                                        {{ schedule.outlet?.name || `Outlet #${schedule.outlet_id}` }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ formatDate(schedule.effective_date) }} -
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
                                    <td class="px-4 py-3 text-xs text-muted">
                                        {{ schedule.notes || "-" }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </Card>

            <Card>
                <div class="p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-text">Penugasan Karyawan</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-muted">
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="px-4 py-3">Periode</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-if="assignments.length === 0">
                                    <td colspan="4" class="px-4 py-6 text-center text-muted">
                                        Belum ada karyawan yang ditugaskan.
                                    </td>
                                </tr>
                                <tr v-for="assignment in assignments" :key="assignment.id">
                                    <td class="px-4 py-3">
                                        {{ assignment.user?.name || `Karyawan #${assignment.user_id}` }}
                                        <p class="text-xs text-muted">
                                            {{ assignment.user?.email || "-" }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ formatDate(assignment.start_date) }} -
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
                                            {{ assignment.is_active ? "Aktif" : "Berakhir" }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted">
                                        {{ assignment.notes || "-" }}
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
