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
import { create, index, show } from '@/routes/consignments';
import type { Paginated } from '@/types';

type ConsignmentRow = {
    id: number;
    reference_number: string;
    vehicle_id: number;
    vehicle_reference: string;
    vehicle_title: string;
    owner_id: number;
    owner_name: string;
    owner_phone: string | null;
    status: string;
    status_label: string;
    status_variant: StatusVariant;
    agreed_selling_price: string;
    commission_type_label: string;
    commission_value: string;
    agreement_version: number;
    received_at: string;
};

const props = defineProps<{
    consignments: Paginated<ConsignmentRow>;
    statusOptions: Array<{ value: string; label: string; variant: string }>;
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
            { title: 'Consignments', href: index() },
        ],
    },
});

const { can } = usePermissions();
const { formatMoney, formatDate } = useFormatting();

const columns: DataTableColumn[] = [
    { key: 'reference_number', label: 'Consignment', sortable: true },
    { key: 'vehicle', label: 'Vehicle & Owner' },
    { key: 'commission', label: 'Commission' },
    {
        key: 'agreed_selling_price',
        label: 'Agreed Price',
        sortable: true,
        align: 'right',
    },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'received_at', label: 'Received', sortable: true },
    { key: 'actions', label: 'Actions', align: 'right' },
];

function updateQuery(overrides: Record<string, string | number>): void {
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
    <Head title="Consignments" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Consignment Management"
            description="Track third-party consignment agreements, agreed selling prices, commission arrangements, and versioned terms."
        >
            <template v-if="can('consignments.create')" #actions>
                <Button as-child class="min-h-10 w-full sm:w-auto">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        New Consignment
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <DataTableCard
            caption="Third-party vehicle consignment agreements"
            :columns="columns"
            :rows="consignments"
            :search="filters.search"
            search-placeholder="Search consignment ref, owner, or vehicle..."
            :sort="filters.sort"
            :direction="filters.direction"
            :per-page="filters.per_page"
            empty-title="No consignment records yet"
            empty-description="Register a consignment vehicle and link it to a third-party vehicle owner."
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
                    :value="filters.status"
                    aria-label="Filter by consignment status"
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

            <template v-if="can('consignments.create')" #emptyAction>
                <Button as-child class="min-h-10">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        New Consignment
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
                                {{ row.reference_number }} (v{{
                                    row.agreement_version
                                }})
                            </Link>
                            <p class="truncate font-semibold text-foreground">
                                {{ row.vehicle_title }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Owner: {{ row.owner_name }}
                            </p>
                        </div>
                        <StatusBadge
                            :label="row.status_label"
                            :variant="row.status_variant"
                        />
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-muted-foreground">{{
                            row.commission_type_label
                        }}</span>
                        <span
                            class="text-sm font-semibold text-foreground tabular-nums"
                        >
                            {{ formatMoney(row.agreed_selling_price) }}
                        </span>
                    </div>
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        class="min-h-9 w-full"
                    >
                        <Link :href="show(row.id)">
                            <Eye class="mr-1.5 size-3.5" aria-hidden="true" />
                            Open Agreement
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
                    <div class="text-[11px] text-muted-foreground">
                        Agreement v{{ row.agreement_version }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <div class="font-medium text-foreground">
                        {{ row.vehicle_title }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ row.owner_name }}
                        <span v-if="row.owner_phone"
                            >· {{ row.owner_phone }}</span
                        >
                    </div>
                </td>
                <td class="px-4 py-3.5 text-xs text-muted-foreground">
                    {{ row.commission_type_label }}
                </td>
                <td
                    class="px-4 py-3.5 text-right font-semibold text-foreground tabular-nums"
                >
                    {{ formatMoney(row.agreed_selling_price) }}
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
                    {{ formatDate(row.received_at) }}
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
