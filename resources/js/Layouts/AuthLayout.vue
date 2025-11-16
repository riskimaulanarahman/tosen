<script setup lang="ts">
import {
    getAppInitials,
    getAppName,
    getCurrentYear,
} from "@/utils/appInfo";

interface Props {
    title?: string;
    description?: string;
}

withDefaults(defineProps<Props>(), {
    title: "Selamat Datang",
    description: "Masuk ke akun Anda untuk melanjutkan",
});

const appName = getAppName();
const appInitials = getAppInitials(appName);
const currentYear = getCurrentYear();

const highlightCards = [
    {
        label: "Geo Attendance",
        description: "Validasi GPS, selfie, dan radius kehadiran sekaligus.",
    },
    {
        label: "Shift Otomatis",
        description: "Distribusikan jadwal lintas cabang tanpa spreadsheet.",
    },
    {
        label: "Insight Payroll",
        description: "Rekap absensi siap tarik ke perhitungan payroll.",
    },
    {
        label: "Alert Real-time",
        description: "Notifikasi anomali muncul langsung di dashboard.",
    },
];

const trustMetrics = [
    { value: "4K+", label: "Check-in harian" },
    { value: "120+", label: "Outlet aktif" },
    { value: "24/7", label: "Monitoring uptime" },
];
</script>

<template>
    <div
        class="min-h-screen bg-gradient-to-b from-surface-0 to-surface-1 text-text"
    >
        <div class="flex flex-col lg:flex-row min-h-screen">
            <section
                class="relative hidden lg:flex lg:w-1/2 overflow-hidden bg-gradient-to-br from-primary-700 via-primary-800 to-primary-900 p-12 text-white"
            >
                <div class="absolute inset-0 opacity-40">
                    <div
                        class="absolute -top-10 -left-10 w-64 h-64 bg-accent/30 blur-3xl rounded-full"
                    ></div>
                    <div
                        class="absolute bottom-0 right-0 w-72 h-72 bg-primary-500/30 blur-3xl rounded-full"
                    ></div>
                    <div
                        class="absolute inset-0 bg-gradient-to-b from-white/5 via-transparent to-white/10 mix-blend-soft-light"
                    ></div>
                </div>
                <div class="relative z-10 flex flex-col justify-center">
                    <div class="space-y-10 max-w-xl">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-16 h-16 rounded-2xl bg-white/10 border border-white/30 flex items-center justify-center text-2xl font-semibold tracking-wide"
                            >
                                {{ appInitials }}
                            </div>
                            <div>
                                <p
                                    class="text-sm uppercase tracking-[0.55em] text-white/60"
                                >
                                    Presence Suite
                                </p>
                                <p class="text-3xl font-bold leading-tight">
                                    {{ appName }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h2 class="text-4xl font-bold leading-snug">
                                Sistem absensi tepercaya untuk tim yang dinamis
                            </h2>
                            <p class="text-white/80 text-lg">
                                Kelola absensi, shift, payroll, dan operasional
                                dalam satu layar. {{ appName }} menjaga
                                produktivitas dan menghadirkan transparansi
                                real-time.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <article
                                v-for="card in highlightCards"
                                :key="card.label"
                                class="rounded-2xl border border-white/15 bg-white/5 backdrop-blur-md p-4"
                            >
                                <p
                                    class="text-xs uppercase tracking-[0.4em] text-white/70"
                                >
                                    {{ card.label }}
                                </p>
                                <p
                                    class="mt-2 text-sm text-white/90 leading-relaxed"
                                >
                                    {{ card.description }}
                                </p>
                            </article>
                        </div>

                        <div
                            class="grid grid-cols-3 gap-4 pt-6 border-t border-white/15"
                        >
                            <div
                                v-for="metric in trustMetrics"
                                :key="metric.label"
                            >
                                <p class="text-3xl font-bold">
                                    {{ metric.value }}
                                </p>
                                <p class="text-sm text-white/70">
                                    {{ metric.label }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-10"
            >
                <div class="w-full max-w-md space-y-6">
                    <div
                        class="lg:hidden flex items-center justify-center gap-4"
                    >
                        <div
                            class="w-14 h-14 rounded-2xl bg-primary-500/10 text-primary-600 flex items-center justify-center font-semibold"
                        >
                            {{ appInitials }}
                        </div>
                        <div class="text-center">
                            <p
                                class="text-xs uppercase tracking-[0.45em] text-text-3"
                            >
                                Presence Suite
                            </p>
                            <p class="text-2xl font-bold text-text">
                                {{ appName }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="bg-surface-0/90 backdrop-blur-sm rounded-3xl p-8 shadow-2xl border border-border"
                    >
                        <div class="text-center mb-8">
                            <p
                                class="text-xs font-semibold tracking-[0.5em] uppercase text-accent"
                            >
                                Secure Access
                            </p>
                            <h2 class="text-3xl font-bold text-text mt-3">
                                {{ title }}
                            </h2>
                            <p class="text-text-3 mt-2">
                                {{ description }}
                            </p>
                        </div>

                        <div>
                            <slot />
                        </div>
                    </div>

                    <div class="text-center">
                        <slot name="footer" />
                    </div>

                    <p class="text-center text-text-muted text-sm">
                        © {{ currentYear }} {{ appName }}. Seluruh hak cipta
                        dilindungi.
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
