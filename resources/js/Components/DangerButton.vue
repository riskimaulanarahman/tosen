<template>
    <button
        class="inline-flex items-center justify-center rounded-lg border border-transparent bg-error px-4 py-2 text-sm font-semibold text-white transition duration-fast ease-in-out hover:bg-error/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-error focus-visible:ring-offset-2 focus-visible:ring-offset-surface-1 active:bg-error/80 disabled:cursor-not-allowed disabled:opacity-60"
        v-bind="$attrs"
    >
        <slot />
    </button>
</template>
