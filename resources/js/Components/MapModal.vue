<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import Modal from "./Modal.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    outlet: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["close"]);

const mapContainer = ref(null);
const map = ref(null);
const marker = ref(null);

// Generate Google Maps URL
const googleMapsUrl = computed(() => {
    if (!props.outlet.latitude || !props.outlet.longitude) {
        return "#";
    }
    return `https://www.google.com/maps?q=${props.outlet.latitude},${props.outlet.longitude}`;
});

// Initialize Leaflet map
const initializeMap = () => {
    if (
        !mapContainer.value ||
        !props.outlet.latitude ||
        !props.outlet.longitude
    ) {
        return;
    }

    // Import Leaflet dynamically
    import("leaflet").then((L) => {
        // Fix for default marker icon
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconRetinaUrl:
                "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png",
            iconUrl:
                "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png",
            shadowUrl:
                "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png",
        });

        // Initialize map
        map.value = L.map(mapContainer.value).setView(
            [props.outlet.latitude, props.outlet.longitude],
            15
        );

        // Add tile layer
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "© OpenStreetMap contributors",
        }).addTo(map.value);

        // Add marker
        marker.value = L.marker([
            props.outlet.latitude,
            props.outlet.longitude,
        ]).addTo(map.value);

        // Add popup with outlet information
        const popupContent = `
            <div class="text-sm">
                <strong>${props.outlet.name}</strong><br>
                ${props.outlet.address || "Alamat tidak tersedia"}<br>
                Radius: ${props.outlet.radius || 0}m
            </div>
        `;
        marker.value.bindPopup(popupContent).openPopup();
    });
};

// Clean up map
const cleanupMap = () => {
    if (map.value) {
        map.value.remove();
        map.value = null;
        marker.value = null;
    }
};

// Watch for modal show/hide
watch(
    () => props.show,
    (newShow) => {
        if (newShow) {
            // Initialize map when modal opens
            setTimeout(() => {
                initializeMap();
            }, 100);
        } else {
            // Clean up map when modal closes
            cleanupMap();
        }
    }
);

onUnmounted(() => {
    cleanupMap();
});
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="4xl">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-text">
                        Peta Lokasi Outlet
                    </h3>
                    <p class="text-sm text-muted mt-1">
                        {{ outlet.name }}
                    </p>
                </div>
                <button
                    @click="emit('close')"
                    class="text-muted hover:text-text transition-colors"
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

            <!-- Outlet Info -->
            <div class="bg-surface-2 rounded-lg p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-text">Nama Outlet</p>
                        <p class="text-sm text-muted">{{ outlet.name }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-text">Alamat</p>
                        <p class="text-sm text-muted">
                            {{ outlet.address || "Tidak tersedia" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-text">Koordinat</p>
                        <p class="text-sm text-muted">
                            {{ outlet.latitude }}, {{ outlet.longitude }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-text">Radius</p>
                        <p class="text-sm text-muted">
                            {{ outlet.radius || 0 }} meter
                        </p>
                    </div>
                </div>
            </div>

            <!-- Map Container -->
            <div class="mb-4">
                <div
                    ref="mapContainer"
                    class="w-full h-96 rounded-lg border border-border bg-surface-2"
                ></div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3">
                <a
                    :href="googleMapsUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <svg
                        class="w-4 h-4 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                    Buka di Google Maps
                </a>
                <button
                    @click="emit('close')"
                    class="px-4 py-2 bg-surface-3 hover:bg-surface-4 text-text text-sm font-medium rounded-lg transition-colors"
                >
                    Tutup
                </button>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
@import "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css";
</style>
