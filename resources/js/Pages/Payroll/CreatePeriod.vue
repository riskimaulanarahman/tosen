<script setup>
import { computed, ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

const props = defineProps({
    employees: {
        type: Array,
        default: () => [],
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    payrollPeriods: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbItems = [
    { name: "Dashboard", href: route("dashboard") },
    { name: "Manajemen Penggajian", href: route("payroll.index") },
    { name: "Buat Payroll" },
];

const employeeFilter = ref("");
const outletFilter = ref("");

const filteredEmployees = computed(() => {
    return props.employees.filter((employee) => {
        const matchesOutlet =
            !outletFilter.value ||
            Number(employee.outlet_id) === Number(outletFilter.value);
        const matchesSearch =
            !employeeFilter.value ||
            employee.name
                .toLowerCase()
                .includes(employeeFilter.value.toLowerCase()) ||
            (employee.email || "")
                .toLowerCase()
                .includes(employeeFilter.value.toLowerCase());
        return matchesOutlet && matchesSearch;
    });
});

const createPeriodForm = useForm({
    name: "",
    start_date: "",
    end_date: "",
    basic_rate: 5000000,
    overtime_rate: 1.5,
    notes: "",
    user_ids: [],
});

const generatePayrollForm = useForm({
    payroll_period_id: "",
    start_date: "",
    end_date: "",
    user_ids: [],
});

const toggleEmployee = (id, targetForm = "create") => {
    const form =
        targetForm === "create" ? createPeriodForm : generatePayrollForm;
    if (form.user_ids.includes(id)) {
        form.user_ids = form.user_ids.filter((userId) => userId !== id);
    } else {
        form.user_ids = [...form.user_ids, id];
    }
};

const submitCreatePeriod = () => {
    createPeriodForm.post(route("payroll.create.period"), {
        preserveScroll: true,
        onSuccess: () => {
            createPeriodForm.reset();
            outletFilter.value = "";
            employeeFilter.value = "";
        },
    });
};

const submitPayrollGeneration = () => {
    generatePayrollForm.post(route("payroll.generate"), {
        preserveScroll: true,
        onSuccess: () => generatePayrollForm.reset(),
    });
};

const recentDraftPeriods = computed(() =>
    props.payrollPeriods.slice(0, 5)
);
</script>

<template>
    <Head title="Buat Payroll" />

    <AuthenticatedLayout>
        <template #breadcrumb>
            <Breadcrumb :items="breadcrumbItems" />
        </template>

        <div class="space-y-6">
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <h1 class="text-3xl font-bold text-text">
                        Pengaturan Periode Payroll
                    </h1>
                    <p class="text-muted">
                        Buat periode baru, pilih karyawan, dan jalankan proses
                        payroll terjadwal.
                    </p>
                </div>
                <Button variant="secondary" :href="route('payroll.index')">
                    Kembali ke daftar
                </Button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <div class="p-5 space-y-4">
                        <h2 class="text-lg font-semibold text-text">
                            Buat Periode Payroll
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input
                                v-model="createPeriodForm.name"
                                type="text"
                                placeholder="Nama periode"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                            <input
                                v-model="createPeriodForm.basic_rate"
                                type="number"
                                min="0"
                                placeholder="Basic rate"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                            <input
                                v-model="createPeriodForm.start_date"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                            <input
                                v-model="createPeriodForm.end_date"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                            <input
                                v-model="createPeriodForm.overtime_rate"
                                type="number"
                                min="1"
                                step="0.1"
                                placeholder="Overtime multiplier"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                            <textarea
                                v-model="createPeriodForm.notes"
                                rows="2"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg col-span-1 md:col-span-2"
                                placeholder="Catatan tambahan"
                            ></textarea>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <p class="text-muted">
                                Karyawan dipilih:
                                <strong>{{ createPeriodForm.user_ids.length }}</strong>
                            </p>
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="createPeriodForm.user_ids = []"
                            >
                                Hapus pilihan
                            </Button>
                        </div>
                        <Button
                            class="w-full"
                            :disabled="createPeriodForm.processing"
                            @click="submitCreatePeriod"
                        >
                            {{
                                createPeriodForm.processing
                                    ? "Menyimpan..."
                                    : "Buat Periode"
                            }}
                        </Button>
                        <div
                            v-if="Object.keys(createPeriodForm.errors).length"
                            class="text-xs text-danger"
                        >
                            <div
                                v-for="(error, key) in createPeriodForm.errors"
                                :key="key"
                            >
                                {{ error }}
                            </div>
                        </div>
                    </div>
                </Card>

                <Card>
                    <div class="p-5 space-y-4">
                        <h2 class="text-lg font-semibold text-text">
                            Generate Payroll
                        </h2>
                        <div class="space-y-3">
                            <select
                                v-model="generatePayrollForm.payroll_period_id"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            >
                                <option value="">Pilih Periode</option>
                                <option
                                    v-for="period in payrollPeriods"
                                    :key="period.id"
                                    :value="period.id"
                                >
                                    {{ period.name }} ·
                                    {{
                                        new Date(
                                            period.start_date
                                        ).toLocaleDateString("id-ID")
                                    }}
                                </option>
                            </select>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <input
                                    v-model="generatePayrollForm.start_date"
                                    type="date"
                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                />
                                <input
                                    v-model="generatePayrollForm.end_date"
                                    type="date"
                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                />
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <p class="text-muted">
                                    Karyawan dipilih:
                                    <strong>{{ generatePayrollForm.user_ids.length }}</strong>
                                </p>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    @click="generatePayrollForm.user_ids = []"
                                >
                                    Hapus pilihan
                                </Button>
                            </div>
                            <Button
                                class="w-full"
                                :disabled="generatePayrollForm.processing"
                                @click="submitPayrollGeneration"
                            >
                                {{
                                    generatePayrollForm.processing
                                        ? "Memproses..."
                                        : "Generate Payroll"
                                }}
                            </Button>
                        </div>
                        <div
                            v-if="Object.keys(generatePayrollForm.errors).length"
                            class="text-xs text-danger"
                        >
                            <div
                                v-for="(error, key) in generatePayrollForm.errors"
                                :key="key"
                            >
                                {{ error }}
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card class="lg:col-span-2">
                    <div class="p-5 space-y-4">
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between gap-3"
                        >
                            <div>
                                <h2 class="text-lg font-semibold text-text">
                                    Pilih Karyawan
                                </h2>
                                <p class="text-sm text-muted">
                                    Filter berdasarkan outlet atau nama.
                                </p>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input
                                    v-model="employeeFilter"
                                    type="text"
                                    placeholder="Cari nama/email"
                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                />
                                <select
                                    v-model="outletFilter"
                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                >
                                    <option value="">Semua Outlet</option>
                                    <option
                                        v-for="outlet in outlets"
                                        :key="outlet.id"
                                        :value="outlet.id"
                                    >
                                        {{ outlet.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase text-muted">
                                        <th class="px-4 py-3">Pilih</th>
                                        <th class="px-4 py-3">Karyawan</th>
                                        <th class="px-4 py-3">Outlet</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr
                                        v-if="filteredEmployees.length === 0"
                                        class="text-center text-muted"
                                    >
                                        <td colspan="3" class="px-4 py-6">
                                            Tidak ada karyawan yang cocok.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="employee in filteredEmployees"
                                        :key="employee.id"
                                    >
                                        <td class="px-4 py-3">
                                            <div class="space-y-1">
                                                <label class="flex items-center text-xs text-muted">
                                                    <input
                                                        type="checkbox"
                                                        class="mr-2"
                                                        :checked="
                                                            createPeriodForm.user_ids.includes(
                                                                employee.id
                                                            )
                                                        "
                                                        @change="
                                                            toggleEmployee(employee.id, 'create')
                                                        "
                                                    />
                                                    Periode
                                                </label>
                                                <label class="flex items-center text-xs text-muted">
                                                    <input
                                                        type="checkbox"
                                                        class="mr-2"
                                                        :checked="
                                                            generatePayrollForm.user_ids.includes(
                                                                employee.id
                                                            )
                                                        "
                                                        @change="
                                                            toggleEmployee(employee.id, 'generate')
                                                        "
                                                    />
                                                    Generate
                                                </label>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-text">
                                                {{ employee.name }}
                                            </p>
                                            <p class="text-xs text-muted">
                                                {{ employee.email }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-3">
                                            {{
                                                outlets.find(
                                                    (outlet) =>
                                                        outlet.id ===
                                                        employee.outlet_id
                                                )?.name || "Tidak ada outlet"
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </Card>

                <Card>
                    <div class="p-5 space-y-4">
                        <h2 class="text-lg font-semibold text-text">
                            Riwayat Periode
                        </h2>
                        <div class="space-y-3">
                            <div
                                v-for="period in recentDraftPeriods"
                                :key="period.id"
                                class="p-3 bg-surface-2 rounded-lg border border-border"
                            >
                                <p class="font-semibold">
                                    {{ period.name }}
                                </p>
                                <p class="text-xs text-muted">
                                    {{
                                        new Date(
                                            period.start_date
                                        ).toLocaleDateString("id-ID")
                                    }}
                                    -
                                    {{
                                        new Date(
                                            period.end_date
                                        ).toLocaleDateString("id-ID")
                                    }}
                                </p>
                                <span
                                    class="inline-flex mt-2 px-2 py-1 rounded-full text-xs"
                                    :class="
                                        period.status === 'active'
                                            ? 'bg-primary-100 text-primary-800'
                                            : period.status === 'completed'
                                            ? 'bg-success-100 text-success-800'
                                            : 'bg-gray-100 text-gray-700'
                                    "
                                >
                                    {{ period.status }}
                                </span>
                            </div>
                            <p
                                v-if="recentDraftPeriods.length === 0"
                                class="text-sm text-muted text-center"
                            >
                                Belum ada periode yang tercatat.
                            </p>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
