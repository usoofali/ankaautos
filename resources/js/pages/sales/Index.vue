<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BadgeDollarSign,
    CheckCircle2,
    Eye,
    Plus,
    Wallet,
    AlertCircle,
} from '@lucide/vue';
import { ref } from 'vue';
import DataTableCard, {
    type DataTableColumn,
} from '@/components/anka/DataTableCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatCard from '@/components/anka/StatCard.vue';
import StatusBadge, {
    type StatusVariant,
} from '@/components/anka/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useFormatting } from '@/composables/useFormatting';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { create, index, show } from '@/routes/sales';
import type { PaginatedData } from '@/types';

type SaleRow = {
    id: number;
    reference_number: string;
    vehicle_id: number;
    vehicle_reference: string;
    vehicle_title: string;
    ownership_label: string;
    customer_id: number;
    customer_name: string;
    final_price: string;
    amount_paid: string;
    balance_due: string;
    is_installment: boolean;
    has_handover: boolean;
    status: string;
    status_label: string;
    status_variant: StatusVariant;
    confirmed_at: string;
};

const props = defineProps<{
    sales: PaginatedData<SaleRow>;
    statusOptions: Array<{ value: string; label: string }>;
    summary: {
        total_sales_value: string;
        total_collected: string;
        total_receivable: string;
        completed_count: number;
    };
    filters: {
        search: string;
        status: string;
        sort: string;
        direction: 'asc' | 'desc';
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Sales', href: index() },
        ],
    },
});

const { can } = usePermissions();
const { formatMoney, formatDate } = useFormatting();

const search = ref(props.filters.search);
const statusFilter = ref(props.filters.status);
const sortBy = ref(props.filters.sort);
const sortDir = ref<'asc' | 'desc'>(props.filters.direction);
const perPage = ref(props.filters.per_page);
const loading = ref(false);

const columns: DataTableColumn[] = [
    { key: 'reference_number', label: 'Reference', sortable: true },
    { key: 'vehicle_title', label: 'Vehicle & Customer' },
    { key: 'final_price', label: 'Final Price', align: 'right', sortable: true },
    { key: 'amount_paid', label: 'Paid', align: 'right' },
    { key: 'balance_due', label: 'Balance', align: 'right', sortable: true },
    { key: 'status', label: 'Status' },
    { key: 'confirmed_at', label: 'Date', sortable: true },
];

function applyFilters(page = 1): void {
    loading.value = true;
    router.get(
        index.url(),
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            sort: sortBy.value,
            direction: sortDir.value,
            per_page: perPage.value,
            page,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                loading.value = false;
            },
        },
    );
}

function handleSort(column: string): void {
    if (sortBy.value === column) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDir.value = 'desc';
    }
    applyFilters(1);
}

function resetFilters(): void {
    search.value = '';
    statusFilter.value = '';
    sortBy.value = 'confirmed_at';
    sortDir.value = 'desc';
    applyFilters(1);
}
</script>

