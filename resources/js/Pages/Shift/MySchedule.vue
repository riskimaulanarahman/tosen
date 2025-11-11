<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

const props = defineProps({
    currentAssignment: {
        type: Object,
        default: () => null,
    },
    history: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    weeklyPreview: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const breadcrumbItems = [
    { name: "Dashboard", href: route("dashboard") },
    { name: "Jadwal Saya" },
];

const historyEntries = computed(() => props.history?.data ?? []);
const historyLinks = computed(() => props.history?.links ?? []);

const formatDate = (value) => {
    if (!value) return "-";
    return new Date(value).toLocaleDateString("id-ID", {
        weekday: "long",
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const formatTime = (value) => {
    if (!value) return "--:--";
    return value.length > 5 ? value.slice(0, 5) : value;
};

const getStatusBadge = (assignment) => {
    if (!assignment) return "bg-gray-100 text-gray-600";
    return assignment.is_active
        ? "bg-success-100 text-success-800"
        : "bg-gray-100 text-gray-600";
};
</script>

<template>
    <Head title="Jadwal Saya" />

    <AuthenticatedLayout>
        <template #breadcrumb>
            <Breadcrumb :items="breadcrumbItems" />
        </template>

        <div class="space-y-6">
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <h1 class="text-3xl font-bold text-text">Jadwal Saya</h1>
                    <p class="text-muted">
                        Lihat shift aktif dan riwayat penugasan Anda
                    </p>
                </div>
                <Link :href="route('attendance.index')">
                    <Button variant="secondary">Buka Halaman Absensi</Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <Card>
                    <div class="p-5 space-y-2">
                        <p class="text-sm text-muted">Total Penugasan</p>
                        <p class="text-3xl font-bold text-text">
                            {{ stats.total_assignments || 0 }}
                        </p>
                    </div>
                </Card>
                <Card>
                    <div class="p-5 space-y-2">
                        <p class="text-sm text-muted">Shift Aktif</p>
                        <p class="text-3xl font-bold text-primary">
                            {{ stats.current_shift_name || "Belum ada" }}
                        </p>
                    </div>
                </Card>
                <Card>
                    <div class="p-5 space-y-2">
                        <p class="text-sm text-muted">Aktif Sejak</p>
                        <p class="text-3xl font-bold text-text">
                            {{
                                stats.active_since
                                    ? new Date(
                                          stats.active_since
                                      ).toLocaleDateString("id-ID")
                                    : "-"
                            }}
                        </p>
                    </div>
                </Card>
            </div>

            <Card>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-text">
                                Shift Saat Ini
                            </h2>
                            <p class="text-sm text-muted">
                                Informasi penugasan terbaru
                            </p>
                        </div>
                        <span
                            class="inline-flex px-3 py-1 rounded-full text-xs font-semibold"
                            :class="getStatusBadge(currentAssignment)"
                        >
                            {{
                                currentAssignment?.is_active
                                    ? "Sedang Aktif"
                                    : "Tidak Aktif"
                            }}
                        </span>
                    </div>

                    <div
                        v-if="currentAssignment"
                        class="grid grid-cols-1 md:grid-cols-3 gap-4"
                    >
                        <div class="bg-surface-2 rounded-lg p-4 border border-border">
                            <p class="text-sm text-muted">Shift</p>
                            <p class="text-lg font-semibold text-text">
                                {{ currentAssignment.shift?.name || "-" }}
                            </p>
                            <p class="text-sm text-muted">
                                {{
                                    formatTime(
                                        currentAssignment.shift?.start_time
                                    )
                                }}
                                -
                                {{
                                    formatTime(
                                        currentAssignment.shift?.end_time
                                    )
                                }}
                            </p>
                        </div>
                        <div class="bg-surface-2 rounded-lg p-4 border border-border">
                            <p class="text-sm text-muted">Mulai</p>
                            <p class="text-lg font-semibold text-text">
                                {{
                                    currentAssignment.start_date
                                        ? formatDate(
                                              currentAssignment.start_date
                                          )
                                        : "-"
                                }}
                            </p>
                        </div>
                        <div class="bg-surface-2 rounded-lg p-4 border border-border">
                            <p class="text-sm text-muted">Berakhir</p>
                            <p class="text-lg font-semibold text-text">
                                {{
                                    currentAssignment.end_date
                                        ? formatDate(
                                              currentAssignment.end_date
                                          )
                                        : "Berjalan"
                                }}
                            </p>
                        </div>
                    </div>
                    <div v-else class="text-center text-muted py-8">
                        Anda belum memiliki penugasan shift aktif.
                    </div>
                </div>
            </Card>

            <Card>
                <div class="p-6 space-y-4">
                    <h2 class="text-xl font-semibold text-text">
                        Jadwal 7 Hari ke Depan
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div
                            v-for="(item, index) in weeklyPreview"
                            :key="index"
                            class="border border-border rounded-lg p-4"
                            :class="{
                                'bg-primary-50 border-primary': item.is_today,
                            }"
                        >
                            <p
                                class="text-sm font-semibold"
                                :class="{
                                    'text-primary': item.is_today,
                                }"
                            >
                                {{ item.day_label }}
                            </p>
                            <p class="text-xs text-muted">
                                {{
                                    new Date(item.date).toLocaleDateString(
                                        "id-ID",
                                        {
                                            month: "short",
                                            day: "numeric",
                                        }
                                    )
                                }}
                            </p>
                            <div class="mt-3">
                                <p class="text-sm font-semibold text-text">
                                    {{ item.shift_name || "Tidak dijadwalkan" }}
                                </p>
                                <p class="text-xs text-muted">
                                    {{
                                        item.shift_name
                                            ? `${formatTime(
                                                  item.start_time
                                              )} - ${formatTime(
                                                  item.end_time
                                              )}`
                                            : "—"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <Card>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-text">
                                Riwayat Penugasan
                            </h2>
                            <p class="text-sm text-muted">
                                Riwayat shift yang pernah Anda jalani
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-muted">
                                    <th class="px-4 py-3">Shift</th>
                                    <th class="px-4 py-3">Periode</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-if="historyEntries.length === 0">
                                    <td
                                        colspan="4"
                                        class="px-4 py-6 text-center text-muted"
                                    >
                                        Belum ada catatan penugasan.
                                    </td>
                                </tr>
                                <tr
                                    v-for="assignment in historyEntries"
                                    :key="assignment.id"
                                >
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">
                                            {{ assignment.shift?.name || "-" }}
                                        </p>
                                        <p class="text-xs text-muted">
                                            {{
                                                formatTime(
                                                    assignment.shift?.start_time
                                                )
                                            }}
                                            -
                                            {{
                                                formatTime(
                                                    assignment.shift?.end_time
                                                )
                                            }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ formatDate(assignment.start_date) }}
                                        <span class="text-muted"> · </span>
                                        {{
                                            assignment.end_date
                                                ? formatDate(assignment.end_date)
                                                : "Berjalan"
                                        }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex px-2 py-1 rounded-full text-xs font-semibold"
                                            :class="
                                                assignment.is_active
                                                    ? 'bg-success-100 text-success-800'
                                                    : 'bg-gray-100 text-gray-600'
                                            "
                                        >
                                            {{
                                                assignment.is_active
                                                    ? "Aktif"
                                                    : "Selesai"
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-muted">
                                        {{ assignment.notes || "-" }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="historyLinks.length" class="flex flex-wrap gap-2">
                        <Link
                            v-for="(link, index) in historyLinks"
                            :key="index"
                            :href="link.url || '#'"
                            class="px-3 py-1 rounded border text-sm"
                            :class="[
                                link.active
                                    ? 'bg-primary text-white border-primary'
                                    : 'bg-surface-1 text-text border-border',
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
