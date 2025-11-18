<script setup>
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    attendance: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["close"]);

const activeTab = ref("checkin");

const downloadImage = (url, filename) => {
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    link.target = "_blank";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const getDownloadFilename = (type, attendance) => {
    const date = new Date(attendance.created_at).toISOString().split("T")[0];
    const employeeName = attendance.user.name.replace(/\s+/g, "_");
    return `${type}_${employeeName}_${date}.jpg`;
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="4xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-text">
                    Foto Selfie - {{ attendance.user.name }}
                </h3>
                <button
                    @click="emit('close')"
                    class="text-text-3 hover:text-text transition-colors"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Tabs -->
            <div class="flex space-x-1 mb-6 bg-surface-2 p-1 rounded-lg">
                <button
                    @click="activeTab = 'checkin'"
                    :class="[
                        'flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors',
                        activeTab === 'checkin'
                            ? 'bg-primary text-white'
                            : 'text-text-3 hover:text-text',
                    ]"
                >
                    Check-in
                </button>
                <button
                    @click="activeTab = 'checkout'"
                    :class="[
                        'flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors',
                        activeTab === 'checkout'
                            ? 'bg-primary text-white'
                            : 'text-text-3 hover:text-text',
                    ]"
                >
                    Check-out
                </button>
            </div>

            <!-- Content -->
            <div class="space-y-6">
                <!-- Check-in Tab -->
                <div v-if="activeTab === 'checkin'" class="space-y-4">
                    <div class="text-sm text-text-3">
                        <div class="font-medium">Waktu Check-in:</div>
                        <div>
                            {{
                                new Date(
                                    attendance.check_in_time
                                ).toLocaleString("id-ID")
                            }}
                        </div>
                    </div>

                    <div
                        v-if="attendance.check_in_selfie_url"
                        class="space-y-4"
                    >
                        <div class="text-sm text-text-3">
                            <div class="font-medium">Ukuran File:</div>
                            <div>
                                {{ attendance.check_in_file_size_formatted }}
                            </div>
                        </div>

                        <div class="relative">
                            <img
                                :src="attendance.check_in_selfie_url"
                                alt="Check-in Selfie"
                                class="w-full max-w-2xl mx-auto rounded-lg shadow-lg"
                            />
                        </div>

                        <div class="flex justify-center space-x-4">
                            <button
                                @click="
                                    downloadImage(
                                        attendance.check_in_selfie_url,
                                        getDownloadFilename(
                                            'checkin',
                                            attendance
                                        )
                                    )
                                "
                                class="flex items-center space-x-2 px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 transition-colors"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                    />
                                </svg>
                                <span>Download Foto</span>
                            </button>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-text-3">
                        <svg
                            class="w-12 h-12 mx-auto mb-4 text-muted"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <div>Tidak ada foto check-in</div>
                    </div>
                </div>

                <!-- Check-out Tab -->
                <div v-if="activeTab === 'checkout'" class="space-y-4">
                    <div class="text-sm text-text-3">
                        <div class="font-medium">Waktu Check-out:</div>
                        <div v-if="attendance.check_out_time">
                            {{
                                new Date(
                                    attendance.check_out_time
                                ).toLocaleString("id-ID")
                            }}
                        </div>
                        <div v-else class="text-warning">Belum check-out</div>
                    </div>

                    <div
                        v-if="attendance.check_out_selfie_url"
                        class="space-y-4"
                    >
                        <div class="text-sm text-text-3">
                            <div class="font-medium">Ukuran File:</div>
                            <div>
                                {{ attendance.check_out_file_size_formatted }}
                            </div>
                        </div>

                        <div class="relative">
                            <img
                                :src="attendance.check_out_selfie_url"
                                alt="Check-out Selfie"
                                class="w-full max-w-2xl mx-auto rounded-lg shadow-lg"
                            />
                        </div>

                        <div class="flex justify-center space-x-4">
                            <button
                                @click="
                                    downloadImage(
                                        attendance.check_out_selfie_url,
                                        getDownloadFilename(
                                            'checkout',
                                            attendance
                                        )
                                    )
                                "
                                class="flex items-center space-x-2 px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 transition-colors"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                    />
                                </svg>
                                <span>Download Foto</span>
                            </button>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-text-3">
                        <svg
                            class="w-12 h-12 mx-auto mb-4 text-muted"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <div>Tidak ada foto check-out</div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
