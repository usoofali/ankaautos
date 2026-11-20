<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Phone, Plus } from '@lucide/vue';
import DataTableCard, {
    type DataTableColumn,
} from '@/components/anka/DataTableCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { create, edit, index, show } from '@/routes/customers';
import type { Paginated } from '@/types';

type CustomerRow = {
    id: number;
    reference_number: string;
    name: string;
    category_label: string;
    phone: string;
    email: string | null;
    inquiries_count: number;
    reservations_count: number;
    sales_count: number;
};

const props = defineProps<{
    customers: Paginated<CustomerRow>;
    filters: {
        search: string;
        sort: string;
        direction: 'asc' | 'desc';
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Customers', href: index() },
        ],
    },
});

const { can } = usePermissions();

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Customer', sortable: true },
    { key: 'phone', label: 'Contact' },
    { key: 'inquiries', label: 'Enquiries', align: 'center' },
    { key: 'reservations', label: 'Reservations', align: 'center' },
    { key: 'sales', label: 'Purchases', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

function updateQuery(overrides: Record<string, string | number>): void {
    router.get(
        index.url(),
        {
            search: props.filters.search || undefined,
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
    <Head title="Customers" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Customers"
            description="Manage prospective and repeat buyers, track enquiries, inspections, reservations, and vehicle sales history."
        >
            <template v-if="can('customers.manage')" #actions>
                <Button as-child class="min-h-10 w-full sm:w-auto">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Register Customer
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <DataTableCard
            caption="Registered dealership customers"
            :columns="columns"
            :rows="customers"
            :search="filters.search"
            search-placeholder="Search customer name, ref, phone, or email..."
            :sort="filters.sort"
            :direction="filters.direction"
            :per-page="filters.per_page"
            empty-title="No customers registered yet"
            empty-description="Register buyers to log vehicle enquiries, reservations, and sales."
            @update:search="(value) => updateQuery({ search: value, page: 1 })"
            @sort="
                (column, dir) =>
                    updateQuery({ sort: column, direction: dir, page: 1 })
            "
            @update:per-page="
                (perPage) => updateQuery({ per_page: perPage, page: 1 })
            "
        >
            <template v-if="can('customers.manage')" #emptyAction>
                <Button as-child class="min-h-10">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Register Customer
                    </Link>
                </Button>
            </template>

            <template #mobile-card="{ row }">
                <div class="space-y-2.5">
                    <div>
                        <Link
                            :href="show(row.id)"
                            class="font-semibold text-foreground hover:underline"
                        >
                            {{ row.name }}
                        </Link>
                        <p class="font-mono text-xs text-muted-foreground">
                            {{ row.reference_number }} ·
                            {{ row.category_label }}
                        </p>
                    </div>
                    <div
                        class="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-1">
                            <Phone class="size-3.5" aria-hidden="true" />
                            {{ row.phone }}
                        </span>
                        <span
                            >{{ row.inquiries_count }} enq ·
                            {{ row.sales_count }} sales</span
                        >
                    </div>
                    <div class="flex gap-2">
                        <Button
                            as-child
                            variant="outline"
                            size="sm"
                            class="min-h-9 flex-1"
                        >
                            <Link :href="show(row.id)">View Profile</Link>
                        </Button>
                        <Button
                            v-if="can('customers.manage')"
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
                        {{ row.reference_number }} · {{ row.category_label }}
                    </div>
                </td>
                <td class="px-4 py-3.5 text-xs">
                    <div class="font-medium tabular-nums">{{ row.phone }}</div>
                    <div v-if="row.email" class="text-muted-foreground">
                        {{ row.email }}
                    </div>
                </td>
                <td class="px-4 py-3.5 text-center tabular-nums">
                    {{ row.inquiries_count }}
                </td>
                <td class="px-4 py-3.5 text-center tabular-nums">
                    {{ row.reservations_count }}
                </td>
                <td class="px-4 py-3.5 text-center font-semibold tabular-nums">
                    {{ row.sales_count }}
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
                            Open
                        </Link>
                    </Button>
                </td>
            </template>
        </DataTableCard>
    </div>
</template>
