<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, usePage, Link, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/ui/Card.vue";
import Button from "@/Components/ui/Button.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

const page = usePage();
const isSubmitting = ref(false);
const showRequestForm = ref(false);

// Form for leave request
const form = useForm({
    leave_type_id: "",
    start_date: "",
    end_date: "",
    is_half_day: false,
    half_day_type: "first_half",
    emergency_leave: false,
    reason: "",
});

// Computed
const leaveBalances = computed(() => page.props.leaveBalances);
const leaveRequests = computed(() => page.props.leaveRequests.data);
const statistics = computed(() => page.props.statistics);
const leaveTypes = computed(() => page.props.leaveTypes);

const breadcrumbItems = [
    { name: "Dashboard", href: route("dashboard") },
    { name: "Manajemen Cuti" },
];

// Methods
const toggleRequestForm = () => {
    showRequestForm.value = !showRequestForm.value;
    if (!showRequestForm.value) {
        form.reset();
    }
};

const submitRequest = () => {
    form.post(route("leave.store"), {
        onSuccess: () => {
            form.reset();
            showRequestForm.value = false;
        },
    });
};

const formatDate = (dateString) => {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: "bg-warning-100 text-warning-800",
        approved: "bg-success-100 text-success-800",
        rejected: "bg-danger-100 text-danger-800",
        cancelled: "bg-gray-100 text-gray-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const getStatusIcon = (status) => {
    const icons = {
        pending: "⏳",
        approved: "✅",
        rejected: "❌",
        cancelled: "🚫",
    };
    return icons[status] || "❓";
};

const formatDuration = (request) => {
    if (request.is_half_day) {
        const halfDayText =
            request.half_day_type === "first_half"
                ? "First Half"
                : "Second Half";
        return `Half Day (${halfDayText})`;
    }

    if (request.days_count === 1) {
        return "1 Day";
    }

    return `${request.days_count} Days`;
};

onMounted(() => {
    // Initialize date pickers or other UI components
});
</script>

