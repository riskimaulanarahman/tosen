<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    items: {
        type: Array,
        required: true,
        default: () => [],
    },
});
</script>

<template>
    <nav class="flex items-center space-x-2 text-sm">
        <!-- Home icon for first item -->
        <template v-for="(item, index) in items" :key="index">
            <!-- Separator -->
            <span v-if="index > 0" class="text-gray-600">
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
                        d="M9 5l7 7-7 7"
                    />
                </svg>
            </span>

            <!-- Breadcrumb item -->
            <div>
                <!-- Link item -->
                <Link
                    v-if="item.href && index < items.length - 1"
                    :href="item.href"
                    class="text-gray-400 hover:text-orange-500 transition-colors duration-200"
                >
                    {{ item.name }}
                </Link>

                <!-- Current page (no link) -->
                <span
                    v-else
                    class="text-gray-100 font-medium"
                    :class="index === items.length - 1 ? 'text-orange-500' : ''"
                >
                    {{ item.name }}
                </span>
            </div>
        </template>
    </nav>
</template>

<style scoped>
/* Custom styling for breadcrumb separator */
nav > div > span {
    transform: rotate(0deg);
}
</style>
