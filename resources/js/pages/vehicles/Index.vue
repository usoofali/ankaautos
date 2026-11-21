<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Building2,
    Car,
    CheckCircle2,
    Eye,
    Handshake,
    MoreHorizontal,
    Pencil,
    Plus,
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useFormatting } from '@/composables/useFormatting';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { create, edit, index, show } from '@/routes/vehicles';
import type { Paginated } from '@/types';

type VehicleRow = {
    id: number;
    reference_number: string;
    title: string;
    year: number;
    exterior_colour: string;
    ownership_type: string;
    ownership_label: string;
    owner_name: string | null;
    status: string;
    status_label: string;
    status_variant: StatusVariant;
    condition_name: string;
    condition_variant: StatusVariant;
    category_name: string;
    advertised_price: string | null;
    location: string | null;
    received_at: string;
    primary_photo_url: string | null;
};

const props = defineProps<{
    vehicles: Paginated<VehicleRow>;
    ownershipOptions: Array<{ value: string; label: string }>;
    statusOptions: Array<{ value: string; label: string; variant: string }>;
    conditions: Array<{ id: number; name: string }>;
    summary: {
        total: number;
        available: number;
        company_owned: number;
        consignment: number;
    };
    filters: {
        search: string;
        ownership: string;
        status: string;
        condition_id: string;
        sort: string;
        direction: 'asc' | 'desc';
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Inventory', href: index() },
        ],
    },
});

const { can } = usePermissions();
const { formatMoney, formatDate } = useFormatting();

const columns: DataTableColumn[] = [
    { key: 'reference_number', label: 'Vehicle', sortable: true },
    { key: 'ownership', label: 'Ownership' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'condition', label: 'Condition' },
    {
        key: 'advertised_price',
        label: 'Asking Price',
        sortable: true,
        align: 'right',
    },
    { key: 'received_at', label: 'Received', sortable: true },
    { key: 'actions', label: 'Actions', align: 'right' },
];

