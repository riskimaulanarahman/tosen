<script setup>
import { computed, ref } from "vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
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
const page = usePage();
const flash = computed(() => page.props.flash || {});
const showHelp = ref(false);

const approveForm = useForm({
    notes: "",
});

const paymentForm = useForm({
    payment_method: "",
    payment_reference: "",
    paid_at: "",
});
const paymentMethodOptions = [
    { value: "cash", label: "Cash" },
    { value: "transfer", label: "Transfer Bank" },
    { value: "ewallet", label: "E-Wallet" },
];
const adjustmentForm = useForm({
    payroll_record_id: null,
    bonus: "",
    other_deductions: "",
    notes: "",
});
const editingId = ref(null);

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
    const datePart =
        typeof value === "string" ? value.split("T")[0].split(" ")[0] : value;
    const date = new Date(`${datePart}T00:00:00`);
    return new Intl.DateTimeFormat("id-ID", {
        year: "numeric",
        month: "short",
        day: "numeric",
        timeZone: "Asia/Jakarta",
    }).format(date);
};

const totalNetPay = computed(() =>
    records.value.reduce(
        (sum, record) =>
            sum +
            Number(record.total_pay || 0) -
            Number(record.tax_deduction || 0) -
            Number(record.other_deductions || 0),
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
    paymentForm.post(
        route("payroll.process.payments", props.payrollPeriod.id),
        {
            preserveScroll: true,
        }
    );
};

const exportPayroll = () => {
    window.location.href = route("payroll.export", props.payrollPeriod.id);
};

const startEdit = (record) => {
    editingId.value = record.id;
    adjustmentForm.payroll_record_id = record.id;
    adjustmentForm.bonus = record.bonus ?? 0;
    adjustmentForm.other_deductions = record.other_deductions ?? 0;
    adjustmentForm.notes = record.notes ?? "";
};

const cancelEdit = () => {
    editingId.value = null;
    adjustmentForm.reset();
    adjustmentForm.clearErrors();
};

const submitAdjustment = () => {
    if (!adjustmentForm.payroll_record_id) return;
    adjustmentForm.put(
        route("payroll.record.adjust", adjustmentForm.payroll_record_id),
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelEdit();
            },
        }
    );
};
</script>

