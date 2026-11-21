<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
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
import { index as auditLogsIndex } from '@/routes/admin/audit-logs';
import { index as consignmentsIndex } from '@/routes/consignments';
import { index as expensesIndex } from '@/routes/expenses';
import { index as reservationsIndex } from '@/routes/reservations';
import {
    create as createSale,
    index as salesIndex,
    show as showSale,
} from '@/routes/sales';
import { index as settlementsIndex } from '@/routes/settlements';
import {
    create as createVehicle,
    index as vehiclesIndex,
} from '@/routes/vehicles';
import { index as withdrawalsIndex } from '@/routes/withdrawals';

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
                <Button v-if="can('sales.create')" as-child class="min-h-10">
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

        <!-- Operational KPIs (Clickable + Deferred with skeleton) -->
        <section class="space-y-3" aria-label="Operational metrics">
            <h2
                class="px-0.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Inventory &amp; Sales Pulse
            </h2>
            <Deferred data="operational">
                <template #fallback>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div
                            v-for="i in 4"
                            :key="i"
                            class="h-30 animate-pulse rounded-xl border bg-card/60"
                        />
                    </div>
                </template>

                <div
                    v-if="operational"
                    class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <StatCard
                        label="Available Vehicles"
                        :value="String(operational.available_vehicles)"
                        :description="`${operational.total_vehicles} total active stock`"
                        :icon="Car"
                        tone="primary"
                        :href="
                            can('vehicles.view')
                                ? vehiclesIndex({
                                      query: { status: 'available' },
                                  })
                                : undefined
                        "
                    />
                    <StatCard
                        label="Reserved Vehicles"
                        :value="String(operational.reserved_vehicles)"
                        description="Deposit-backed reservations"
                        :icon="CalendarCheck2"
                        tone="info"
                        :href="
                            can('reservations.view')
                                ? reservationsIndex()
                                : can('vehicles.view')
                                  ? vehiclesIndex({
                                        query: { status: 'reserved' },
                                    })
                                  : undefined
                        "
                    />
                    <StatCard
                        label="Active Consignments"
                        :value="String(operational.active_consignments)"
                        :description="`${operational.pending_withdrawals} pending withdrawals`"
                        :icon="FileCheck2"
                        tone="warning"
                        :href="
                            can('consignments.view')
                                ? consignmentsIndex({
                                      query: { status: 'active' },
                                  })
                                : undefined
                        "
                    />
                    <StatCard
                        label="Sales This Month"
                        :value="String(operational.monthly_sales_count)"
                        description="Confirmed & completed sales"
                        :icon="BadgeDollarSign"
                        tone="success"
                        :href="can('sales.view') ? salesIndex() : undefined"
                    />
                </div>
            </Deferred>
        </section>

        <!-- Financial KPIs (Permission-gated + Clickable + Deferred) -->
        <section
            v-if="canViewFinancials"
            class="space-y-3"
            aria-label="Financial metrics"
        >
            <h2
                class="px-0.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Financial Snapshot
            </h2>
            <Deferred data="financial">
                <template #fallback>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div
                            v-for="i in 4"
                            :key="i"
                            class="h-30 animate-pulse rounded-xl border bg-card/60"
                        />
                    </div>
                </template>

                <div
                    v-if="financial"
                    class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <StatCard
                        label="Monthly Sales Value"
                        :value="formatMoney(financial.monthly_sales_value)"
                        :description="`Company Net: ${formatMoney(financial.company_net_cashflow)}`"
                        :icon="TrendingUp"
                        tone="success"
                        :href="can('sales.view') ? salesIndex() : undefined"
                    />
                    <StatCard
                        label="Customer Receivables"
                        :value="formatMoney(financial.customer_receivables)"
                        description="Unpaid installment balances"
                        :icon="Wallet"
                        tone="warning"
                        :href="
                            can('sales.view')
                                ? salesIndex({ query: { status: 'confirmed' } })
                                : undefined
                        "
                    />
                    <StatCard
                        label="Owner Payables"
                        :value="formatMoney(financial.owner_payables)"
                        :description="`${financial.locked_settlements_count} locked awaiting buyer payment`"
                        :icon="Landmark"
                        tone="info"
                        :href="
                            can('settlements.view')
                                ? settlementsIndex()
                                : undefined
                        "
                    />
                    <StatCard
                        label="Monthly Vehicle Expenses"
                        :value="formatMoney(financial.monthly_expenses)"
                        :description="`Owner Funds Held: ${formatMoney(financial.third_party_held)}`"
                        :icon="Receipt"
                        tone="danger"
                        :href="
                            can('expenses.view') ? expensesIndex() : undefined
                        "
                    />
                </div>
            </Deferred>
        </section>

        <!-- Pending Withdrawals Actionable Alert -->
        <Link
            v-if="
                operational &&
                operational.pending_withdrawals > 0 &&
                can('withdrawals.view')
            "
            :href="withdrawalsIndex()"
            class="group flex items-center justify-between gap-3 rounded-xl border border-amber-300/90 bg-amber-50/90 px-4 py-3.5 text-sm text-amber-950 shadow-xs transition hover:border-amber-400 hover:bg-amber-50 dark:border-amber-900/70 dark:bg-amber-950/35 dark:text-amber-200"
        >
            <div class="flex items-center gap-2.5">
                <RotateCcw
                    class="size-4 shrink-0 text-amber-600 dark:text-amber-400"
                />
                <span>
                    There
                    {{ operational.pending_withdrawals === 1 ? 'is' : 'are' }}
                    <strong>{{ operational.pending_withdrawals }}</strong>
                    pending consignment withdrawal request{{
                        operational.pending_withdrawals === 1 ? '' : 's'
                    }}
                    awaiting payment clearance or handover.
                </span>
            </div>
            <span
                class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold"
            >
                Review
                <ArrowRight
                    class="size-3.5 transition-transform group-hover:translate-x-0.5"
                />
            </span>
        </Link>

        <!-- Recent Sales & Activity Feed -->
        <Deferred data="operational">
            <template #fallback>
                <div class="grid gap-5 lg:grid-cols-2">
                    <div
                        class="h-64 animate-pulse rounded-xl border bg-card/60"
                    />
                    <div
                        class="h-64 animate-pulse rounded-xl border bg-card/60"
                    />
                </div>
            </template>

            <div v-if="operational" class="grid gap-5 lg:grid-cols-2">
                <FormSectionCard
                    title="Recent Vehicle Sales"
                    description="Latest confirmed and completed dealership sales."
                >
                    <template v-if="can('sales.view')" #headerAction>
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="salesIndex()">
                                View all
                                <ArrowRight class="ml-1 size-3.5" />
                            </Link>
                        </Button>
                    </template>

                    <p
                        v-if="operational.recent_sales.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No vehicle sales recorded yet.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="sale in operational.recent_sales"
                            :key="sale.id"
                            class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
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
                    <template v-if="can('audit.view')" #headerAction>
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="auditLogsIndex()">
                                Audit logs
                                <ArrowRight class="ml-1 size-3.5" />
                            </Link>
                        </Button>
                    </template>

                    <p
                        v-if="operational.recent_activities.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No recent activity recorded yet.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="act in operational.recent_activities"
                            :key="act.id"
                            class="flex items-start gap-3 py-2.5 first:pt-0 last:pb-0"
                        >
                            <span
                                class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <Activity class="size-3.5" aria-hidden="true" />
                            </span>
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
    </div>
</template>
