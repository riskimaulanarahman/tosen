<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";

const props = defineProps({
    payrollPeriod: {
        type: Object,
        required: true,
    },
});

const records = computed(() => props.payrollPeriod?.payroll_records || []);

const approveForm = useForm({
    notes: "",
});

const paymentForm = useForm({});

const formatCurrency = (value) => {
    if (value === null || value === undefined) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(Number(value));
};

const formatDate = (value) => {
    if (!value) return "-";
    return new Date(value).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const totalNetPay = computed(() =>
    records.value.reduce(
        (sum, record) => sum + Number(record.total_pay || 0) - Number(record.tax_deduction || 0) - Number(record.other_deductions || 0),
        0
    )
);

const totalEmployees = computed(() => records.value.length);

const submitApproval = () => {
    approveForm.post(route("payroll.approve", props.payrollPeriod.id), {
        preserveScroll: true,
        onSuccess: () => approveForm.reset(),
    });
};

const processPayments = () => {
    paymentForm.post(route("payroll.process.payments", props.payrollPeriod.id), {
        preserveScroll: true,
    });
};

const exportPayroll = () => {
    window.location.href = route("payroll.export", props.payrollPeriod.id);
};
</script>

<template>
    <Head :title="`Detail Payroll: ${payrollPeriod.name}`" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-text" style="font-family: 'Oswald', sans-serif">
                        {{ payrollPeriod.name }}
                    </h1>
                    <p class="text-muted">
                        Periode {{ formatDate(payrollPeriod.start_date) }} - {{ formatDate(payrollPeriod.end_date) }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('payroll.index')">
                        <Button variant="secondary">Kembali</Button>
                    </Link>
                    <Button variant="ghost" @click="exportPayroll">Export CSV</Button>
                    <Button
                        variant="primary"
                        :disabled="approveForm.processing"
                        @click="submitApproval"
                    >
                        {{
                            approveForm.processing
                                ? "Menyetujui..."
                                : "Setujui Periode"
                        }}
                    </Button>
                    <Button
                        variant="success"
                        :disabled="paymentForm.processing || payrollPeriod.status !== 'completed'"
                        @click="processPayments"
                    >
                        {{
                            paymentForm.processing ? "Memproses..." : "Proses Pembayaran"
                        }}
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <div class="p-4">
                        <p class="text-sm text-muted">Total Karyawan</p>
                        <p class="text-3xl font-bold text-text">{{ totalEmployees }}</p>
                    </div>
                </Card>
                <Card>
                    <div class="p-4">
                        <p class="text-sm text-muted">Total Gaji Bersih</p>
                        <p class="text-3xl font-bold text-success">
                            {{ formatCurrency(totalNetPay) }}
                        </p>
                    </div>
                </Card>
                <Card>
                    <div class="p-4">
                        <p class="text-sm text-muted">Status Periode</p>
                        <p class="text-3xl font-bold text-info">
                            {{ payrollPeriod.status }}
                        </p>
                    </div>
                </Card>
            </div>

            <Card>
                <div class="p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-text">Catatan Persetujuan</h2>
                    <textarea
                        v-model="approveForm.notes"
                        rows="3"
                        class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                        placeholder="Catatan untuk approval..."
                    ></textarea>
                    <div
                        v-if="approveForm.errors.notes"
                        class="text-xs text-danger"
                    >
                        {{ approveForm.errors.notes }}
                    </div>
                </div>
            </Card>

            <Card>
                <div class="p-6 space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-semibold text-text">Daftar Payroll</h2>
                            <p class="text-sm text-muted">Semua karyawan pada periode ini</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-muted">
                                    <th class="px-4 py-3">Karyawan</th>
                                    <th class="px-4 py-3">Total</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Diterima</th>
                                    <th class="px-4 py-3">Overtime</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-if="records.length === 0">
                                    <td colspan="5" class="px-4 py-6 text-center text-muted">
                                        Belum ada payroll yang dihasilkan.
                                    </td>
                                </tr>
                                <tr v-for="record in records" :key="record.id">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">
                                            {{ record.user?.name || `Karyawan #${record.user_id}` }}
                                        </p>
                                        <p class="text-xs text-muted">
                                            {{ record.user?.email || "-" }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p>{{ formatCurrency(record.total_pay) }}</p>
                                        <p class="text-xs text-muted">
                                            Pajak: {{ formatCurrency(record.tax_deduction) }} • Potongan lain:
                                            {{ formatCurrency(record.other_deductions) }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex px-2 py-1 rounded-full text-xs"
                                            :class="record.status === 'paid'
                                                ? 'bg-success-100 text-success-800'
                                                : record.status === 'approved'
                                                ? 'bg-primary-100 text-primary-800'
                                                : 'bg-gray-100 text-gray-600'"
                                        >
                                            {{ record.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ formatCurrency(record.total_pay - record.tax_deduction - record.other_deductions) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="(record.overtime_records || []).length === 0" class="text-xs text-muted">
                                            Tidak ada lembur
                                        </div>
                                        <ul v-else class="text-xs text-muted space-y-1">
                                            <li v-for="overtime in record.overtime_records" :key="overtime.id">
                                                {{ formatDate(overtime.date) }} • {{ overtime.overtime_minutes }} menit
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
