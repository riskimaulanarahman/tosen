<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center rounded-lg border border-border bg-surface-2 px-4 py-2 text-sm font-medium text-text transition duration-fast ease-in-out hover:bg-surface-3 active:bg-surface-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface-1 disabled:cursor-not-allowed disabled:opacity-60"
    >
        <slot />
    </button>
</template>
