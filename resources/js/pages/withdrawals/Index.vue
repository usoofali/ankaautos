<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Plus } from '@lucide/vue';
import DataTableCard, {
    type DataTableColumn,
} from '@/components/anka/DataTableCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
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

const props = defineProps<{
    withdrawals: Paginated<WithdrawalRow>;
    statusOptions: Array<{ value: string; label: string }>;
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
            description="Process owner withdrawal requests with 100% clearance of withdrawal charges and owner-deductible expenses prior to release."
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

        <DataTableCard
            caption="Consignment withdrawal requests"
            :columns="columns"
            :rows="withdrawals"
            :search="filters.search"
            search-placeholder="Search withdrawal ref, owner, vehicle..."
            :per-page="filters.per_page"
            empty-title="No consignment withdrawals"
            empty-description="Initiate a withdrawal whenever an owner requests to retrieve an unsold consigned vehicle."
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
                                {{ row.owner_name }}
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
                            <span class="text-muted-foreground">Due</span>
                            <p class="font-semibold">
                                {{ formatMoney(row.total_due) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Paid</span>
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
                        <Link :href="show(row.id)">Open Withdrawal</Link>
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
                        {{ row.consignment_reference }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <div class="font-medium">{{ row.vehicle_title }}</div>
                    <div class="text-xs text-muted-foreground">
                        Owner: {{ row.owner_name }}
                    </div>
                </td>
                <td class="px-4 py-3.5 text-right font-medium tabular-nums">
                    {{ formatMoney(row.total_due) }}
                </td>
                <td
                    class="px-4 py-3.5 text-right text-emerald-700 tabular-nums dark:text-emerald-400"
                >
                    {{ formatMoney(row.amount_paid) }}
                </td>
                <td
                    class="px-4 py-3.5 text-right font-semibold tabular-nums"
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
                <td class="px-4 py-3.5 text-xs text-muted-foreground">
                    {{ formatDate(row.requested_at) }}
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