<template>
    <Head title="Vehicle Sales" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Vehicle Sales"
            description="Track confirmed vehicle sales, installment receivables, receipts, and physical handovers."
        >
            <template #actions>
                <Button v-if="can('sales.create')" as-child class="min-h-10">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Record Sale
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                label="Total Sales Value"
                :value="formatMoney(summary.total_sales_value)"
                :icon="BadgeDollarSign"
                tone="primary"
            />
            <StatCard
                label="Customer Collections"
                :value="formatMoney(summary.total_collected)"
                :icon="Wallet"
                tone="success"
            />
            <StatCard
                label="Outstanding Receivables"
                :value="formatMoney(summary.total_receivable)"
                :icon="AlertCircle"
                tone="warning"
            />
            <StatCard
                label="Completed Sales"
                :value="String(summary.completed_count)"
                :icon="CheckCircle2"
                tone="info"
            />
        </div>

        <DataTableCard
            title="Sales Transactions"
            description="Search by reference, customer name, phone, or vehicle code."
            :columns="columns"
            :rows="sales.data"
            :pagination="sales"
            :search="search"
            search-placeholder="Search sale reference, customer, vehicle..."
            :sort-by="sortBy"
            :sort-direction="sortDir"
            :per-page="perPage"
            :loading="loading"
            empty-title="No sales recorded yet"
            empty-description="Confirm a direct sale or convert an active reservation to start tracking collections."
            @update:search="
                (v) => {
                    search = v;
                    applyFilters(1);
                }
            "
            @update:per-page="
                (v) => {
                    perPage = v;
                    applyFilters(1);
                }
            "
            @sort="handleSort"
        >
            <template #filters>
                <select
                    v-model="statusFilter"
                    aria-label="Filter by sale status"
                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    @change="applyFilters(1)"
                >
                    <option value="">All Statuses</option>
                    <option
                        v-for="opt in statusOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>
                <Button
                    v-if="search || statusFilter"
                    variant="ghost"
                    size="sm"
                    class="min-h-10"
                    @click="resetFilters"
                >
                    Reset
                </Button>
            </template>

            <template #cell-reference_number="{ row }">
                <Link
                    :href="show(row.id)"
                    class="font-mono text-sm font-semibold text-primary hover:underline"
                >
                    {{ row.reference_number }}
                </Link>
                <div class="text-xs text-muted-foreground">
                    {{ row.ownership_label }}
                    <span v-if="row.is_installment"> · Installment</span>
                </div>
            </template>

            <template #cell-vehicle_title="{ row }">
                <div class="font-medium text-foreground">
                    {{ row.vehicle_title }}
                </div>
                <div class="text-xs text-muted-foreground">
                    {{ row.vehicle_reference }} · {{ row.customer_name }}
                </div>
            </template>

            <template #cell-final_price="{ row }">
                <span class="font-medium tabular-nums">
                    {{ formatMoney(row.final_price) }}
                </span>
            </template>

            <template #cell-amount_paid="{ row }">
                <span class="text-emerald-700 tabular-nums dark:text-emerald-400">
                    {{ formatMoney(row.amount_paid) }}
                </span>
            </template>

            <template #cell-balance_due="{ row }">
                <span
                    class="font-medium tabular-nums"
                    :class="
                        Number(row.balance_due) > 0
                            ? 'text-amber-700 dark:text-amber-400'
                            : 'text-muted-foreground'
                    "
                >
                    {{ formatMoney(row.balance_due) }}
                </span>
            </template>

            <template #cell-status="{ row }">
                <div class="flex flex-wrap items-center gap-1.5">
                    <StatusBadge
                        :label="row.status_label"
                        :variant="row.status_variant"
                    />
                    <StatusBadge
                        v-if="row.has_handover"
                        label="Handed Over"
                        variant="info"
                    />
                </div>
            </template>

            <template #cell-confirmed_at="{ row }">
                <span class="text-xs text-muted-foreground tabular-nums">
                    {{ formatDate(row.confirmed_at) }}
                </span>
            </template>

            <template #actions="{ row }">
                <Button as-child variant="outline" size="sm" class="min-h-9">
                    <Link :href="show(row.id)">
                        <Eye class="mr-1 size-3.5" aria-hidden="true" />
                        Open
                    </Link>
                </Button>
            </template>

            <template #mobile-card="{ row }">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <Link
                            :href="show(row.id)"
                            class="font-mono text-sm font-semibold text-primary hover:underline"
                        >
                            {{ row.reference_number }}
                        </Link>
                        <p class="mt-0.5 text-sm font-medium">
                            {{ row.vehicle_title }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ row.customer_name }} · {{ row.ownership_label }}
                        </p>
                    </div>
                    <StatusBadge
                        :label="row.status_label"
                        :variant="row.status_variant"
                    />
                </div>
                <div
                    class="mt-3 grid grid-cols-3 gap-2 border-t pt-2.5 text-xs"
                >
                    <div>
                        <span class="text-muted-foreground">Final</span>
                        <p class="font-semibold tabular-nums">
                            {{ formatMoney(row.final_price) }}
                        </p>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Paid</span>
                        <p
                            class="font-semibold text-emerald-700 tabular-nums dark:text-emerald-400"
                        >
                            {{ formatMoney(row.amount_paid) }}
                        </p>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Balance</span>
                        <p class="font-semibold tabular-nums">
                            {{ formatMoney(row.balance_due) }}
                        </p>
                    </div>
                </div>
            </template>
        </DataTableCard>
    </div>
</template>
