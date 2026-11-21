<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Clock,
    Eye,
    KeyRound,
    Plus,
    Undo2,
    Wallet,
} from '@lucide/vue';
import DataTableCard, {
    type DataTableColumn,
} from '@/components/anka/DataTableCard.vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatCard from '@/components/anka/StatCard.vue';
import StatusBadge, {
    type StatusVariant,
} from '@/components/anka/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useFormatting } from '@/composables/useFormatting';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { create, index, show } from '@/routes/withdrawals';
import type { Paginated } from '@/types';

type WithdrawalRow = {
    id: number;
    reference_number: string;
    consignment_reference: string;
    vehicle_title: string;
    owner_name: string;
    total_due: string;
    amount_paid: string;
    balance_due: string;
    status: string;
    status_label: string;
    status_variant: StatusVariant;
    requested_at: string;
};

type EligibleConsignment = {
    id: number;
    reference_number: string;
    vehicle_title: string;
    vehicle_reference: string;
    owner_name: string;
    status_label: string;
    status_variant: StatusVariant;
};

const props = defineProps<{
    withdrawals: Paginated<WithdrawalRow>;
    statusOptions: Array<{ value: string; label: string }>;
    summary: {
        awaiting_payment_count: number;
        ready_for_handover_count: number;
        completed_count: number;
        total_collected: string;
    };
    eligibleConsignments: EligibleConsignment[];
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
            { title: 'Withdrawals', href: index() },
        ],
    },
});

const { can } = usePermissions();
const { formatMoney, formatDate } = useFormatting();

const columns: DataTableColumn[] = [
    { key: 'reference_number', label: 'Reference' },
    { key: 'vehicle_title', label: 'Vehicle & Owner' },
    { key: 'total_due', label: 'Total Due', align: 'right' },
    { key: 'amount_paid', label: 'Paid', align: 'right' },
    { key: 'balance_due', label: 'Balance', align: 'right' },
    { key: 'status', label: 'Status' },
    { key: 'requested_at', label: 'Requested' },
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
    <Head title="Consignment Withdrawals" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Consignment Withdrawals"
            description="Process owner withdrawal requests with strict 100% clearance of withdrawal charges and owner-deductible expenses prior to release."
        >
            <template v-if="can('withdrawals.initiate')" #actions>
                <Button as-child class="min-h-10 w-full sm:w-auto">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Initiate Withdrawal
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                label="Awaiting Payment"
                :value="String(summary.awaiting_payment_count)"
                description="Locked until 100% cleared"
                :icon="Clock"
                tone="warning"
            />
            <StatCard
                label="Ready for Handover"
                :value="String(summary.ready_for_handover_count)"
                description="Paid in full — awaiting release"
                :icon="KeyRound"
                tone="info"
            />
            <StatCard
                label="Completed Withdrawals"
                :value="String(summary.completed_count)"
                description="Returned to vehicle owner"
                :icon="CheckCircle2"
                tone="success"
            />
            <StatCard
                label="Clearance Collected"
                :value="formatMoney(summary.total_collected)"
                description="Recovered expenses & charges"
                :icon="Wallet"
                tone="primary"
            />
        </div>

        <DataTableCard
            caption="Consignment withdrawal requests"
            :columns="columns"
            :rows="withdrawals"
            :search="filters.search"
            search-placeholder="Search withdrawal ref, owner, vehicle..."
            :per-page="filters.per_page"
            empty-title="No consignment withdrawals initiated yet"
            empty-description="Initiate a withdrawal above or select an active consignment below whenever an owner requests to retrieve an unsold vehicle."
            @update:search="
                (v) => updateQuery({ search: v || undefined, page: 1 })
            "
            @update:per-page="(p) => updateQuery({ per_page: p, page: 1 })"
        >
            <template #filters>
                <select
                    :value="filters.status"
                    aria-label="Filter by withdrawal status"
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
                                {{ row.owner_name }}
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
                            <span class="text-muted-foreground">Total Due</span>
                            <span class="shrink-0 font-semibold">
                                {{ formatMoney(row.total_due) }}
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
                            <span class="text-muted-foreground">Balance</span>
                            <span
                                class="shrink-0 font-semibold"
                                :class="
                                    Number(row.balance_due) > 0
                                        ? 'text-destructive'
                                        : 'text-emerald-700 dark:text-emerald-400'
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
                        <Link :href="show(row.id)">Open Withdrawal</Link>
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
                        {{ row.consignment_reference }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <div class="font-medium">{{ row.vehicle_title }}</div>
                    <div class="text-xs text-muted-foreground">
                        Owner: {{ row.owner_name }}
                    </div>
                </td>
                <td
                    class="px-4 py-3.5 text-right font-medium whitespace-nowrap tabular-nums"
                >
                    {{ formatMoney(row.total_due) }}
                </td>
                <td
                    class="px-4 py-3.5 text-right whitespace-nowrap text-emerald-700 tabular-nums dark:text-emerald-400"
                >
                    {{ formatMoney(row.amount_paid) }}
                </td>
                <td
                    class="px-4 py-3.5 text-right font-semibold whitespace-nowrap tabular-nums"
                    :class="
                        Number(row.balance_due) > 0
                            ? 'text-destructive'
                            : 'text-emerald-700 dark:text-emerald-400'
                    "
                >
                    {{ formatMoney(row.balance_due) }}
                </td>
                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="row.status_label"
                        :variant="row.status_variant"
                    />
                </td>
                <td
                    class="px-4 py-3.5 text-xs whitespace-nowrap text-muted-foreground tabular-nums"
                >
                    {{ formatDate(row.requested_at) }}
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

        <!-- Active Consignments Eligible for Quick Withdrawal -->
        <FormSectionCard
            v-if="
                can('withdrawals.initiate') && eligibleConsignments.length > 0
            "
            title="Active Consignments Eligible for Withdrawal"
            description="Quickly initiate a withdrawal request directly for any unsold consigned vehicle currently on stand."
        >
            <ul class="divide-y text-sm">
                <li
                    v-for="c in eligibleConsignments"
                    :key="c.id"
                    class="flex flex-col justify-between gap-3 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="font-mono text-xs font-semibold text-primary"
                            >
                                {{ c.reference_number }}
                            </span>
                            <StatusBadge
                                :label="c.status_label"
                                :variant="c.status_variant"
                            />
                        </div>
                        <p class="mt-0.5 font-medium">
                            {{ c.vehicle_title }} ({{ c.vehicle_reference }})
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Owner: {{ c.owner_name }}
                        </p>
                    </div>
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        class="min-h-9 shrink-0"
                    >
                        <Link
                            :href="create({ query: { consignment_id: c.id } })"
                        >
                            <Undo2 class="mr-1.5 size-3.5" aria-hidden="true" />
                            Initiate Withdrawal
                        </Link>
                    </Button>
                </li>
            </ul>
        </FormSectionCard>
    </div>
</template>
