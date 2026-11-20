<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowUpRight,
    Landmark,
    Scale,
    SlidersHorizontal,
    TrendingUp,
    Wallet,
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
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormatting } from '@/composables/useFormatting';
import { dashboard } from '@/routes';
import { index } from '@/routes/finance';
import { store as storeAdjustment } from '@/routes/finance/adjustments';
import type { Paginated } from '@/types';

type TransactionRow = {
    id: number;
    reference_number: string;
    type_label: string;
    direction: string;
    direction_label: string;
    bucket: string;
    bucket_label: string;
    bucket_variant: StatusVariant;
    amount: string;
    account_name: string;
    method_name: string;
    vehicle_reference: string | null;
    description: string;
    transacted_at: string;
    recorded_by: string;
};

const props = defineProps<{
    transactions: Paginated<TransactionRow>;
    summary: {
        company_inflow: string;
        company_outflow: string;
        company_net: string;
        third_party_held_balance: string;
        customer_receivables: string;
        owner_payables: string;
    };
    canAdjust: boolean;
    financialAccounts: Array<{ id: number; name: string }>;
    filters: {
        search: string;
        bucket: string;
        direction: string;
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Financial Ledger', href: index() },
        ],
    },
});

const { formatMoney, formatDate } = useFormatting();
const adjustOpen = ref(false);

const adjustForm = useForm({
    revenue_bucket: 'company',
    direction: 'inflow',
    amount: '',
    financial_account_id: props.financialAccounts[0]?.id ?? '',
    adjusted_at: new Date().toISOString().slice(0, 10),
    reason: '',
});

const columns: DataTableColumn[] = [
    { key: 'reference_number', label: 'Reference' },
    { key: 'type_label', label: 'Transaction' },
    { key: 'bucket_label', label: 'Bucket' },
    { key: 'account_name', label: 'Account / Method' },
    { key: 'amount', label: 'Amount', align: 'right' },
    { key: 'transacted_at', label: 'Date' },
];

