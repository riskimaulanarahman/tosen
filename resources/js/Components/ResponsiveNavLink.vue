<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-accent bg-surface-2 text-start text-base font-medium text-text transition duration-fast ease-in-out focus-visible:outline-none focus-visible:bg-surface-3 focus-visible:border-accent'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-text-2 hover:text-text hover:bg-surface-2 hover:border-primary-400 focus-visible:outline-none focus-visible:text-text focus-visible:bg-surface-2 focus-visible:border-primary-400 transition duration-fast ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