<template>
    <Head :title="`Detail Payroll: ${payrollPeriod.name}`" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-text"
                        style="font-family: 'Oswald', sans-serif"
                    >
                        {{ payrollPeriod.name }}
                    </h1>
                    <p class="text-muted">
                        Periode {{ formatDate(payrollPeriod.start_date) }} -
                        {{ formatDate(payrollPeriod.end_date) }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="ghost" @click="showHelp = !showHelp">
                        {{ showHelp ? "Tutup Help" : "Help" }}
                    </Button>
                    <Link :href="route('payroll.index')">
                        <Button variant="secondary">Kembali</Button>
                    </Link>
                    <Button variant="ghost" @click="exportPayroll"
                        >Export CSV</Button
                    >
                    <Button
                        variant="ghost"
                        :href="
                            route('payroll.export.summary', payrollPeriod.id)
                        "
                    >
                        Export Summary
                    </Button>
                    <Button
                        variant="primary"
                        :disabled="
                            approveForm.processing ||
                            payrollPeriod.status !== 'active'
                        "
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
                        :disabled="
                            paymentForm.processing ||
                            payrollPeriod.status !== 'completed'
                        "
                        @click="processPayments"
                    >
                        {{
                            paymentForm.processing
                                ? "Memproses..."
                                : "Proses Pembayaran"
                        }}
                    </Button>
                </div>
            </div>

            <div
                v-if="flash?.success"
                class="bg-success-900/20 border border-success-700 text-success-100 p-4 rounded-lg"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash?.error"
                class="bg-danger-900/20 border border-danger-700 text-danger-100 p-4 rounded-lg"
            >
                {{ flash.error }}
            </div>

            <Card v-if="showHelp">
                <div class="p-4 space-y-2 text-sm text-text">
                    <p class="font-semibold">Cara menggunakan halaman ini:</p>
                    <ul class="list-disc ml-5 space-y-1 text-muted">
                        <li>
                            Pastikan status periode
                            <strong>active</strong> sebelum klik "Setujui
                            Periode" (akan menjadi <strong>completed</strong>).
                        </li>
                        <li>
                            "Proses Pembayaran" hanya aktif setelah status
                            menjadi <strong>completed</strong>; mengubah seluruh
                            record approved menjadi <strong>paid</strong>.
                        </li>
                        <li>
                            Gunakan "Export CSV" untuk mengunduh rekap gaji dan
                            lembur.
                        </li>
                        <li>
                            Periksa tabel payroll untuk melihat potongan pajak,
                            potongan lain, dan detail lembur per karyawan.
                        </li>
                    </ul>
                </div>
            </Card>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <div class="p-4">
                        <p class="text-sm text-muted">Total Karyawan</p>
                        <p class="text-3xl font-bold text-text">
                            {{ totalEmployees }}
                        </p>
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
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-text">
                            Pembayaran
                        </h2>
                        <p class="text-xs text-muted">
                            Tentukan metode/tanggal sebelum "Proses Pembayaran"
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <label class="text-sm text-muted">Metode</label>
                            <select
                                v-model="paymentForm.payment_method"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            >
                                <option value="">Pilih metode</option>
                                <option
                                    v-for="item in paymentMethodOptions"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm text-muted"
                                >Referensi/No. Transaksi</label
                            >
                            <input
                                v-model="paymentForm.payment_reference"
                                type="text"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                placeholder="Nomor transfer/nota"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm text-muted"
                                >Tanggal Bayar</label
                            >
                            <input
                                v-model="paymentForm.paid_at"
                                type="date"
                                class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                            />
                        </div>
                    </div>
                    <div
                        class="text-xs text-danger"
                        v-if="paymentForm.errors.payment_method"
                    >
                        {{ paymentForm.errors.payment_method }}
                    </div>
                    <div
                        class="text-xs text-danger"
                        v-if="paymentForm.errors.payment_reference"
                    >
                        {{ paymentForm.errors.payment_reference }}
                    </div>
                    <div
                        class="text-xs text-danger"
                        v-if="paymentForm.errors.paid_at"
                    >
                        {{ paymentForm.errors.paid_at }}
                    </div>
                </div>
            </Card>

            <Card>
                <div class="p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-text">
                        Catatan Persetujuan
                    </h2>
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
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-2"
                    >
                        <div>
                            <h2 class="text-lg font-semibold text-text">
                                Daftar Payroll
                            </h2>
                            <p class="text-sm text-muted">
                                Semua karyawan pada periode ini
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table
                            class="min-w-full divide-y divide-border text-sm"
                        >
                            <thead>
                                <tr
                                    class="text-left text-xs uppercase text-muted"
                                >
                                    <th class="px-4 py-3">Karyawan</th>
                                    <th class="px-4 py-3">Total</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Diterima</th>
                                    <th class="px-4 py-3">Overtime</th>
                                    <th class="px-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-if="records.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-4 py-6 text-center text-muted"
                                    >
                                        Belum ada payroll yang dihasilkan.
                                    </td>
                                </tr>
                                <tr
                                    v-for="record in records"
                                    :key="record.id"
                                    class="align-top"
                                >
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">
                                            {{
                                                record.user?.name ||
                                                `Karyawan #${record.user_id}`
                                            }}
                                        </p>
                                        <p class="text-xs text-muted">
                                            {{ record.user?.email || "-" }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p>
                                            {{
                                                formatCurrency(record.total_pay)
                                            }}
                                        </p>
                                        <p class="text-xs text-muted">
                                            Pajak:
                                            {{
                                                formatCurrency(
                                                    record.tax_deduction
                                                )
                                            }}
                                            • Potongan lain:
                                            {{
                                                formatCurrency(
                                                    record.other_deductions
                                                )
                                            }}
                                        </p>
                                        <p class="text-xs text-muted">
                                            Bonus:
                                            {{ formatCurrency(record.bonus) }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex px-2 py-1 rounded-full text-xs"
                                            :class="
                                                record.status === 'paid'
                                                    ? 'bg-success-100 text-success-800'
                                                    : record.status ===
                                                      'approved'
                                                    ? 'bg-primary-100 text-primary-800'
                                                    : 'bg-gray-100 text-gray-600'
                                            "
                                        >
                                            {{ record.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{
                                            formatCurrency(
                                                record.total_pay -
                                                    record.tax_deduction -
                                                    record.other_deductions
                                            )
                                        }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            v-if="
                                                (record.overtime_records || [])
                                                    .length === 0
                                            "
                                            class="text-xs text-muted"
                                        >
                                            Tidak ada lembur
                                        </div>
                                        <ul
                                            v-else
                                            class="text-xs text-muted space-y-1"
                                        >
                                            <li
                                                v-for="overtime in record.overtime_records"
                                                :key="overtime.id"
                                            >
                                                {{
                                                    formatDate(overtime.date)
                                                }}
                                                •
                                                {{
                                                    overtime.overtime_minutes
                                                }}
                                                menit
                                            </li>
                                        </ul>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="mb-2 flex flex-col gap-2">
                                            <a
                                                :href="
                                                    route(
                                                        'payroll.record.slip',
                                                        [
                                                            record.id,
                                                            { view: 'html' },
                                                        ]
                                                    )
                                                "
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                <Button
                                                    size="sm"
                                                    variant="secondary"
                                                    class="w-full"
                                                >
                                                    Lihat Slip
                                                </Button>
                                            </a>
                                        </div>
                                        <div
                                            v-if="editingId === record.id"
                                            class="space-y-2"
                                        >
                                            <div class="space-y-1">
                                                <label
                                                    class="text-xs text-muted"
                                                    >Tunjangan/Bonus</label
                                                >
                                                <input
                                                    v-model.number="
                                                        adjustmentForm.bonus
                                                    "
                                                    type="number"
                                                    min="0"
                                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                                />
                                            </div>
                                            <div class="space-y-1">
                                                <label
                                                    class="text-xs text-muted"
                                                    >Potongan lain</label
                                                >
                                                <input
                                                    v-model.number="
                                                        adjustmentForm.other_deductions
                                                    "
                                                    type="number"
                                                    min="0"
                                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                                />
                                            </div>
                                            <div class="space-y-1">
                                                <label
                                                    class="text-xs text-muted"
                                                    >Catatan</label
                                                >
                                                <textarea
                                                    v-model="
                                                        adjustmentForm.notes
                                                    "
                                                    rows="2"
                                                    class="w-full px-3 py-2 bg-surface-2 border border-border rounded-lg"
                                                />
                                            </div>
                                            <div class="flex gap-2">
                                                <Button
                                                    size="sm"
                                                    :disabled="
                                                        adjustmentForm.processing
                                                    "
                                                    @click="submitAdjustment"
                                                >
                                                    {{
                                                        adjustmentForm.processing
                                                            ? "Menyimpan..."
                                                            : "Simpan"
                                                    }}
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="secondary"
                                                    @click="cancelEdit"
                                                >
                                                    Batal
                                                </Button>
                                            </div>
                                            <div
                                                v-if="
                                                    adjustmentForm.errors.bonus
                                                "
                                                class="text-xs text-danger"
                                            >
                                                {{
                                                    adjustmentForm.errors.bonus
                                                }}
                                            </div>
                                            <div
                                                v-if="
                                                    adjustmentForm.errors
                                                        .other_deductions
                                                "
                                                class="text-xs text-danger"
                                            >
                                                {{
                                                    adjustmentForm.errors
                                                        .other_deductions
                                                }}
                                            </div>
                                            <div
                                                v-if="
                                                    adjustmentForm.errors.notes
                                                "
                                                class="text-xs text-danger"
                                            >
                                                {{
                                                    adjustmentForm.errors.notes
                                                }}
                                            </div>
                                        </div>
                                        <div v-else class="space-y-2">
                                            <div class="text-xs text-muted">
                                                Bonus:
                                                {{ formatCurrency(record.bonus)
                                                }}<br />
                                                Potongan:
                                                {{
                                                    formatCurrency(
                                                        record.other_deductions
                                                    )
                                                }}
                                            </div>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                @click="startEdit(record)"
                                                :disabled="
                                                    payrollPeriod.status ===
                                                    'paid'
                                                "
                                            >
                                                Edit
                                            </Button>
                                        </div>
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
