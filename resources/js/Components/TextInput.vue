<script setup>
import { onMounted, ref, useAttrs } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);
const attrs = useAttrs();

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        v-bind="attrs"
        :class="[
            'w-full rounded-lg border border-border bg-surface-2 px-3 py-2 text-text placeholder-text-muted shadow-sm transition duration-fast focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-400 disabled:cursor-not-allowed disabled:opacity-60',
            attrs.class,
        ]"
        v-model="model"
        ref="input"
    />
</template>
