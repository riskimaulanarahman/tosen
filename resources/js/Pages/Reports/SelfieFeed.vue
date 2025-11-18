<script setup>
import { ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";

const props = defineProps({
    attendances: Object,
    outlets: Array,
    stats: Object,
    filters: Object,
});

const filterState = ref({
    outlet_id: props.filters.outlet_id || "",
    date: props.filters.date || "",
    search: props.filters.search || "",
});

const applyFilters = () => {
    router.get(
        route("reports.selfies"),
        { ...filterState.value },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const clearFilters = () => {
    filterState.value = {
        outlet_id: "",
        date: "",
        search: "",
    };
    applyFilters();
};

const goToFullReport = () => {
    router.get(
        route("reports.index"),
        { ...filterState.value },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const formatDateTime = (value) => {
    if (!value) return "-";
    return new Date(value).toLocaleString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const downloadImage = (url) => {
    if (!url) return;
    const link = document.createElement("a");
    link.href = url;
    link.target = "_blank";
    link.rel = "noopener";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Head title="Selfie Karyawan" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        Review Selfie Karyawan
                    </h1>
                    <p class="text-muted">
                        Mode sederhana untuk memeriksa foto check-in dan
                        check-out
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Button @click="goToFullReport" variant="secondary">
                        Kembali ke Laporan Lengkap
                    </Button>
                </div>
            </div>

            <Card>
                <div class="grid md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm text-text-3 mb-2"
                            >Outlet</label
                        >
                        <select
                            v-model="filterState.outlet_id"
                            class="w-full rounded-lg border border-border bg-surface-2 text-text focus:ring-primary"
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
                        <label class="block text-sm text-text-3 mb-2"
                            >Tanggal</label
                        >
                        <input
                            type="date"
                            v-model="filterState.date"
                            class="w-full rounded-lg border border-border bg-surface-2 text-text focus:ring-primary"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-text-3 mb-2"
                            >Cari Karyawan</label
                        >
                        <input
                            type="text"
                            placeholder="Nama atau email"
                            v-model="filterState.search"
                            class="w-full rounded-lg border border-border bg-surface-2 text-text focus:ring-primary"
                        />
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                    <Button variant="primary" @click="applyFilters">
                        Terapkan Filter
                    </Button>
                    <Button variant="ghost" @click="clearFilters">
                        Reset
                    </Button>
                </div>
            </Card>

            <div class="grid md:grid-cols-4 gap-4">
                <Card variant="gradient" hover>
                    <p class="text-sm text-text-3">Total Catatan</p>
                    <p class="text-2xl font-semibold text-text">
                        {{ stats.total_records }}
                    </p>
                </Card>
                <Card variant="gradient" hover>
                    <p class="text-sm text-text-3">Dengan Selfie Check-in</p>
                    <p class="text-2xl font-semibold text-text">
                        {{ stats.with_check_in_selfie }}
                    </p>
                </Card>
                <Card variant="gradient" hover>
                    <p class="text-sm text-text-3">Dengan Selfie Check-out</p>
                    <p class="text-2xl font-semibold text-text">
                        {{ stats.with_check_out_selfie }}
                    </p>
                </Card>
                <Card variant="gradient" hover>
                    <p class="text-sm text-text-3">Lengkap (Check-in & out)</p>
                    <p class="text-2xl font-semibold text-text">
                        {{ stats.with_both }}
                    </p>
                </Card>
            </div>

            <Card>
                <template v-if="attendances.data.length">
                    <div
                        class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <Card
                            v-for="attendance in attendances.data"
                            :key="attendance.id"
                            variant="bordered"
                            hover
                        >
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-text">
                                        {{ attendance.user.name }}
                                    </h3>
                                    <p class="text-sm text-text-3">
                                        {{ attendance.user.email }}
                                    </p>
                                    <p class="text-xs text-muted">
                                        {{ attendance.user.outlet || "Tidak ada outlet" }}
                                    </p>
                                </div>
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-semibold"
                                    :class="[
                                        attendance.selfie_deletion_status.color ===
                                        'danger'
                                            ? 'bg-error-100 text-error-600'
                                            : 'bg-success-100 text-success-700',
                                    ]"
                                >
                                    {{ attendance.selfie_deletion_status.text }}
                                </span>
                            </div>

                            <div class="space-y-2 text-sm text-text-3 mb-4">
                                <div class="flex justify-between">
                                    <span>Check-in</span>
                                    <span class="text-text">{{
                                        formatDateTime(attendance.check_in_time)
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Check-out</span>
                                    <span class="text-text">{{
                                        formatDateTime(
                                            attendance.check_out_time
                                        )
                                    }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div class="bg-surface-2 rounded-lg p-3 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-text"
                                            >Selfie Check-in</span
                                        >
                                        <span
                                            class="text-xs text-text-3"
                                        >
                                            {{
                                                attendance.check_in_file_size_formatted
                                            }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="attendance.check_in_selfie_url"
                                        class="rounded-lg overflow-hidden border border-border"
                                    >
                                        <img
                                            :src="
                                                attendance.check_in_thumbnail_url ||
                                                attendance.check_in_selfie_url
                                            "
                                            class="w-full h-48 object-cover"
                                            alt="Check-in selfie"
                                        />
                                    </div>
                                    <p
                                        v-else
                                        class="text-sm text-text-3 text-center py-6"
                                    >
                                        Tidak ada selfie check-in
                                    </p>
                                    <div class="flex justify-end">
                                        <Button
                                            v-if="attendance.check_in_selfie_url"
                                            variant="secondary"
                                            size="sm"
                                            @click="
                                                downloadImage(
                                                    attendance.check_in_selfie_url
                                                )
                                            "
                                        >
                                            Lihat Foto
                                        </Button>
                                    </div>
                                </div>

                                <div class="bg-surface-2 rounded-lg p-3 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-text"
                                            >Selfie Check-out</span
                                        >
                                        <span class="text-xs text-text-3">
                                            {{
                                                attendance.check_out_file_size_formatted
                                            }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="attendance.check_out_selfie_url"
                                        class="rounded-lg overflow-hidden border border-border"
                                    >
                                        <img
                                            :src="
                                                attendance.check_out_thumbnail_url ||
                                                attendance.check_out_selfie_url
                                            "
                                            class="w-full h-48 object-cover"
                                            alt="Check-out selfie"
                                        />
                                    </div>
                                    <p
                                        v-else
                                        class="text-sm text-text-3 text-center py-6"
                                    >
                                        Tidak ada selfie check-out
                                    </p>
                                    <div class="flex justify-end">
                                        <Button
                                            v-if="attendance.check_out_selfie_url"
                                            variant="secondary"
                                            size="sm"
                                            @click="
                                                downloadImage(
                                                    attendance.check_out_selfie_url
                                                )
                                            "
                                        >
                                            Lihat Foto
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <div v-if="attendances.links" class="mt-6">
                        <template
                            v-for="(link, key) in attendances.links"
                            :key="key"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-2 text-sm border rounded-none',
                                    link.active
                                        ? 'bg-primary text-white border-primary'
                                        : 'bg-surface-1 text-text border-border hover:bg-surface-2',
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
                </template>

                <div v-else class="py-16 text-center">
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
                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M4 6h4l2-3h4l2 3h4v12H4z"
                        />
                    </svg>
                    <p class="mt-4 text-muted">
                        Belum ada data selfie untuk filter yang dipilih.
                    </p>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
