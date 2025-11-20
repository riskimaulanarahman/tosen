<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-muted mb-2">
                Default Gaji Pokok (per bulan)
            </label>
            <input
                v-model.number="model.default_salary"
                type="number"
                min="0"
                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                placeholder="Contoh: 2000000"
            />
            <p class="text-xs text-muted mt-1">
                Dipakai jika karyawan tidak memiliki base salary.
            </p>
            <div v-if="errors?.default_salary" class="mt-1 text-xs text-danger">
                {{ errors.default_salary }}
            </div>
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center">
                <input v-model="model.use_tax" type="checkbox" class="mr-2" />
                <span class="text-sm text-text">Terapkan pajak 5% pada payroll</span>
            </label>
        </div>
    </div>
</template>

<script setup>
const model = defineModel({ type: Object, required: true });
const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
});
</script>