function updateQuery(
    overrides: Record<string, string | number | undefined>,
): void {
    router.get(
        index.url(),
        {
            search: props.filters.search || undefined,
            bucket: props.filters.bucket || undefined,
            direction: props.filters.direction || undefined,
            per_page: props.filters.per_page,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function submitAdjustment(): void {
    adjustForm.post(storeAdjustment.url(), {
        preserveScroll: true,
        onSuccess: () => {
            adjustOpen.value = false;
            adjustForm.reset('amount', 'reason');
        },
    });
}
</script>

<template>
    <Head title="Financial Ledger" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Financial Ledger"
            description="Immutable dealership ledger separating Company Funds from Third-Party Consignment Owner Funds."
        >
            <template v-if="canAdjust" #actions>
                <Button
                    variant="outline"
                    class="min-h-10 w-full sm:w-auto"
                    @click="adjustOpen = true"
                >
                    <SlidersHorizontal
                        class="mr-1.5 size-4"
                        aria-hidden="true"
                    />
                    Post Adjustment
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <StatCard
                label="Company Net Cashflow"
                :value="formatMoney(summary.company_net)"
                :description="`Inflow ${formatMoney(summary.company_inflow)} · Outflow ${formatMoney(summary.company_outflow)}`"
                :icon="TrendingUp"
                tone="primary"
            />
            <StatCard
                label="Third-Party Owner Funds Held"
                :value="formatMoney(summary.third_party_held_balance)"
                description="Consignment sale proceeds held in trust"
                :icon="Landmark"
                tone="info"
            />
            <StatCard
                label="Receivables vs. Owner Payables"
                :value="formatMoney(summary.customer_receivables)"
                :description="`Owner Payables: ${formatMoney(summary.owner_payables)}`"
                :icon="Scale"
                tone="warning"
            />
        </div>

        <DataTableCard
            caption="Immutable financial ledger entries"
            :columns="columns"
            :rows="transactions"
            :search="filters.search"
            search-placeholder="Search reference or description..."
            :per-page="filters.per_page"
            empty-title="No financial transactions posted yet"
            empty-description="Ledger entries are posted automatically when payments, expenses, refunds, or settlements occur."
            @update:search="
                (v) => updateQuery({ search: v || undefined, page: 1 })
            "
            @update:per-page="(p) => updateQuery({ per_page: p, page: 1 })"
        >
            <template #filters>
                <select
                    :value="filters.bucket"
                    aria-label="Filter by revenue bucket"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    @change="
                        updateQuery({
                            bucket:
                                ($event.target as HTMLSelectElement).value ||
                                undefined,
                            page: 1,
                        })
                    "
                >
                    <option value="">All Buckets</option>
                    <option value="company">Company Funds</option>
                    <option value="third_party_owner">
                        Third-Party Owner Funds
                    </option>
                </select>
                <select
                    :value="filters.direction"
                    aria-label="Filter by direction"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    @change="
                        updateQuery({
                            direction:
                                ($event.target as HTMLSelectElement).value ||
                                undefined,
                            page: 1,
                        })
                    "
                >
                    <option value="">Inflow & Outflow</option>
                    <option value="inflow">Inflow Only</option>
                    <option value="outflow">Outflow Only</option>
                </select>
            </template>

            <template #mobile-card="{ row }">
                <div class="space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono text-xs font-semibold">
                                {{ row.reference_number }}
                            </span>
                            <p class="mt-0.5 text-sm font-medium">
                                {{ row.type_label }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ row.description }}
                            </p>
                        </div>
                        <span
                            class="font-semibold tabular-nums"
                            :class="
                                row.direction === 'inflow'
                                    ? 'text-emerald-700 dark:text-emerald-400'
                                    : 'text-destructive'
                            "
                        >
                            {{ row.direction === 'inflow' ? '+' : '-'
                            }}{{ formatMoney(row.amount) }}
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-between border-t pt-2 text-xs"
                    >
                        <StatusBadge
                            :label="row.bucket_label"
                            :variant="row.bucket_variant"
                        />
                        <span class="text-muted-foreground">
                            {{ formatDate(row.transacted_at) }}
                        </span>
                    </div>
                </div>
            </template>

            <template #row="{ row }">
                <td class="px-4 py-3.5">
                    <span class="font-mono text-xs font-semibold">
                        {{ row.reference_number }}
                    </span>
                    <div
                        v-if="row.vehicle_reference"
                        class="text-xs text-muted-foreground"
                    >
                        {{ row.vehicle_reference }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <div class="font-medium">{{ row.type_label }}</div>
                    <div class="line-clamp-1 text-xs text-muted-foreground">
                        {{ row.description }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="row.bucket_label"
                        :variant="row.bucket_variant"
                    />
                </td>
                <td class="px-4 py-3.5">
                    <div class="text-sm">{{ row.account_name }}</div>
                    <div class="text-xs text-muted-foreground">
                        {{ row.method_name }}
                    </div>
                </td>
                <td class="px-4 py-3.5 text-right">
                    <span
                        class="inline-flex items-center gap-1 font-semibold tabular-nums"
                        :class="
                            row.direction === 'inflow'
                                ? 'text-emerald-700 dark:text-emerald-400'
                                : 'text-destructive'
                        "
                    >
                        <ArrowDownLeft
                            v-if="row.direction === 'inflow'"
                            class="size-3.5"
                        />
                        <ArrowUpRight v-else class="size-3.5" />
                        {{ row.direction === 'inflow' ? '+' : '-'
                        }}{{ formatMoney(row.amount) }}
                    </span>
                </td>
                <td class="px-4 py-3.5">
                    <div class="text-xs text-muted-foreground">
                        {{ formatDate(row.transacted_at) }}
                    </div>
                    <div class="text-[11px] text-muted-foreground">
                        {{ row.recorded_by }}
                    </div>
                </td>
            </template>
        </DataTableCard>

        <!-- Authorized Financial Adjustment Modal -->
        <Dialog v-model:open="adjustOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Record Financial Adjustment</DialogTitle>
                    <DialogDescription>
                        Authorized adjustments post an immutable entry to the
                        ledger and audit log.
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitAdjustment">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="adj_bucket">Revenue Bucket</Label>
                            <select
                                id="adj_bucket"
                                v-model="adjustForm.revenue_bucket"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                required
                            >
                                <option value="company">Company Funds</option>
                                <option value="third_party_owner">
                                    Third-Party Owner Funds
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="adj_dir">Direction</Label>
                            <select
                                id="adj_dir"
                                v-model="adjustForm.direction"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                required
                            >
                                <option value="inflow">Inflow (+)</option>
                                <option value="outflow">Outflow (-)</option>
                            </select>
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="adj_amt">Amount (₦)</Label>
                            <Input
                                id="adj_amt"
                                v-model="adjustForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                            />
                            <InputError :message="adjustForm.errors.amount" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="adj_date">Date</Label>
                            <Input
                                id="adj_date"
                                v-model="adjustForm.adjusted_at"
                                type="date"
                                required
                            />
                        </div>
                        <div class="grid gap-1.5 sm:col-span-2">
                            <Label for="adj_acc">Financial Account</Label>
                            <select
                                id="adj_acc"
                                v-model="adjustForm.financial_account_id"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">No Specific Account</option>
                                <option
                                    v-for="a in financialAccounts"
                                    :key="a.id"
                                    :value="a.id"
                                >
                                    {{ a.name }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-1.5 sm:col-span-2">
                            <Label for="adj_reason">
                                Adjustment Reason (Mandatory)
                            </Label>
                            <textarea
                                id="adj_reason"
                                v-model="adjustForm.reason"
                                rows="3"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Explain why this financial adjustment is required..."
                                required
                            />
                            <InputError :message="adjustForm.errors.reason" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="adjustOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="adjustForm.processing">
                            <Spinner
                                v-if="adjustForm.processing"
                                class="mr-2 size-4"
                            />
                            <Wallet class="mr-1.5 size-4" />
                            Post Adjustment
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