function updateQuery(overrides: Record<string, string | number>): void {
    router.get(
        index.url(),
        {
            search: props.filters.search || undefined,
            ownership: props.filters.ownership || undefined,
            status: props.filters.status || undefined,
            condition_id: props.filters.condition_id || undefined,
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
    <Head title="Vehicle Inventory" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Vehicle Inventory"
            description="Central registry of all company-owned and third-party consignment vehicles on the car stand."
        >
            <template v-if="can('vehicles.create')" #actions>
                <Button as-child class="min-h-10 w-full sm:w-auto">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Register Vehicle
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 xl:gap-4">
            <StatCard
                label="Total Registered"
                :value="summary.total"
                :icon="Car"
                accent-class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200"
            />
            <StatCard
                label="Available"
                :value="summary.available"
                :icon="CheckCircle2"
                accent-class="bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300"
            />
            <StatCard
                label="Company-Owned"
                :value="summary.company_owned"
                :icon="Building2"
                accent-class="bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300"
            />
            <StatCard
                label="Consignment"
                :value="summary.consignment"
                :icon="Handshake"
                accent-class="bg-indigo-100 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300"
            />
        </div>

        <DataTableCard
            caption="Registered dealership vehicles"
            :columns="columns"
            :rows="vehicles"
            :search="filters.search"
            search-placeholder="Search ref, make, model, VIN, reg, or owner..."
            :sort="filters.sort"
            :direction="filters.direction"
            :per-page="filters.per_page"
            empty-title="No vehicles have been registered yet"
            empty-description="Register your first company-owned or third-party consignment vehicle to begin tracking inventory."
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
                    :value="filters.ownership"
                    aria-label="Filter by ownership"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    @change="
                        updateQuery({
                            ownership: ($event.target as HTMLSelectElement)
                                .value,
                            page: 1,
                        })
                    "
                >
                    <option value="">All Ownership</option>
                    <option
                        v-for="opt in ownershipOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>

                <select
                    :value="filters.status"
                    aria-label="Filter by status"
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

                <select
                    :value="filters.condition_id"
                    aria-label="Filter by condition"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    @change="
                        updateQuery({
                            condition_id: ($event.target as HTMLSelectElement)
                                .value,
                            page: 1,
                        })
                    "
                >
                    <option value="">All Conditions</option>
                    <option
                        v-for="cond in conditions"
                        :key="cond.id"
                        :value="String(cond.id)"
                    >
                        {{ cond.name }}
                    </option>
                </select>
            </template>

            <template v-if="can('vehicles.create')" #emptyAction>
                <Button as-child class="min-h-10">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Register Vehicle
                    </Link>
                </Button>
            </template>

            <template #mobile-card="{ row }">
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted"
                        >
                            <img
                                v-if="row.primary_photo_url"
                                :src="row.primary_photo_url"
                                :alt="row.title"
                                class="h-full w-full object-cover"
                            />
                            <Car
                                v-else
                                class="size-6 text-muted-foreground"
                                aria-hidden="true"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <Link
                                :href="show(row.id)"
                                class="block truncate font-semibold text-foreground hover:underline"
                            >
                                {{ row.title }}
                            </Link>
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ row.reference_number }} ·
                                {{ row.exterior_colour }}
                            </p>
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                <StatusBadge
                                    :label="row.status_label"
                                    :variant="row.status_variant"
                                />
                                <StatusBadge
                                    :label="row.ownership_label"
                                    :variant="
                                        row.ownership_type === 'company_owned'
                                            ? 'info'
                                            : 'pending'
                                    "
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span>
                            {{
                                row.owner_name
                                    ? `Owner: ${row.owner_name}`
                                    : row.location || row.category_name
                            }}
                        </span>
                        <span
                            class="text-sm font-semibold text-foreground tabular-nums"
                        >
                            {{
                                row.advertised_price
                                    ? formatMoney(row.advertised_price)
                                    : 'Price TBD'
                            }}
                        </span>
                    </div>

                    <div class="flex gap-2">
                        <Button
                            as-child
                            variant="outline"
                            size="sm"
                            class="min-h-9 flex-1"
                        >
                            <Link :href="show(row.id)">
                                <Eye
                                    class="mr-1.5 size-3.5"
                                    aria-hidden="true"
                                />
                                View Record
                            </Link>
                        </Button>
                        <Button
                            v-if="can('vehicles.edit')"
                            as-child
                            variant="outline"
                            size="sm"
                            class="min-h-9"
                        >
                            <Link :href="edit(row.id)">
                                <Pencil class="size-3.5" aria-hidden="true" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </template>

            <template #row="{ row }">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted"
                        >
                            <img
                                v-if="row.primary_photo_url"
                                :src="row.primary_photo_url"
                                :alt="row.title"
                                class="h-full w-full object-cover"
                            />
                            <Car
                                v-else
                                class="size-5 text-muted-foreground"
                                aria-hidden="true"
                            />
                        </div>
                        <div class="min-w-0">
                            <Link
                                :href="show(row.id)"
                                class="block truncate font-semibold text-foreground hover:underline"
                            >
                                {{ row.title }}
                            </Link>
                            <div
                                class="flex items-center gap-2 text-xs text-muted-foreground"
                            >
                                <span class="font-mono">{{
                                    row.reference_number
                                }}</span>
                                <span>· {{ row.exterior_colour }}</span>
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="text-xs font-medium text-foreground">
                        {{ row.ownership_label }}
                    </div>
                    <div
                        v-if="row.owner_name"
                        class="text-xs text-muted-foreground"
                    >
                        {{ row.owner_name }}
                    </div>
                </td>
                <td class="px-4 py-3">
                    <StatusBadge
                        :label="row.status_label"
                        :variant="row.status_variant"
                    />
                </td>
                <td class="px-4 py-3">
                    <StatusBadge
                        :label="row.condition_name"
                        :variant="row.condition_variant"
                    />
                </td>
                <td
                    class="px-4 py-3 text-right font-semibold text-foreground tabular-nums"
                >
                    {{
                        row.advertised_price
                            ? formatMoney(row.advertised_price)
                            : '—'
                    }}
                </td>
                <td
                    class="px-4 py-3 text-xs whitespace-nowrap text-muted-foreground tabular-nums"
                >
                    {{ formatDate(row.received_at) }}
                </td>
                <td class="px-4 py-3 text-right">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="size-9"
                                :aria-label="`Actions for ${row.reference_number}`"
                            >
                                <MoreHorizontal
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem as-child>
                                <Link :href="show(row.id)">
                                    <Eye
                                        class="mr-2 size-4"
                                        aria-hidden="true"
                                    />
                                    Open Vehicle Record
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="can('vehicles.edit')"
                                as-child
                            >
                                <Link :href="edit(row.id)">
                                    <Pencil
                                        class="mr-2 size-4"
                                        aria-hidden="true"
                                    />
                                    Edit Specifications
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </td>
            </template>
        </DataTableCard>
    </div>
</template>
