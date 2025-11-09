<template>
    <button
        class="inline-flex items-center justify-center rounded-lg border border-transparent bg-primary px-4 py-2 text-sm font-semibold tracking-wide text-white transition duration-fast ease-in-out hover:bg-primary-600 active:bg-primary-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface-1 disabled:cursor-not-allowed disabled:opacity-60"
        v-bind="$attrs"
    >
        <slot />
    </button>
</template>
