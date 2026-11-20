<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Building2,
    Eye,
    Handshake,
    Plus,
    Receipt,
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
import { create, index, show } from '@/routes/expenses';
import type { Paginated } from '@/types';

type ExpenseRow = {
    id: number;
    reference_number: string;
    vehicle_id: number;
    vehicle_reference: string;
    vehicle_title: string;
    category_name: string;
    borne_by: string;
    borne_by_label: string;
    borne_by_variant: StatusVariant;
    description: string;
    amount: string;
    amount_paid: string;
    balance_due: string;
    payment_status: string;
    payment_status_label: string;
    payment_status_variant: StatusVariant;
    expense_date: string;
    vendor_name: string | null;
    is_deducted: boolean;
};

const props = defineProps<{
    expenses: Paginated<ExpenseRow>;
    categories: Array<{ id: number; name: string }>;
    bearerOptions: Array<{ value: string; label: string }>;
    statusOptions: Array<{ value: string; label: string }>;
    summary: {
        total_amount: string;
        company_total: string;
        owner_deductible_total: string;
        unpaid_count: number;
    };
    filters: {
        search: string;
        borne_by: string;
        payment_status: string;
        category_id: string;
        sort: string;
        direction: 'asc' | 'desc';
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Expenses', href: index() },
        ],
    },
});

const { can } = usePermissions();
const { formatMoney, formatDate } = useFormatting();

const columns: DataTableColumn[] = [
    { key: 'reference_number', label: 'Expense', sortable: true },
    { key: 'vehicle', label: 'Vehicle & Category' },
    { key: 'borne_by', label: 'Cost Bearer' },
    { key: 'amount', label: 'Amount', sortable: true, align: 'right' },
    { key: 'payment_status', label: 'Payment', sortable: true },
    { key: 'expense_date', label: 'Date', sortable: true },
    { key: 'actions', label: 'Actions', align: 'right' },
];

