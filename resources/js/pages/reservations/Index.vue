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
import { create, index, show } from '@/routes/reservations';
import type { Paginated } from '@/types';

type ReservationRow = {
    id: number;
    reference_number: string;
    vehicle_reference: string;
    vehicle_title: string;
    customer_name: string;
    customer_phone: string | null;
    agreed_price: string;
    deposit_amount: string;
    status_label: string;
    status_variant: StatusVariant;
    reserved_at: string;
};

const props = defineProps<{
    reservations: Paginated<ReservationRow>;
    statusOptions: Array<{ value: string; label: string }>;
    filters: { search: string; status: string; per_page: number };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Reservations', href: index() },
        ],
    },
});

const { can } = usePermissions();
const { formatMoney, formatDate } = useFormatting();

const columns: DataTableColumn[] = [
    { key: 'reference_number', label: 'Reservation' },
    { key: 'vehicle', label: 'Vehicle & Buyer' },
    { key: 'deposit', label: 'Deposit Paid', align: 'right' },
    { key: 'agreed_price', label: 'Agreed Price', align: 'right' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

function updateQuery(overrides: Record<string, string | number>): void {
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
    <Head title="Vehicle Reservations" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Vehicle Reservations"
            description="Deposit-backed vehicle holds. Active reservations remain open until manually converted to a sale or cancelled."
        >
            <template v-if="can('reservations.manage')" #actions>
                <Button as-child class="min-h-10 w-full sm:w-auto">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        New Reservation
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <DataTableCard
            caption="Vehicle reservation records"
            :columns="columns"
            :rows="reservations"
            :search="filters.search"
            search-placeholder="Search reservation ref, buyer, or vehicle..."
            :per-page="filters.per_page"
            empty-title="No reservations recorded yet"
            empty-description="Reserve an available vehicle with a mandatory customer deposit."
            @update:search="(v) => updateQuery({ search: v, page: 1 })"
            @update:per-page="(p) => updateQuery({ per_page: p, page: 1 })"
        >
            <template #filters>
                <select
                    :value="filters.status"
                    aria-label="Filter by reservation status"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    @change="
                        updateQuery({
                            status: ($event.target as HTMLSelectElement).value,
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
                                class="font-mono text-xs font-semibold hover:underline"
                            >
                                {{ row.reference_number }}
                            </Link>
                            <p class="font-semibold">{{ row.vehicle_title }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ row.customer_name }}
                            </p>
                        </div>
                        <StatusBadge
                            :label="row.status_label"
                            :variant="row.status_variant"
                        />
                    </div>
                    <div class="flex justify-between text-xs tabular-nums">
                        <span
                            >Deposit:
                            <strong>{{
                                formatMoney(row.deposit_amount)
                            }}</strong></span
                        >
                        <span>Price: {{ formatMoney(row.agreed_price) }}</span>
                    </div>
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        class="min-h-9 w-full"
                    >
                        <Link :href="show(row.id)">Open Reservation</Link>
                    </Button>
                </div>
            </template>

            <template #row="{ row }">
                <td class="px-4 py-3.5">
                    <Link
                        :href="show(row.id)"
                        class="font-mono text-xs font-semibold hover:underline"
                    >
                        {{ row.reference_number }}
                    </Link>
                    <div class="text-[11px] text-muted-foreground">
                        {{ formatDate(row.reserved_at) }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <div class="font-medium">{{ row.vehicle_title }}</div>
                    <div class="text-xs text-muted-foreground">
                        {{ row.customer_name }}
                    </div>
                </td>
                <td class="px-4 py-3.5 text-right font-semibold tabular-nums">
                    {{ formatMoney(row.deposit_amount) }}
                </td>
                <td class="px-4 py-3.5 text-right tabular-nums">
                    {{ formatMoney(row.agreed_price) }}
                </td>
                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="row.status_label"
                        :variant="row.status_variant"
                    />
                </td>
                <td class="px-4 py-3.5 text-right">
                    <Button as-child variant="outline" size="sm" class="min-h-9">
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
