<script setup>
import { computed } from "vue";

const props = defineProps({
    outlet: {
        type: Object,
        required: true,
    },
    showDetails: {
        type: Boolean,
        default: false,
    },
});

const statusData = computed(() => {
    return (
        props.outlet.operational_status || {
            status: "unknown",
            text: "Status tidak diketahui",
            color: "gray",
            time: "N/A",
        }
    );
});

const statusColorClass = computed(() => {
    const colorMap = {
        open: "bg-green-500",
        closed: "bg-red-500",
        warning: "bg-yellow-500",
        unknown: "bg-gray-500",
    };
    return colorMap[statusData.value.status] || "bg-gray-500";
});

const statusTextColorClass = computed(() => {
    const colorMap = {
        open: "text-green-400",
        closed: "text-red-400",
        warning: "text-yellow-400",
        unknown: "text-gray-400",
    };
    return colorMap[statusData.value.status] || "text-gray-400";
});

const statusBgClass = computed(() => {
    const colorMap = {
        open: "bg-green-900/20 border-green-600",
        closed: "bg-red-900/20 border-red-600",
        warning: "bg-yellow-900/20 border-yellow-600",
        unknown: "bg-gray-900/20 border-gray-600",
    };
    return (
        colorMap[statusData.value.status] || "bg-gray-900/20 border-gray-600"
    );
});

const formattedWorkDays = computed(() => {
    return props.outlet.formatted_work_days || "Not set";
});

const toleranceSettings = computed(() => {
    return (
        props.outlet.tolerance_settings || {
            grace_period: 5,
            late_tolerance: 15,
            early_checkout_tolerance: 10,
            overtime_threshold: 60,
        }
    );
});
</script>

<template>
    <!-- Simple Status Indicator -->
    <div v-if="!showDetails" class="flex items-center space-x-2">
        <div class="w-3 h-3 rounded-full" :class="statusColorClass"></div>
        <span class="text-sm font-medium" :class="statusTextColorClass">
            {{ statusData.text }}
        </span>
        <span class="text-sm text-gray-400">
            {{ statusData.time }}
        </span>
    </div>

    <!-- Detailed Status Card -->
    <div v-else class="space-y-4">
        <!-- Main Status -->
        <div
            class="flex items-center justify-between p-4 rounded-lg border"
            :class="statusBgClass"
        >
            <div class="flex items-center space-x-3">
                <div
                    class="w-4 h-4 rounded-full animate-pulse"
                    :class="statusColorClass"
                ></div>
                <div>
                    <div
                        class="font-semibold text-lg"
                        :class="statusTextColorClass"
                    >
                        {{ statusData.text }}
                    </div>
                    <div class="text-sm text-gray-400">
                        {{ statusData.time }}
                    </div>
                </div>
            </div>

            <div class="text-right">
                <div class="text-sm text-gray-400">Status</div>
                <div
                    class="text-xs font-medium uppercase tracking-wider"
                    :class="statusTextColorClass"
                >
                    {{ statusData.status }}
                </div>
            </div>
        </div>

        <!-- Operational Hours -->
        <div class="bg-gray-800 p-4 rounded-lg border border-gray-700">
            <h3 class="text-sm font-semibold text-gray-300 mb-2">
                Jam Operasional
            </h3>
            <div class="space-y-1">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Waktu:</span>
                    <span class="text-gray-200">{{ statusData.time }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Hari Kerja:</span>
                    <span class="text-gray-200">{{ formattedWorkDays }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Timezone:</span>
                    <span class="text-gray-200">{{
                        outlet.timezone || "Asia/Jakarta"
                    }}</span>
                </div>
                <!-- Additional info for closed status -->
                <div
                    v-if="
                        statusData.status === 'closed' &&
                        statusData.next_open_time
                    "
                    class="pt-2 border-t border-gray-600"
                >
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Buka Kembali:</span>
                        <span class="text-gray-200">
                            {{ statusData.next_open_day }}
                            {{ statusData.next_open_time }}
                        </span>
                    </div>
                    <div
                        v-if="statusData.time_until_next"
                        class="flex justify-between text-sm"
                    >
                        <span class="text-gray-400">Dalam:</span>
                        <span class="text-yellow-400 font-medium">
                            {{ statusData.time_until_next }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tolerance Settings -->
        <div class="bg-gray-800 p-4 rounded-lg border border-gray-700">
            <h3 class="text-sm font-semibold text-gray-300 mb-2">
                Pengaturan Toleransi
            </h3>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400">Grace Period:</span>
                    <span class="text-gray-200"
                        >{{ toleranceSettings.grace_period }} menit</span
                    >
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Toleransi Terlambat:</span>
                    <span class="text-gray-200"
                        >{{ toleranceSettings.late_tolerance }} menit</span
                    >
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Toleransi Checkout Awal:</span>
                    <span class="text-gray-200"
                        >{{
                            toleranceSettings.early_checkout_tolerance
                        }}
                        menit</span
                    >
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Threshold Lembur:</span>
                    <span class="text-gray-200"
                        >{{ toleranceSettings.overtime_threshold }} menit</span
                    >
                </div>
            </div>
        </div>
    </div>
</template>