function updateQuery(overrides: Record<string, string | number>): void {
    router.get(
        index.url(),
        {
            search: props.filters.search || undefined,
            borne_by: props.filters.borne_by || undefined,
            payment_status: props.filters.payment_status || undefined,
            category_id: props.filters.category_id || undefined,
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
    <Head title="Vehicle Expenses" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Vehicle Expenses"
            description="Track acquisition preparation, repairs, washing, and maintenance tagged by cost bearer (Company vs. Owner-Deductible)."
        >
            <template v-if="can('expenses.create')" #actions>
                <Button as-child class="min-h-10 w-full sm:w-auto">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Record Expense
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
            <StatCard
                label="Total Recorded"
                :value="formatMoney(summary.total_amount, { compact: true })"
                :icon="Receipt"
                accent-class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200"
            />
            <StatCard
                label="Company-Borne"
                :value="formatMoney(summary.company_total, { compact: true })"
                :icon="Building2"
                accent-class="bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300"
            />
            <StatCard
                label="Owner-Deductible"
                :value="
                    formatMoney(summary.owner_deductible_total, {
                        compact: true,
                    })
                "
                :icon="Handshake"
                accent-class="bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300"
            />
            <StatCard
                label="Unpaid / Partial"
                :value="summary.unpaid_count"
                :icon="AlertCircle"
                accent-class="bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300"
            />
        </div>

        <DataTableCard
            caption="Vehicle expense records"
            :columns="columns"
            :rows="expenses"
            :search="filters.search"
            search-placeholder="Search expense ref, description, vendor, or vehicle..."
            :sort="filters.sort"
            :direction="filters.direction"
            :per-page="filters.per_page"
            empty-title="No vehicle expenses recorded yet"
            empty-description="Record maintenance, washing, or repair expenses against individual vehicles."
            @update:search="(value) => updateQuery({ search: value, page: 1 })"
            @sort="
                (column, dir) =>
                    updateQuery({ sort: column, direction: dir, page: 1 })
            "
            @update:per-page="
                (perPage) => updateQuery({ per_page: perPage, page: 1 })
            "
        >
            <template #filters>
                <select
                    :value="filters.borne_by"
                    aria-label="Filter by cost bearer"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    @change="
                        updateQuery({
                            borne_by: ($event.target as HTMLSelectElement)
                                .value,
                            page: 1,
                        })
                    "
                >
                    <option value="">All Cost Bearers</option>
                    <option
                        v-for="opt in bearerOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>

                <select
                    :value="filters.payment_status"
                    aria-label="Filter by payment status"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    @change="
                        updateQuery({
                            payment_status: ($event.target as HTMLSelectElement)
                                .value,
                            page: 1,
                        })
                    "
                >
                    <option value="">All Payment States</option>
                    <option
                        v-for="opt in statusOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>

                <select
                    :value="filters.category_id"
                    aria-label="Filter by expense category"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    @change="
                        updateQuery({
                            category_id: ($event.target as HTMLSelectElement)
                                .value,
                            page: 1,
                        })
                    "
                >
                    <option value="">All Categories</option>
                    <option
                        v-for="cat in categories"
                        :key="cat.id"
                        :value="String(cat.id)"
                    >
                        {{ cat.name }}
                    </option>
                </select>
            </template>

            <template v-if="can('expenses.create')" #emptyAction>
                <Button as-child class="min-h-10">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Record Expense
                    </Link>
                </Button>
            </template>

            <template #mobile-card="{ row }">
                <div class="space-y-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <Link
                                :href="show(row.id)"
                                class="font-mono text-xs font-semibold text-foreground hover:underline"
                            >
                                {{ row.reference_number }}
                            </Link>
                            <p class="truncate font-semibold text-foreground">
                                {{ row.description }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ row.vehicle_title }} ({{
                                    row.vehicle_reference
                                }})
                            </p>
                        </div>
                        <span
                            class="shrink-0 font-bold text-foreground tabular-nums"
                        >
                            {{ formatMoney(row.amount) }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <StatusBadge
                            :label="row.borne_by_label"
                            :variant="row.borne_by_variant"
                        />
                        <StatusBadge
                            :label="row.payment_status_label"
                            :variant="row.payment_status_variant"
                        />
                    </div>
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        class="min-h-9 w-full"
                    >
                        <Link :href="show(row.id)">
                            <Eye class="mr-1.5 size-3.5" aria-hidden="true" />
                            View Details &amp; Payments
                        </Link>
                    </Button>
                </div>
            </template>

            <template #row="{ row }">
                <td class="px-4 py-3.5">
                    <Link
                        :href="show(row.id)"
                        class="font-mono text-xs font-semibold text-foreground hover:underline"
                    >
                        {{ row.reference_number }}
                    </Link>
                    <div
                        class="max-w-xs truncate text-xs text-muted-foreground"
                    >
                        {{ row.description }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <div class="font-medium text-foreground">
                        {{ row.vehicle_title }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ row.vehicle_reference }} · {{ row.category_name }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="row.borne_by_label"
                        :variant="row.borne_by_variant"
                    />
                </td>
                <td
                    class="px-4 py-3.5 text-right font-semibold text-foreground tabular-nums"
                >
                    <div>{{ formatMoney(row.amount) }}</div>
                    <div
                        v-if="Number(row.balance_due) > 0"
                        class="text-[11px] font-normal text-destructive"
                    >
                        Bal: {{ formatMoney(row.balance_due) }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="row.payment_status_label"
                        :variant="row.payment_status_variant"
                    />
                </td>
                <td
                    class="px-4 py-3.5 text-xs whitespace-nowrap text-muted-foreground tabular-nums"
                >
                    {{ formatDate(row.expense_date) }}
                </td>
                <td class="px-4 py-3.5 text-right">
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        class="min-h-9"
                    >
                        <Link :href="show(row.id)">
                            <Eye class="mr-1.5 size-3.5" aria-hidden="true" />
                            View
                        </Link>
                    </Button>
                </td>
            </template>
        </DataTableCard>
    </div>
</template>
