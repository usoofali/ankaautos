<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle2, Eye, Handshake, Lock, Wallet } from '@lucide/vue';
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
import { dashboard } from '@/routes';
import { index, show } from '@/routes/settlements';
import type { Paginated } from '@/types';

type SettlementRow = {
    id: number;
    reference_number: string;
    sale_reference: string;
    vehicle_title: string;
    owner_name: string;
    gross_sale_price: string;
    commission_amount: string;
    net_payable_to_owner: string;
    amount_paid: string;
    balance_due: string;
    status: string;
    status_label: string;
    status_variant: StatusVariant;
    created_at: string | null;
};

const props = defineProps<{
    settlements: Paginated<SettlementRow>;
    statusOptions: Array<{ value: string; label: string }>;
    summary: {
        total_payable: string;
        total_disbursed: string;
        outstanding_balance: string;
        locked_count: number;
    };
    filters: {
        search: string;
        status: string;
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Owner Settlements', href: index() },
        ],
    },
});

const { formatMoney } = useFormatting();

const columns: DataTableColumn[] = [
    { key: 'reference_number', label: 'Settlement' },
    { key: 'vehicle_title', label: 'Vehicle & Owner' },
    { key: 'commission_amount', label: 'Commission', align: 'right' },
    { key: 'net_payable_to_owner', label: 'Net Payable', align: 'right' },
    { key: 'amount_paid', label: 'Disbursed', align: 'right' },
    { key: 'balance_due', label: 'Balance', align: 'right' },
    { key: 'status', label: 'Status' },
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
            per_page: props.filters.per_page,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Owner Settlements" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Consignment Owner Settlements"
            description="Track commissions, owner-deductible expense deductions, and strict cash-first owner payout disbursements."
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                label="Total Net Payable"
                :value="formatMoney(summary.total_payable)"
                :icon="Handshake"
                tone="primary"
            />
            <StatCard
                label="Total Disbursed"
                :value="formatMoney(summary.total_disbursed)"
                :icon="CheckCircle2"
                tone="success"
            />
            <StatCard
                label="Outstanding Payables"
                :value="formatMoney(summary.outstanding_balance)"
                :icon="Wallet"
                tone="warning"
            />
            <StatCard
                label="Locked (Awaiting Buyer)"
                :value="String(summary.locked_count)"
                :icon="Lock"
                tone="info"
            />
        </div>

        <DataTableCard
            caption="Consignment owner settlement records"
            :columns="columns"
            :rows="settlements"
            :search="filters.search"
            search-placeholder="Search settlement ref, owner, vehicle..."
            :per-page="filters.per_page"
            empty-title="No owner settlements yet"
            empty-description="Settlements are automatically generated when consigned vehicles are sold."
            @update:search="
                (v) => updateQuery({ search: v || undefined, page: 1 })
            "
            @update:per-page="(p) => updateQuery({ per_page: p, page: 1 })"
        >
            <template #filters>
                <select
                    :value="filters.status"
                    aria-label="Filter by settlement status"
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
                                {{ row.owner_name }} · {{ row.sale_reference }}
                            </p>
                        </div>
                        <StatusBadge
                            :label="row.status_label"
                            :variant="row.status_variant"
                        />
                    </div>
                    <div
                        class="grid grid-cols-3 gap-2 border-t pt-2 text-xs tabular-nums"
                    >
                        <div>
                            <span class="text-muted-foreground"
                                >Net Payable</span
                            >
                            <p class="font-semibold">
                                {{ formatMoney(row.net_payable_to_owner) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Disbursed</span>
                            <p
                                class="font-semibold text-emerald-700 dark:text-emerald-400"
                            >
                                {{ formatMoney(row.amount_paid) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Balance</span>
                            <p class="font-semibold">
                                {{ formatMoney(row.balance_due) }}
                            </p>
                        </div>
                    </div>
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        class="min-h-9 w-full"
                    >
                        <Link :href="show(row.id)">Open Settlement</Link>
                    </Button>
                </div>
            </template>

            <template #row="{ row }">
                <td class="px-4 py-3.5">
                    <Link
                        :href="show(row.id)"
                        class="font-mono text-sm font-semibold text-primary hover:underline"
                    >
                        {{ row.reference_number }}
                    </Link>
                    <div class="text-xs text-muted-foreground">
                        Sale {{ row.sale_reference }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <div class="font-medium">{{ row.vehicle_title }}</div>
                    <div class="text-xs text-muted-foreground">
                        Owner: {{ row.owner_name }}
                    </div>
                </td>
                <td class="px-4 py-3.5 text-right tabular-nums">
                    {{ formatMoney(row.commission_amount) }}
                </td>
                <td class="px-4 py-3.5 text-right font-medium tabular-nums">
                    {{ formatMoney(row.net_payable_to_owner) }}
                </td>
                <td
                    class="px-4 py-3.5 text-right text-emerald-700 tabular-nums dark:text-emerald-400"
                >
                    {{ formatMoney(row.amount_paid) }}
                </td>
                <td class="px-4 py-3.5 text-right font-semibold tabular-nums">
                    {{ formatMoney(row.balance_due) }}
                </td>
                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="row.status_label"
                        :variant="row.status_variant"
                    />
                </td>
                <td class="px-4 py-3.5 text-right">
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