<template>
    <Head title="Leave Management" />

    <AuthenticatedLayout>
        <template #breadcrumb>
            <Breadcrumb :items="breadcrumbItems" />
        </template>

        <div class="space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <h1 class="text-3xl font-bold text-text">
                        Leave Management
                    </h1>
                    <p class="text-muted">
                        Manage your leave requests and balances
                    </p>
                </div>

                <Button @click="toggleRequestForm" variant="primary">
                    {{ showRequestForm ? "Tutup Form" : "Request Leave" }}
                </Button>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <div class="p-6">
                        <div class="flex items-center">
                            <div
                                class="flex-shrink-0 bg-primary-100 rounded-lg p-3"
                            >
                                <svg
                                    class="w-6 h-6 text-primary"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002 2V7a2 2 0 00-2-2h-2M9 5a2 2 0 001-1v-1m3-2V8a2 2 0 00-2 2v6a2 2 0 002 2zm6-4V7a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-text">
                                    Total Requests
                                </p>
                                <p class="text-2xl font-bold text-text">
                                    {{ statistics.total_requests }}
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>

                <Card>
                    <div class="p-6">
                        <div class="flex items-center">
                            <div
                                class="flex-shrink-0 bg-success-100 rounded-lg p-3"
                            >
                                <svg
                                    class="w-6 h-6 text-success"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-text">
                                    Approved
                                </p>
                                <p class="text-2xl font-bold text-text">
                                    {{ statistics.approved_requests }}
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>

                <Card>
                    <div class="p-6">
                        <div class="flex items-center">
                            <div
                                class="flex-shrink-0 bg-info-100 rounded-lg p-3"
                            >
                                <svg
                                    class="w-6 h-6 text-info"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-text">
                                    Days Taken
                                </p>
                                <p class="text-2xl font-bold text-text">
                                    {{ statistics.total_days_taken }}
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>

                <Card>
                    <div class="p-6">
                        <div class="flex items-center">
                            <div
                                class="flex-shrink-0 bg-orange-100 rounded-lg p-3"
                            >
                                <svg
                                    class="w-6 h-6 text-orange-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8c-1.657 0-3-1.343-3-3S8.343 2 6 2 3s3 1.343 3 3 3 1.657 0 3 1.657z"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-text">
                                    Pending
                                </p>
                                <p class="text-2xl font-bold text-text">
                                    {{ statistics.pending_requests }}
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Leave Balances -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <h2 class="text-xl font-bold text-text mb-6">
                        Leave Balances
                    </h2>
                    <div class="space-y-4">
                        <div
                            v-for="balance in leaveBalances"
                            :key="balance.leave_type.id"
                            class="border-b border-border pb-4 last:border-0"
                        >
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="font-semibold text-text">
                                        {{ balance.leave_type.name }}
                                    </h3>
                                    <p class="text-sm text-muted">
                                        {{ balance.leave_type.description }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div
                                        class="text-2xl font-bold"
                                        :class="{
                                            'text-success':
                                                balance.balance.remaining_days >
                                                5,
                                            'text-warning':
                                                balance.balance.remaining_days >
                                                    0 &&
                                                balance.balance
                                                    .remaining_days <= 5,
                                            'text-danger':
                                                balance.balance
                                                    .remaining_days <= 0,
                                        }"
                                    >
                                        {{ balance.balance.remaining_days }}
                                    </div>
                                    <div class="text-sm text-muted">
                                        days remaining
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <div
                                    class="w-full bg-gray-200 rounded-full h-2"
                                >
                                    <div
                                        class="bg-primary-600 h-2 rounded-full"
                                        :style="{
                                            width:
                                                balance.balance
                                                    .usage_percentage + '%',
                                        }"
                                    ></div>
                                </div>
                                <div class="text-xs text-muted mt-1">
                                    {{ balance.balance.usage_percentage }}% used
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Recent Leave Requests -->
                <Card>
                    <h2 class="text-xl font-bold text-text mb-6">
                        Recent Leave Requests
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-surface-1">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-text uppercase tracking-wider"
                                    >
                                        Type
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-text uppercase tracking-wider"
                                    >
                                        Duration
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-text uppercase tracking-wider"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-text uppercase tracking-wider"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-surface-1 divide-y divide-border">
                                <tr
                                    v-for="request in leaveRequests"
                                    :key="request.id"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                :style="{
                                                    backgroundColor:
                                                        request.leave_type
                                                            .color_code + '20',
                                                    color: 'white',
                                                }"
                                            >
                                                {{ request.leave_type.name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ formatDuration(request) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                                getStatusBadgeClass(
                                                    request.status
                                                ),
                                            ]"
                                        >
                                            {{ getStatusIcon(request.status) }}
                                            {{ request.status }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                    >
                                        <Link
                                            :href="
                                                route('leave.show', request.id)
                                            "
                                            class="text-primary hover:text-primary-400 mr-3"
                                        >
                                            View
                                        </Link>
                                        <button
                                            v-if="request.status === 'pending'"
                                            @click="showRequestForm = true"
                                            class="text-danger hover:text-danger-400"
                                        >
                                            Cancel
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </div>

        <!-- Request Leave Form -->
        <Card v-if="showRequestForm" class="mt-6">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-text mb-6">Request Leave</h2>

                <form @submit.prevent="submitRequest" class="space-y-6">
                    <!-- Leave Type -->
                    <div>
                        <label class="block text-sm font-medium text-text mb-2"
                            >Leave Type</label
                        >
                        <select
                            v-model="form.leave_type_id"
                            required
                            class="w-full px-4 py-2 border border-border rounded-lg bg-surface-1 text-text"
                        >
                            <option value="">Select Leave Type</option>
                            <option
                                v-for="type in leaveTypes"
                                :key="type.id"
                                :value="type.id"
                            >
                                {{ type.name }} ({{
                                    type.max_days_per_year
                                }}
                                days/year)
                            </option>
                        </select>
                        <div
                            v-if="form.errors.leave_type_id"
                            class="text-sm text-danger mt-1"
                        >
                            {{ form.errors.leave_type_id }}
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label
                                class="block text-sm font-medium text-text mb-2"
                                >Start Date</label
                            >
                            <input
                                type="date"
                                v-model="form.start_date"
                                required
                                class="w-full px-4 py-2 border border-border rounded-lg bg-surface-1 text-text"
                            />
                            <div
                                v-if="form.errors.start_date"
                                class="text-sm text-danger mt-1"
                            >
                                {{ form.errors.start_date }}
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-text mb-2"
                                >End Date</label
                            >
                            <input
                                type="date"
                                v-model="form.end_date"
                                required
                                class="w-full px-4 py-2 border border-border rounded-lg bg-surface-1 text-text"
                            />
                            <div
                                v-if="form.errors.end_date"
                                class="text-sm text-danger mt-1"
                            >
                                {{ form.errors.end_date }}
                            </div>
                        </div>
                    </div>

                    <!-- Half Day Option -->
                    <div>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.is_half_day"
                                class="mr-2"
                            />
                            <span class="text-sm font-medium text-text"
                                >Half Day</span
                            >
                        </label>

                        <div v-if="form.is_half_day" class="mt-2">
                            <select
                                v-model="form.half_day_type"
                                required
                                class="w-full px-4 py-2 border border-border rounded-lg bg-surface-1 text-text"
                            >
                                <option value="first_half">First Half</option>
                                <option value="second_half">Second Half</option>
                            </select>
                            <div
                                v-if="form.errors.half_day_type"
                                class="text-sm text-danger mt-1"
                            >
                                {{ form.errors.half_day_type }}
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Leave -->
                    <div>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.emergency_leave"
                                class="mr-2"
                            />
                            <span class="text-sm font-medium text-text"
                                >Emergency Leave</span
                            >
                        </label>
                    </div>

                    <!-- Reason -->
                    <div>
                        <label class="block text-sm font-medium text-text mb-2"
                            >Reason</label
                        >
                        <textarea
                            v-model="form.reason"
                            required
                            rows="4"
                            class="w-full px-4 py-2 border border-border rounded-lg bg-surface-1 text-text"
                            placeholder="Please provide a reason for your leave request..."
                        ></textarea>
                        <div
                            v-if="form.errors.reason"
                            class="text-sm text-danger mt-1"
                        >
                            {{ form.errors.reason }}
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-4">
                        <Button
                            type="button"
                            @click="toggleRequestForm"
                            variant="secondary"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            variant="primary"
                        >
                            {{
                                form.processing
                                    ? "Submitting..."
                                    : "Submit Request"
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </Card>
    </AuthenticatedLayout>
</template>
