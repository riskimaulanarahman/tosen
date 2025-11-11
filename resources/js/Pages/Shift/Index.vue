<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";

const page = usePage();
const props = defineProps({
    shifts: {
        type: Array,
        default: () => [],
    },
    shiftSchedules: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    employeeShifts: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const flash = computed(() => page.props.flash || {});
const shiftSchedules = computed(
    () => props.shiftSchedules || { data: [], links: [] }
);
const employeeShifts = computed(
    () => props.employeeShifts || { data: [], links: [] }
);

const filterForm = ref({
    outlet_id: props.filters?.outlet_id || "",
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
});

const createShiftForm = useForm({
    name: "",
    start_time: "09:00",
    end_time: "17:00",
    break_duration: 60,
    is_overnight: false,
    color_code: "#3B82F6",
    description: "",
    is_active: true,
});

const swapForm = useForm({
    requester_id: "",
    target_id: "",
    date: "",
});

const shiftStats = computed(() => {
    const list = props.shifts || [];
    const active = list.filter((shift) => shift.is_active).length;
    const overnight = list.filter((shift) => shift.is_overnight).length;
    const totalSchedules = shiftSchedules.value?.data?.length || 0;
    const employeeAssignments = employeeShifts.value?.data?.length || 0;

    return {
        total: list.length,
        active,
        overnight,
        totalSchedules,
        employeeAssignments,
    };
});

const applyFilters = () => {
    router.get(route("shifts.index"), filterForm.value, {
        preserveScroll: true,
        preserveState: true,
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

const submitCreateShift = () => {
    createShiftForm.post(route("shifts.store"), {
        preserveScroll: true,
        onSuccess: () => {
            createShiftForm.reset();
        },
    });
};

const submitSwap = () => {
    swapForm.post(route("shifts.swap"), {
        preserveScroll: true,
        onSuccess: () => swapForm.reset(),
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

const formatTimeRange = (shift) => {
    if (!shift.start_time || !shift.end_time) {
        return "-";
    }

    const start = new Date(`1970-01-01T${shift.start_time}`);
    const end = new Date(`1970-01-01T${shift.end_time}`);

    return `${start.toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
    })} - ${end.toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
    })}`;
};

const badgeClass = (shift) => {
    if (!shift.is_active) return "bg-gray-100 text-gray-800";
    if (shift.is_overnight) return "bg-purple-100 text-purple-800";
    return "bg-success-100 text-success-800";
};
</script>

<template>
    <Head title="Manajemen Shift" />

    <AuthenticatedLayout>
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
                        Manajemen Shift
                    </h1>
                    <p class="text-muted">
                        Kelola daftar shift, jadwal, dan penugasan karyawan
                    </p>
                </div>
                <div class="flex gap-3">
                    <Button
                        @click="submitCreateShift"
                        :disabled="createShiftForm.processing"
                    >
                        Simpan Shift Baru
                    </Button>
                    <Button variant="secondary" @click="applyFilters">
                        Terapkan Filter
                    </Button>
                </div>
            </div>

            <!-- Flash messages -->
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

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <div class="p-4">
                        <p class="text-sm text-muted">Total Shift</p>
                        <p class="text-3xl font-bold text-text">
                            {{ shiftStats.total }}
                        </p>
                    </div>
                </Card>
                <Card>
                    <div class="p-4">
                        <p class="text-sm text-muted">Shift Aktif</p>
                        <p class="text-3xl font-bold text-success">
                            {{ shiftStats.active }}
                        </p>
                    </div>
                </Card>
                <Card>
                    <div class="p-4">
                        <p class="text-sm text-muted">Overnight</p>
                        <p class="text-3xl font-bold text-purple-400">
                            {{ shiftStats.overnight }}
                        </p>
                    </div>
                </Card>
                <Card>
                    <div class="p-4">
                        <p class="text-sm text-muted">Penugasan Karyawan</p>
                        <p class="text-3xl font-bold text-info">
                            {{ shiftStats.employeeAssignments }}
                        </p>
                    </div>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <div class="p-4 space-y-4">
                    <h2 class="text-lg font-semibold text-text">
                        Filter Jadwal
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm mb-1 text-muted">
                                ID Outlet
                            </label>
                            <input
                                v-model="filterForm.outlet_id"
                                type="number"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                placeholder="Contoh: 1"
                            />
                        </div>
                        <div>
                            <label class="block text-sm mb-1 text-muted">
                                Tanggal Mulai
                            </label>
                            <input
                                v-model="filterForm.date_from"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                        </div>
                        <div>
                            <label class="block text-sm mb-1 text-muted">
                                Tanggal Akhir
                            </label>
                            <input
                                v-model="filterForm.date_to"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                        </div>
                        <div class="flex items-end gap-2">
                            <Button class="flex-1" @click="applyFilters">
                                Terapkan
                            </Button>
                            <Button
                                variant="secondary"
                                class="flex-1"
                                @click="resetFilters"
                            >
                                Reset
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Shift definition & forms -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <Card class="xl:col-span-2">
                    <div class="p-4 space-y-4">
                        <h2 class="text-lg font-semibold text-text">
                            Daftar Shift
                        </h2>
                        <div
                            v-if="(props.shifts || []).length === 0"
                            class="text-center text-muted py-6"
                        >
                            Belum ada shift yang terdaftar.
                        </div>
                        <div
                            v-else
                            class="grid grid-cols-1 md:grid-cols-2 gap-4"
                        >
                            <div
                                v-for="shift in props.shifts"
                                :key="shift.id"
                                class="bg-surface-2 border border-border rounded-lg p-4 space-y-3"
                            >
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-text">
                                        {{ shift.name }}
                                    </h3>
                                    <span
                                        class="text-xs px-2 py-1 rounded-full"
                                        :class="badgeClass(shift)"
                                    >
                                        {{
                                            shift.is_active
                                                ? "Aktif"
                                                : "Nonaktif"
                                        }}
                                    </span>
                                </div>
                                <p class="text-sm text-muted">
                                    {{ formatTimeRange(shift) }}
                                </p>
                                <p class="text-sm text-text">
                                    Durasi istirahat:
                                    {{ shift.break_duration }} menit
                                </p>
                                <p class="text-sm text-muted truncate">
                                    {{
                                        shift.description ||
                                        "Belum ada deskripsi"
                                    }}
                                </p>
                                <div
                                    class="flex justify-between text-xs text-muted"
                                >
                                    <span>
                                        Overnight:
                                        <strong>{{
                                            shift.is_overnight ? "Ya" : "Tidak"
                                        }}</strong>
                                    </span>
                                    <Link
                                        :href="route('shifts.show', shift.id)"
                                        class="text-primary hover:text-primary-400"
                                    >
                                        Detail
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>

                <div class="space-y-6">
                    <Card>
                        <div class="p-4 space-y-3">
                            <h3 class="text-lg font-semibold text-text">
                                Shift Baru
                            </h3>
                            <div class="grid grid-cols-1 gap-3">
                                <input
                                    v-model="createShiftForm.name"
                                    type="text"
                                    placeholder="Nama shift"
                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                />
                                <div class="grid grid-cols-2 gap-2">
                                    <input
                                        v-model="createShiftForm.start_time"
                                        type="time"
                                        class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                    />
                                    <input
                                        v-model="createShiftForm.end_time"
                                        type="time"
                                        class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                    />
                                </div>
                                <input
                                    v-model.number="
                                        createShiftForm.break_duration
                                    "
                                    type="number"
                                    min="0"
                                    placeholder="Durasi istirahat (menit)"
                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                />
                                <input
                                    v-model="createShiftForm.color_code"
                                    type="color"
                                    class="w-full h-10 bg-surface-2 border border-border rounded-lg"
                                />
                                <textarea
                                    v-model="createShiftForm.description"
                                    rows="2"
                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                    placeholder="Catatan singkat"
                                ></textarea>
                                <label
                                    class="flex items-center gap-2 text-sm text-text"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="createShiftForm.is_overnight"
                                    />
                                    Overnight
                                </label>
                                <label
                                    class="flex items-center gap-2 text-sm text-text"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="createShiftForm.is_active"
                                    />
                                    Aktif
                                </label>
                                <Button
                                    class="w-full"
                                    :disabled="createShiftForm.processing"
                                    @click="submitCreateShift"
                                >
                                    {{
                                        createShiftForm.processing
                                            ? "Menyimpan..."
                                            : "Simpan Shift"
                                    }}
                                </Button>
                                <div
                                    v-if="
                                        Object.keys(createShiftForm.errors)
                                            .length
                                    "
                                    class="text-xs text-danger"
                                >
                                    <div
                                        v-for="(
                                            error, key
                                        ) in createShiftForm.errors"
                                        :key="key"
                                    >
                                        {{ error }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div class="p-4 space-y-4">
                            <h3 class="text-lg font-semibold text-text">
                                Halaman Shift Lanjutan
                            </h3>
                            <p class="text-sm text-muted">
                                Akses halaman khusus untuk mengelola jadwal,
                                penugasan, dan analitik shift.
                            </p>
                            <div class="space-y-2">
                                <Link :href="route('shifts.schedule')">
                                    <Button variant="secondary" class="w-full">
                                        Kelola Jadwal Shift
                                    </Button>
                                </Link>
                                <Link :href="route('shifts.assign.index')">
                                    <Button variant="secondary" class="w-full">
                                        Penugasan Shift
                                    </Button>
                                </Link>
                                <Link :href="route('shifts.statistics')">
                                    <Button variant="ghost" class="w-full">
                                        Statistik Shift
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Schedules -->
            <Card>
                <div class="p-6 space-y-4">
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-2"
                    >
                        <div>
                            <h2 class="text-xl font-bold text-text">
                                Jadwal Shift
                            </h2>
                            <p class="text-sm text-muted">
                                Riwayat jadwal berdasarkan filter aktif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-muted">
                                Total:
                                {{ shiftSchedules.data?.length || 0 }} jadwal
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead>
                                <tr
                                    class="text-left text-xs uppercase text-muted bg-surface-1"
                                >
                                    <th class="px-6 py-3">Shift</th>
                                    <th class="px-6 py-3">Outlet</th>
                                    <th class="px-6 py-3">Periode</th>
                                    <th class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-surface-1 divide-y divide-border">
                                <tr
                                    v-if="
                                        (shiftSchedules.data || []).length === 0
                                    "
                                >
                                    <td
                                        colspan="4"
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
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                />
                                            </svg>
                                            <p class="text-lg font-medium">
                                                Jadwal belum tersedia
                                            </p>
                                            <p class="text-sm mt-1">
                                                Silakan buat jadwal shift
                                                terlebih dahulu
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-for="schedule in shiftSchedules.data"
                                    :key="schedule.id"
                                    class="hover:bg-surface-2 transition-colors"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div
                                                class="w-2 h-2 rounded-full mr-3"
                                                :style="{
                                                    backgroundColor:
                                                        schedule.shift
                                                            ?.color_code ||
                                                        '#3B82F6',
                                                }"
                                            ></div>
                                            <div>
                                                <p
                                                    class="font-semibold text-text"
                                                >
                                                    {{ schedule.shift?.name }}
                                                </p>
                                                <p class="text-xs text-muted">
                                                    {{
                                                        formatTimeRange(
                                                            schedule.shift || {}
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <svg
                                                class="w-4 h-4 text-gray-400 mr-2"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314-3.673m0 0L19.586 7.414A1.998 1.998 0 0021 5.586V4a1 1 0 00-1-1h-1.586a1.998 1.998 0 00-1.414.586l-4.243 4.242a8 8 0 010 11.314"
                                                />
                                            </svg>
                                            <span class="text-text">
                                                {{
                                                    schedule.outlet?.name ||
                                                    "Outlet #" +
                                                        schedule.outlet_id
                                                }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <p class="font-medium text-text">
                                                {{
                                                    formatDate(
                                                        schedule.effective_date
                                                    )
                                                }}
                                            </p>
                                            <p class="text-muted">
                                                {{
                                                    schedule.end_date
                                                        ? "s/d " +
                                                          formatDate(
                                                              schedule.end_date
                                                          )
                                                        : "Berjalan"
                                                }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                            :class="
                                                schedule.is_active
                                                    ? 'bg-success-100 text-success-800'
                                                    : 'bg-gray-100 text-gray-600'
                                            "
                                        >
                                            <span
                                                class="w-2 h-2 rounded-full mr-2"
                                                :class="
                                                    schedule.is_active
                                                        ? 'bg-success-500'
                                                        : 'bg-gray-500'
                                                "
                                            ></span>
                                            {{
                                                schedule.is_active
                                                    ? "Aktif"
                                                    : "Nonaktif"
                                            }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="shiftSchedules.links"
                        class="flex flex-wrap gap-2 pt-4 justify-center"
                    >
                        <Link
                            v-for="(link, index) in shiftSchedules.links"
                            :key="index"
                            :href="link.url || '#'"
                            class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                            :class="[
                                link.active
                                    ? 'bg-primary text-white border-primary hover:bg-primary-600'
                                    : 'bg-surface-1 text-text border-border hover:bg-surface-2',
                                !link.url && 'opacity-50 cursor-not-allowed',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </Card>

            <!-- Employee assignments -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <Card class="xl:col-span-2">
                    <div class="p-6 space-y-4">
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between gap-2"
                        >
                            <div>
                                <h2 class="text-xl font-bold text-text">
                                    Penugasan Karyawan
                                </h2>
                                <p class="text-sm text-muted">
                                    Daftar karyawan dan shift aktif mereka
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted">
                                    Total:
                                    {{
                                        employeeShifts.data?.length || 0
                                    }}
                                    penugasan
                                </span>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead>
                                    <tr
                                        class="text-left text-xs uppercase text-muted bg-surface-1"
                                    >
                                        <th class="px-6 py-3">Karyawan</th>
                                        <th class="px-6 py-3">Shift</th>
                                        <th class="px-6 py-3">Periode</th>
                                        <th class="px-6 py-3">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-surface-1 divide-y divide-border"
                                >
                                    <tr
                                        v-if="
                                            (employeeShifts.data || [])
                                                .length === 0
                                        "
                                    >
                                        <td
                                            colspan="4"
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
                                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                                    />
                                                </svg>
                                                <p class="text-lg font-medium">
                                                    Belum ada penugasan
                                                </p>
                                                <p class="text-sm mt-1">
                                                    Silakan lakukan penugasan
                                                    shift karyawan
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="assignment in employeeShifts.data"
                                        :key="assignment.id"
                                        class="hover:bg-surface-2 transition-colors"
                                    >
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center mr-3"
                                                >
                                                    <span
                                                        class="text-primary font-semibold text-sm"
                                                    >
                                                        {{
                                                            (
                                                                assignment.user
                                                                    ?.name ||
                                                                "Karyawan #" +
                                                                    assignment.user_id
                                                            )
                                                                .charAt(0)
                                                                .toUpperCase()
                                                        }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <p
                                                        class="font-semibold text-text"
                                                    >
                                                        {{
                                                            assignment.user
                                                                ?.name ||
                                                            "Karyawan #" +
                                                                assignment.user_id
                                                        }}
                                                    </p>
                                                    <p
                                                        class="text-xs text-muted"
                                                    >
                                                        {{
                                                            assignment.user
                                                                ?.email
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-2 h-2 rounded-full mr-3"
                                                    :style="{
                                                        backgroundColor:
                                                            assignment.shift
                                                                ?.color_code ||
                                                            '#3B82F6',
                                                    }"
                                                ></div>
                                                <div>
                                                    <p
                                                        class="font-medium text-text"
                                                    >
                                                        {{
                                                            assignment.shift
                                                                ?.name || "-"
                                                        }}
                                                    </p>
                                                    <p
                                                        class="text-xs text-muted"
                                                    >
                                                        {{
                                                            formatTimeRange(
                                                                assignment.shift ||
                                                                    {}
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm">
                                                <p
                                                    class="font-medium text-text"
                                                >
                                                    {{
                                                        formatDate(
                                                            assignment.start_date
                                                        )
                                                    }}
                                                </p>
                                                <p class="text-muted">
                                                    {{
                                                        assignment.end_date
                                                            ? "s/d " +
                                                              formatDate(
                                                                  assignment.end_date
                                                              )
                                                            : "Berjalan"
                                                    }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div
                                                class="text-sm text-muted max-w-xs truncate"
                                                :title="assignment.notes"
                                            >
                                                {{ assignment.notes || "-" }}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-if="employeeShifts.links"
                            class="flex flex-wrap gap-2 pt-4 justify-center"
                        >
                            <Link
                                v-for="(link, index) in employeeShifts.links"
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
                    <Card>
                        <div class="p-4 space-y-3">
                            <h3 class="text-lg font-semibold text-text">
                                Permintaan Tukar Shift
                            </h3>
                            <input
                                v-model="swapForm.requester_id"
                                type="number"
                                min="1"
                                placeholder="ID Pemohon"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                            <input
                                v-model="swapForm.target_id"
                                type="number"
                                min="1"
                                placeholder="ID Target"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                            <input
                                v-model="swapForm.date"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                            <Button
                                class="w-full"
                                :disabled="swapForm.processing"
                                @click="submitSwap"
                            >
                                {{
                                    swapForm.processing
                                        ? "Memproses..."
                                        : "Validasi Tukar"
                                }}
                            </Button>
                            <div
                                v-if="Object.keys(swapForm.errors).length"
                                class="text-xs text-danger"
                            >
                                <div
                                    v-for="(error, key) in swapForm.errors"
                                    :key="key"
                                >
                                    {{ error }}
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
