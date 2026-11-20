<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    BadgeDollarSign,
    CalendarCheck2,
    Car,
    FileCheck2,
    Landmark,
    Plus,
    Receipt,
    RotateCcw,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
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
import { create as createSale, show as showSale } from '@/routes/sales';
import { create as createVehicle } from '@/routes/vehicles';

type OperationalMetrics = {
    total_vehicles: number;
    available_vehicles: number;
    reserved_vehicles: number;
    active_consignments: number;
    monthly_sales_count: number;
    pending_withdrawals: number;
    recent_sales: Array<{
        id: number;
        reference_number: string;
        vehicle_title: string;
        customer_name: string;
        final_price: string;
        status_label: string;
        status_variant: StatusVariant;
        confirmed_at: string;
    }>;
    recent_activities: Array<{
        id: number;
        action: string;
        description: string;
        actor: string;
        created_at: string | null;
    }>;
};

type FinancialMetrics = {
    monthly_sales_value: string;
    company_net_cashflow: string;
    customer_receivables: string;
    owner_payables: string;
    monthly_expenses: string;
    third_party_held: string;
    locked_settlements_count: number;
};

defineProps<{
    canViewFinancials: boolean;
    operational?: OperationalMetrics;
    financial?: FinancialMetrics | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const { can } = usePermissions();
const { formatMoney, formatDate, formatDateTime } = useFormatting();
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            title="Dealership Operations Dashboard"
            description="Live overview of Anka Auto Sales inventory, consignments, sales activity, and financial position."
        >
            <template #actions>
                <Button
                    v-if="can('vehicles.create')"
                    as-child
                    variant="outline"
                    class="min-h-10"
                >
                    <Link :href="createVehicle()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Register Vehicle
                    </Link>
                </Button>
                <Button
                    v-if="can('sales.create')"
                    as-child
                    class="min-h-10"
                >
                    <Link :href="createSale()">
                        <BadgeDollarSign
                            class="mr-1.5 size-4"
                            aria-hidden="true"
                        />
                        Record Sale
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <!-- Operational KPIs (Deferred with skeleton) -->
        <Deferred data="operational">
            <template #fallback>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="i in 4"
                        :key="i"
                        class="h-28 animate-pulse rounded-xl border bg-muted/40"
                    />
                </div>
            </template>

            <div
                v-if="operational"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <StatCard
                    label="Available Vehicles"
                    :value="String(operational.available_vehicles)"
                    :description="`${operational.total_vehicles} total active stock`"
                    :icon="Car"
                    tone="primary"
                />
                <StatCard
                    label="Reserved Vehicles"
                    :value="String(operational.reserved_vehicles)"
                    description="Deposit-backed reservations"
                    :icon="CalendarCheck2"
                    tone="info"
                />
                <StatCard
                    label="Active Consignments"
                    :value="String(operational.active_consignments)"
                    :description="`${operational.pending_withdrawals} pending withdrawals`"
                    :icon="FileCheck2"
                    tone="warning"
                />
                <StatCard
                    label="Sales This Month"
                    :value="String(operational.monthly_sales_count)"
                    description="Confirmed & completed sales"
                    :icon="BadgeDollarSign"
                    tone="success"
                />
            </div>
        </Deferred>

        <!-- Financial KPIs (Permission-gated + Deferred) -->
        <Deferred v-if="canViewFinancials" data="financial">
            <template #fallback>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="i in 4"
                        :key="i"
                        class="h-28 animate-pulse rounded-xl border bg-muted/40"
                    />
                </div>
            </template>

            <div
                v-if="financial"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <StatCard
                    label="Monthly Sales Value"
                    :value="formatMoney(financial.monthly_sales_value)"
                    :description="`Company Net: ${formatMoney(financial.company_net_cashflow)}`"
                    :icon="TrendingUp"
                    tone="success"
                />
                <StatCard
                    label="Customer Receivables"
                    :value="formatMoney(financial.customer_receivables)"
                    description="Unpaid installment balances"
                    :icon="Wallet"
                    tone="warning"
                />
                <StatCard
                    label="Owner Payables"
                    :value="formatMoney(financial.owner_payables)"
                    :description="`${financial.locked_settlements_count} locked awaiting buyer payment`"
                    :icon="Landmark"
                    tone="info"
                />
                <StatCard
                    label="Monthly Vehicle Expenses"
                    :value="formatMoney(financial.monthly_expenses)"
                    :description="`Owner Funds Held: ${formatMoney(financial.third_party_held)}`"
                    :icon="Receipt"
                    tone="danger"
                />
            </div>
        </Deferred>

        <!-- Recent Sales & Activity Feed -->
        <Deferred data="operational">
            <template #fallback>
                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="h-64 animate-pulse rounded-xl border bg-muted/40" />
                    <div class="h-64 animate-pulse rounded-xl border bg-muted/40" />
                </div>
            </template>

            <div v-if="operational" class="grid gap-5 lg:grid-cols-2">
                <FormSectionCard
                    title="Recent Vehicle Sales"
                    description="Latest confirmed and completed dealership sales."
                >
                    <p
                        v-if="operational.recent_sales.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        No vehicle sales recorded yet.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="sale in operational.recent_sales"
                            :key="sale.id"
                            class="flex items-center justify-between gap-3 py-3"
                        >
                            <div class="min-w-0">
                                <Link
                                    :href="showSale(sale.id)"
                                    class="font-mono text-xs font-semibold text-primary hover:underline"
                                >
                                    {{ sale.reference_number }}
                                </Link>
                                <p class="truncate font-medium">
                                    {{ sale.vehicle_title }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ sale.customer_name }} ·
                                    {{ formatDate(sale.confirmed_at) }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="font-semibold tabular-nums">
                                    {{ formatMoney(sale.final_price) }}
                                </p>
                                <StatusBadge
                                    :label="sale.status_label"
                                    :variant="sale.status_variant"
                                />
                            </div>
                        </li>
                    </ul>
                </FormSectionCard>

                <FormSectionCard
                    title="Recent Operational Activity"
                    description="Live immutable trail of staff actions across modules."
                >
                    <p
                        v-if="operational.recent_activities.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        No recent activity recorded yet.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="act in operational.recent_activities"
                            :key="act.id"
                            class="flex items-start gap-3 py-2.5"
                        >
                            <Activity
                                class="mt-0.5 size-4 shrink-0 text-primary"
                                aria-hidden="true"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-foreground">
                                    {{ act.description }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ act.actor }} ·
                                    {{ formatDateTime(act.created_at) }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </FormSectionCard>
            </div>
        </Deferred>

        <div
            v-if="operational && operational.pending_withdrawals > 0"
            class="flex items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200"
        >
            <RotateCcw class="size-4 shrink-0" />
            <span>
                There {{ operational.pending_withdrawals === 1 ? 'is' : 'are' }}
                <strong>{{ operational.pending_withdrawals }}</strong> pending
                consignment withdrawal request{{
                    operational.pending_withdrawals === 1 ? '' : 's'
                }}
                awaiting payment clearance or handover.
            </span>
        </div>
    </div>
</template>
