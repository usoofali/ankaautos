<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Building2,
    Eye,
    MoreHorizontal,
    Pencil,
    Phone,
    Plus,
    User,
} from '@lucide/vue';
import DataTableCard, {
    type DataTableColumn,
} from '@/components/anka/DataTableCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatusBadge from '@/components/anka/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { create, edit, index, show } from '@/routes/owners';
import type { Paginated } from '@/types';

type OwnerRow = {
    id: number;
    reference_number: string;
    name: string;
    owner_category: string;
    category_label: string;
    phone: string;
    email: string | null;
    bank_name: string | null;
    consignments_count: number;
    is_active: boolean;
    created_at: string | null;
};

const props = defineProps<{
    owners: Paginated<OwnerRow>;
    categoryOptions: Array<{ value: string; label: string }>;
    filters: {
        search: string;
        category: string;
        sort: string;
        direction: 'asc' | 'desc';
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Owners', href: index() },
        ],
    },
});

const { can } = usePermissions();

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Owner', sortable: true },
    { key: 'category', label: 'Category' },
    { key: 'phone', label: 'Contact' },
    { key: 'consignments', label: 'Consignments', align: 'center' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

function updateQuery(overrides: Record<string, string | number>): void {
    router.get(
        index.url(),
        {
            search: props.filters.search || undefined,
            category: props.filters.category || undefined,
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
    <Head title="Vehicle Owners" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Third-Party Vehicle Owners"
            description="Individuals and organizations who entrust vehicles to Anka Auto Sales on consignment."
        >
            <template v-if="can('owners.manage')" #actions>
                <Button as-child class="min-h-10 w-full sm:w-auto">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Register Owner
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <DataTableCard
            caption="Registered third-party vehicle owners"
            :columns="columns"
            :rows="owners"
            :search="filters.search"
            search-placeholder="Search owner name, ref, phone, or email..."
            :sort="filters.sort"
            :direction="filters.direction"
            :per-page="filters.per_page"
            empty-title="No vehicle owners registered yet"
            empty-description="Register third-party owners before creating consignment agreements."
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
                    :value="filters.category"
                    aria-label="Filter by owner category"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    @change="
                        updateQuery({
                            category: ($event.target as HTMLSelectElement)
                                .value,
                            page: 1,
                        })
                    "
                >
                    <option value="">All Categories</option>
                    <option
                        v-for="opt in categoryOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>
            </template>

            <template v-if="can('owners.manage')" #emptyAction>
                <Button as-child class="min-h-10">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Register Owner
                    </Link>
                </Button>
            </template>

            <template #mobile-card="{ row }">
                <div class="space-y-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <Link
                                :href="show(row.id)"
                                class="block truncate font-semibold text-foreground hover:underline"
                            >
                                {{ row.name }}
                            </Link>
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ row.reference_number }} ·
                                {{ row.category_label }}
                            </p>
                        </div>
                        <StatusBadge
                            :label="row.is_active ? 'Active' : 'Inactive'"
                            :variant="row.is_active ? 'success' : 'neutral'"
                        />
                    </div>
                    <div
                        class="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-1">
                            <Phone class="size-3.5" aria-hidden="true" />
                            {{ row.phone }}
                        </span>
                        <span class="font-medium text-foreground">
                            {{ row.consignments_count }} consignment(s)
                        </span>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <Button
                            as-child
                            variant="outline"
                            size="sm"
                            class="min-h-9 flex-1"
                        >
                            <Link :href="show(row.id)">View Profile</Link>
                        </Button>
                        <Button
                            v-if="can('owners.manage')"
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
                <td class="px-4 py-3.5">
                    <Link
                        :href="show(row.id)"
                        class="font-semibold text-foreground hover:underline"
                    >
                        {{ row.name }}
                    </Link>
                    <div class="font-mono text-xs text-muted-foreground">
                        {{ row.reference_number }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <span
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-foreground"
                    >
                        <component
                            :is="
                                row.owner_category === 'organization'
                                    ? Building2
                                    : User
                            "
                            class="size-3.5 text-muted-foreground"
                            aria-hidden="true"
                        />
                        {{ row.category_label }}
                    </span>
                </td>
                <td class="px-4 py-3.5 text-xs">
                    <div class="font-medium text-foreground tabular-nums">
                        {{ row.phone }}
                    </div>
                    <div v-if="row.email" class="text-muted-foreground">
                        {{ row.email }}
                    </div>
                </td>
                <td
                    class="px-4 py-3.5 text-center font-semibold text-foreground tabular-nums"
                >
                    {{ row.consignments_count }}
                </td>
                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="row.is_active ? 'Active' : 'Inactive'"
                        :variant="row.is_active ? 'success' : 'neutral'"
                    />
                </td>
                <td class="px-4 py-3.5 text-right">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="size-9"
                                :aria-label="`Actions for ${row.name}`"
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
                                    View Profile &amp; Vehicles
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="can('owners.manage')"
                                as-child
                            >
                                <Link :href="edit(row.id)">
                                    <Pencil
                                        class="mr-2 size-4"
                                        aria-hidden="true"
                                    />
                                    Edit Owner
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </td>
            </template>
        </DataTableCard>
    </div>
</template>
