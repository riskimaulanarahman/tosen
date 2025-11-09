<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-canvas pt-6 text-text sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-primary" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden rounded-2xl border border-border/70 bg-surface-1 px-6 py-6 shadow-lg sm:max-w-md"
        >
            <slot />
        </div>
    </div>
</template>
