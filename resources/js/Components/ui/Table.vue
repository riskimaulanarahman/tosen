<script setup lang="ts">
import { computed } from "vue";

interface Column {
    key: string;
    label: string;
    sortable?: boolean;
    align?: "left" | "center" | "right";
    width?: string;
}

interface Props {
    columns: Column[];
    data: any[];
    loading?: boolean;
    striped?: boolean;
    hoverable?: boolean;
    bordered?: boolean;
    compact?: boolean;
    emptyMessage?: string;
    sortBy?: string;
    sortDirection?: "asc" | "desc";
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    striped: true,
    hoverable: true,
    bordered: false,
    compact: false,
    emptyMessage: "No data available",
    sortBy: "",
    sortDirection: "asc",
});

const emit = defineEmits<{
    sort: [column: string, direction: "asc" | "desc"];
    rowClick: [row: any, index: number];
}>();

const tableClasses = computed(() => {
    const base = "w-full border-collapse";

    const modifiers = [
        props.striped ? "table-striped" : "",
        props.hoverable ? "table-hover" : "",
        props.bordered ? "table-bordered" : "",
        props.compact ? "table-compact" : "",
    ]
        .filter(Boolean)
        .join(" ");

    return [base, modifiers].join(" ");
});

const headerClasses = computed(() => {
    const base = "bg-surface-2 text-text font-semibold";
    return base;
});

const cellClasses = computed(() => {
    return (column: Column, index: number) => {
        const base = "px-4 py-3 border-b border-border";

        const alignment = {
            left: "text-left",
            center: "text-center",
            right: "text-right",
        }[column.align || "left"];

        const width = column.width ? `w-[${column.width}]` : "";

        const striping =
            props.striped && index % 2 === 1 ? "bg-surface-1" : "bg-canvas";

        const hover = props.hoverable
            ? "hover:bg-surface-2 transition-colors duration-fast"
            : "";

        return [base, alignment, width, striping, hover]
            .filter(Boolean)
            .join(" ");
    };
});

const handleSort = (column: Column) => {
    if (!column.sortable) return;

    let newDirection: "asc" | "desc" = "asc";
    if (props.sortBy === column.key) {
        newDirection = props.sortDirection === "asc" ? "desc" : "asc";
    }

    emit("sort", column.key, newDirection);
};

const handleRowClick = (row: any, index: number) => {
    emit("rowClick", row, index);
};

const getSortIcon = (column: Column) => {
    if (props.sortBy !== column.key || !column.sortable) {
        return "M7 11l5-5m0 0l5 5m-5-5v12";
    }

    return props.sortDirection === "asc" ? "M5 15l7-7 7 7" : "M19 9l-7 7-7-7";
};

const getValue = (row: any, key: string) => {
    const keys = key.split(".");
    let value = row;

    for (const k of keys) {
        value = value?.[k];
        if (value === undefined) break;
    }

    return value ?? "";
};
</script>

<template>
    <div class="overflow-x-auto">
        <table :class="tableClasses">
            <!-- Header -->
            <thead :class="headerClasses">
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        :class="[
                            'px-4 py-3 border-b border-border font-semibold text-left',
                            column.align === 'center' ? 'text-center' : '',
                            column.align === 'right' ? 'text-right' : '',
                            column.sortable
                                ? 'cursor-pointer hover:bg-surface-3'
                                : '',
                            column.width ? `w-[${column.width}]` : '',
                        ]"
                        @click="handleSort(column)"
                    >
                        <div class="flex items-center gap-2">
                            {{ column.label }}

                            <svg
                                v-if="column.sortable"
                                class="h-4 w-4 transition-transform duration-fast"
                                :class="{
                                    'rotate-180':
                                        sortBy === column.key &&
                                        sortDirection === 'desc',
                                }"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 15l7-7 7 7"
                                />
                            </svg>
                        </div>
                    </th>
                </tr>
            </thead>

            <!-- Body -->
            <tbody>
                <!-- Loading State -->
                <tr v-if="loading">
                    <td
                        :colspan="columns.length"
                        class="px-4 py-8 text-center text-text-muted"
                    >
                        <div class="flex items-center justify-center gap-2">
                            <svg
                                class="animate-spin h-5 w-5"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            Loading...
                        </div>
                    </td>
                </tr>

                <!-- Empty State -->
                <tr v-else-if="data.length === 0">
                    <td
                        :colspan="columns.length"
                        class="px-4 py-8 text-center text-text-muted"
                    >
                        {{ emptyMessage }}
                    </td>
                </tr>

                <!-- Data Rows -->
                <tr
                    v-else
                    v-for="(row, index) in data"
                    :key="index"
                    :class="[
                        ...columns.map((_, colIndex) => cellClasses(_, index)),
                        hoverable ? 'cursor-pointer' : '',
                    ]"
                    @click="handleRowClick(row, index)"
                >
                    <td
                        v-for="column in columns"
                        :key="column.key"
                        :class="[
                            'px-4 py-3 border-b border-border',
                            column.align === 'center' ? 'text-center' : '',
                            column.align === 'right' ? 'text-right' : '',
                            striped && index % 2 === 1
                                ? 'bg-surface-1'
                                : 'bg-canvas',
                            hoverable
                                ? 'hover:bg-surface-2 transition-colors duration-fast'
                                : '',
                        ]"
                    >
                        <slot
                            :name="`cell-${column.key}`"
                            :row="row"
                            :value="getValue(row, column.key)"
                            :column="column"
                            :index="index"
                        >
                            {{ getValue(row, column.key) }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
.table-striped tbody tr:nth-child(even) {
    @apply bg-surface-1;
}

.table-hover tbody tr:hover {
    @apply bg-surface-2;
}

.table-bordered {
    @apply border border-border;
}

.table-bordered th,
.table-bordered td {
    @apply border border-border;
}

.table-compact th,
.table-compact td {
    @apply px-2 py-2;
}
</style>
