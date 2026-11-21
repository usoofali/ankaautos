<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    BadgeDollarSign,
    CheckCircle2,
    Eye,
    Plus,
    Wallet,
} from '@lucide/vue';
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
import type { Paginated } from '@/types';

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
    sales: Paginated<SaleRow>;
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

const columns: DataTableColumn[] = [
    { key: 'reference_number', label: 'Reference', sortable: true },
    { key: 'vehicle_title', label: 'Vehicle & Customer' },
    {
        key: 'final_price',
        label: 'Final Price',
        align: 'right',
        sortable: true,
    },
    { key: 'amount_paid', label: 'Paid', align: 'right' },
    { key: 'balance_due', label: 'Balance', align: 'right', sortable: true },
    { key: 'status', label: 'Status' },
    { key: 'confirmed_at', label: 'Date', sortable: true },
    { key: 'actions', label: 'Actions', align: 'right' },
];

function updateQuery(
    overrides: Record<string, string | number | undefined>,
): void {
    router.get(
        index.url(),
        {
            search: props.filters.search || undefined,
            status: props.filters.status || undefined,
            sort: props.filters.sort,
            direction: props.filters.direction,
            per_page: props.filters.per_page,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Vehicle Sales" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Vehicle Sales"
            description="Track confirmed vehicle sales, installment receivables, receipts, and physical handovers."
        >
            <template v-if="can('sales.create')" #actions>
                <Button as-child class="min-h-10 w-full sm:w-auto">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Record Sale
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
            caption="Vehicle sales transactions"
            :columns="columns"
            :rows="sales"
            :search="filters.search"
            search-placeholder="Search sale reference, customer, vehicle..."
            :sort-by="filters.sort"
            :sort-direction="filters.direction"
            :per-page="filters.per_page"
            empty-title="No sales recorded yet"
            empty-description="Confirm a direct sale or convert an active reservation to start tracking collections."
            @update:search="
                (v) => updateQuery({ search: v || undefined, page: 1 })
            "
            @update:per-page="(p) => updateQuery({ per_page: p, page: 1 })"
            @sort="
                (key, dir) =>
                    updateQuery({ sort: key, direction: dir, page: 1 })
            "
        >
            <template #filters>
                <select
                    :value="filters.status"
                    aria-label="Filter by sale status"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    @change="
                        updateQuery({
                            status:
                                ($event.target as HTMLSelectElement).value ||
                                undefined,
                            page: 1,
                        })
                    "
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
            </template>

            <template #mobile-card="{ row }">
                <div class="space-y-2.5">
                    <div class="flex items-start justify-between gap-2.5">
                        <div class="min-w-0">
                            <Link
                                :href="show(row.id)"
                                class="font-mono text-sm font-semibold text-primary hover:underline"
                            >
                                {{ row.reference_number }}
                            </Link>
                            <p class="mt-0.5 text-sm font-medium break-words">
                                {{ row.vehicle_title }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ row.customer_name }} ·
                                {{ row.ownership_label }}
                            </p>
                        </div>
                        <StatusBadge
                            class="shrink-0"
                            :label="row.status_label"
                            :variant="row.status_variant"
                        />
                    </div>
                    <div class="space-y-1 border-t pt-2.5 text-xs tabular-nums">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-muted-foreground"
                                >Final Price</span
                            >
                            <span class="shrink-0 font-semibold">
                                {{ formatMoney(row.final_price) }}
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-muted-foreground"
                                >Amount Paid</span
                            >
                            <span
                                class="shrink-0 font-semibold text-emerald-700 dark:text-emerald-400"
                            >
                                {{ formatMoney(row.amount_paid) }}
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-muted-foreground"
                                >Balance Due</span
                            >
                            <span
                                class="shrink-0 font-semibold"
                                :class="
                                    Number(row.balance_due) > 0
                                        ? 'text-amber-700 dark:text-amber-400'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ formatMoney(row.balance_due) }}
                            </span>
                        </div>
                    </div>
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        class="min-h-9 w-full"
                    >
                        <Link :href="show(row.id)">Open Sale</Link>
                    </Button>
                </div>
            </template>

            <template #row="{ row }">
                <td class="px-4 py-3.5 whitespace-nowrap">
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
                </td>
                <td class="px-4 py-3.5">
                    <div class="font-medium text-foreground">
                        {{ row.vehicle_title }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ row.vehicle_reference }} · {{ row.customer_name }}
                    </div>
                </td>
                <td
                    class="px-4 py-3.5 text-right font-medium whitespace-nowrap tabular-nums"
                >
                    {{ formatMoney(row.final_price) }}
                </td>
                <td
                    class="px-4 py-3.5 text-right whitespace-nowrap text-emerald-700 tabular-nums dark:text-emerald-400"
                >
                    {{ formatMoney(row.amount_paid) }}
                </td>
                <td
                    class="px-4 py-3.5 text-right font-medium whitespace-nowrap tabular-nums"
                    :class="
                        Number(row.balance_due) > 0
                            ? 'text-amber-700 dark:text-amber-400'
                            : 'text-muted-foreground'
                    "
                >
                    {{ formatMoney(row.balance_due) }}
                </td>
                <td class="px-4 py-3.5">
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
                </td>
                <td
                    class="px-4 py-3.5 text-xs whitespace-nowrap text-muted-foreground tabular-nums"
                >
                    {{ formatDate(row.confirmed_at) }}
                </td>
                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        class="min-h-9"
                    >
                        <Link :href="show(row.id)">
                            <Eye class="mr-1 size-3.5" aria-hidden="true" />
                            Open
                        </Link>
                    </Button>
                </td>
            </template>
        </DataTableCard>
    </div>
</template>
