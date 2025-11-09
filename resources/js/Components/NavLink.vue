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
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-accent text-sm font-medium leading-5 text-text transition duration-fast ease-in-out focus-visible:outline-none focus-visible:text-text focus-visible:border-accent'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-text-2 hover:text-text hover:border-primary-400 focus-visible:outline-none focus-visible:text-text focus-visible:border-primary-400 transition duration-fast ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
