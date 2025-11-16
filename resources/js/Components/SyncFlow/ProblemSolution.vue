<script setup lang="ts">
interface ProblemSolutionProps {
    variant?: "default" | "alternate";
}

const props = withDefaults(defineProps<ProblemSolutionProps>(), {
    variant: "default",
});
</script>

<template>
    <section class="syncflow-problem-solution py-20 lg:py-32">
        <div class="syncflow-container">
            <div class="text-center mb-16">
                <h2
                    class="syncflow-text-3xl lg:text-4xl font-bold text-syncflow-dark mb-6"
                >
                    Masalah Yang Sering Dihadapi Tim Anda
                </h2>
                <p class="syncflow-text-lg text-gray-600 max-w-3xl mx-auto">
                    Manajemen proyek yang tidak efektif dapat menyebabkan
                    kerugian waktu, biaya, dan produktivitas tim yang signifikan
                </p>
            </div>

            <div class="grid lg:grid-cols-2 gap-12 items-center mb-16">
                <!-- Problems Section -->
                <div class="space-y-6">
                    <h3
                        class="syncflow-text-2xl font-semibold text-syncflow-danger mb-6"
                    >
                        Tantangan Yang Menghambat Pertumbuhan Bisnis
                    </h3>

                    <div class="space-y-4">
                        <div
                            class="flex items-start space-x-4 p-4 bg-red-50 rounded-lg border-l-4 border-syncflow-danger"
                        >
                            <div class="flex-shrink-0">
                                <svg
                                    class="w-6 h-6 text-syncflow-danger mt-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <h4
                                    class="font-semibold text-syncflow-dark mb-1"
                                >
                                    Kehilangan 20+ Jam/Minggu untuk Update
                                    Status
                                </h4>
                                <p class="text-gray-600">
                                    Tim menghabiskan waktu berharga untuk
                                    meeting status update yang bisa diotomasi
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start space-x-4 p-4 bg-red-50 rounded-lg border-l-4 border-syncflow-danger"
                        >
                            <div class="flex-shrink-0">
                                <svg
                                    class="w-6 h-6 text-syncflow-danger mt-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <h4
                                    class="font-semibold text-syncflow-dark mb-1"
                                >
                                    30% Proyek Melewati Deadline
                                </h4>
                                <p class="text-gray-600">
                                    Kurangnya visibilitas progress dan
                                    perencanaan yang tidak akurat
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start space-x-4 p-4 bg-red-50 rounded-lg border-l-4 border-syncflow-danger"
                        >
                            <div class="flex-shrink-0">
                                <svg
                                    class="w-6 h-6 text-syncflow-danger mt-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <h4
                                    class="font-semibold text-syncflow-dark mb-1"
                                >
                                    Komunikasi Terfragmentasi
                                </h4>
                                <p class="text-gray-600">
                                    Informasi tersebar di email, chat, dan
                                    dokumen yang sulit dilacak
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start space-x-4 p-4 bg-red-50 rounded-lg border-l-4 border-syncflow-danger"
                        >
                            <div class="flex-shrink-0">
                                <svg
                                    class="w-6 h-6 text-syncflow-danger mt-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <h4
                                    class="font-semibold text-syncflow-dark mb-1"
                                >
                                    Laporan Manual yang Memakan Waktu
                                </h4>
                                <p class="text-gray-600">
                                    Manajemen menghabiskan berhari-hari untuk
                                    mengumpulkan data laporan proyek
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Solutions Section -->
                <div class="space-y-6">
                    <h3
                        class="syncflow-text-2xl font-semibold text-syncflow-success mb-6"
                    >
                        Solusi SyncFlow Untuk Transformasi Bisnis Anda
                    </h3>

                    <div class="space-y-4">
                        <div
                            class="flex items-start space-x-4 p-4 bg-green-50 rounded-lg border-l-4 border-syncflow-success"
                        >
                            <div class="flex-shrink-0">
                                <svg
                                    class="w-6 h-6 text-syncflow-success mt-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <h4
                                    class="font-semibold text-syncflow-dark mb-1"
                                >
                                    Automasi Status Real-Time
                                </h4>
                                <p class="text-gray-600">
                                    Dashboard interaktif yang update otomatis,
                                    hemat 15+ jam/minggu
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start space-x-4 p-4 bg-green-50 rounded-lg border-l-4 border-syncflow-success"
                        >
                            <div class="flex-shrink-0">
                                <svg
                                    class="w-6 h-6 text-syncflow-success mt-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <h4
                                    class="font-semibold text-syncflow-dark mb-1"
                                >
                                    95% Proyek Tepat Waktu
                                </h4>
                                <p class="text-gray-600">
                                    Perencanaan cerdas dengan prediksi
                                    bottleneck dan resource allocation
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start space-x-4 p-4 bg-green-50 rounded-lg border-l-4 border-syncflow-success"
                        >
                            <div class="flex-shrink-0">
                                <svg
                                    class="w-6 h-6 text-syncflow-success mt-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <h4
                                    class="font-semibold text-syncflow-dark mb-1"
                                >
                                    Kolaborasi Terpusat
                                </h4>
                                <p class="text-gray-600">
                                    Satu platform untuk semua komunikasi, file,
                                    dan tracking progress
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start space-x-4 p-4 bg-green-50 rounded-lg border-l-4 border-syncflow-success"
                        >
                            <div class="flex-shrink-0">
                                <svg
                                    class="w-6 h-6 text-syncflow-success mt-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <h4
                                    class="font-semibold text-syncflow-dark mb-1"
                                >
                                    Laporan Instan dengan 1 Klik
                                </h4>
                                <p class="text-gray-600">
                                    Generate laporan komprehensif untuk
                                    manajemen dalam hitungan detik
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Impact Statistics -->
            <div
                class="bg-gradient-to-r from-syncflow-primary to-syncflow-primary-dark rounded-2xl p-8 text-white"
            >
                <div class="text-center mb-8">
                    <h3 class="syncflow-text-2xl font-bold mb-4">
                        Dampak Nyata Setelah Implementasi SyncFlow
                    </h3>
                    <p class="text-white/90">
                        Data dari 500+ bisnis yang telah menggunakan SyncFlow
                    </p>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="syncflow-text-3xl font-bold mb-2">40%</div>
                        <div class="text-white/80">
                            Peningkatan Produktivitas
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="syncflow-text-3xl font-bold mb-2">60%</div>
                        <div class="text-white/80">
                            Pengurangan Meeting Status
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="syncflow-text-3xl font-bold mb-2">85%</div>
                        <div class="text-white/80">Kepuasan Tim Meningkat</div>
                    </div>
                    <div class="text-center">
                        <div class="syncflow-text-3xl font-bold mb-2">3x</div>
                        <div class="text-white/80">ROI dalam 6 Bulan</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.syncflow-problem-solution {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

@media (max-width: 768px) {
    .syncflow-problem-solution {
        padding: 3rem 0;
    }
}
</style>
