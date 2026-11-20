<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    Shield,
    UserCheck,
} from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/anka/ConfirmDialog.vue';
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
import { useFormatting } from '@/composables/useFormatting';
import { dashboard } from '@/routes';
import { create, edit, index, toggleStatus } from '@/routes/admin/users';
import type { Paginated } from '@/types';

type StaffRow = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    role_name: string | null;
    role_display_name: string;
    last_login_at: string | null;
    created_at: string | null;
};

type RoleOption = {
    name: string;
    display_name: string;
    description: string | null;
};

const props = defineProps<{
    users: Paginated<StaffRow>;
    roles: RoleOption[];
    filters: {
        search: string;
        role: string;
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
            { title: 'Users & Staff', href: index() },
        ],
    },
});

const page = usePage();
const { formatRelativeTime } = useFormatting();

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Staff Member', sortable: true },
    { key: 'role', label: 'Role' },
    { key: 'phone', label: 'Phone' },
    { key: 'is_active', label: 'Status', sortable: true },
    { key: 'last_login_at', label: 'Last Sign-In', sortable: true },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const toggleCandidate = ref<StaffRow | null>(null);
const toggling = ref(false);

function updateQuery(overrides: Record<string, string | number>): void {
    router.get(
        index.url(),
        {
            search: props.filters.search || undefined,
            role: props.filters.role || undefined,
            status: props.filters.status || undefined,
            sort: props.filters.sort,
            direction: props.filters.direction,
            per_page: props.filters.per_page,
            ...overrides,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function confirmToggleStatus(): void {
    if (!toggleCandidate.value) {
        return;
    }

    toggling.value = true;
    router.patch(
        toggleStatus.url(toggleCandidate.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                toggling.value = false;
                toggleCandidate.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Users & Staff" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Users & Staff"
            description="Create staff accounts, assign dealership roles, and activate or deactivate access."
        >
            <template #actions>
                <Button as-child class="min-h-10 w-full sm:w-auto">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Add Staff Member
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <DataTableCard
            caption="Registered dealership staff accounts"
            :columns="columns"
            :rows="users"
            :search="filters.search"
            search-placeholder="Search name, email, or phone..."
            :sort="filters.sort"
            :direction="filters.direction"
            :per-page="filters.per_page"
            empty-title="No staff accounts found"
            empty-description="No users matched your search or filter selection."
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
                    :value="filters.role"
                    aria-label="Filter by role"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                    @change="
                        updateQuery({
                            role: ($event.target as HTMLSelectElement).value,
                            page: 1,
                        })
                    "
                >
                    <option value="">All Roles</option>
                    <option
                        v-for="role in roles"
                        :key="role.name"
                        :value="role.name"
                    >
                        {{ role.display_name }}
                    </option>
                </select>

                <select
                    :value="filters.status"
                    aria-label="Filter by account status"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                    @change="
                        updateQuery({
                            status: ($event.target as HTMLSelectElement).value,
                            page: 1,
                        })
                    "
                >
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </template>

            <template #emptyAction>
                <Button as-child class="min-h-10">
                    <Link :href="create()">
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        Add Staff Member
                    </Link>
                </Button>
            </template>

            <template #mobile-card="{ row }">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-foreground">
                                {{ row.name }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ row.email }}
                            </p>
                        </div>
                        <StatusBadge
                            :label="row.is_active ? 'Active' : 'Inactive'"
                            :variant="row.is_active ? 'success' : 'danger'"
                        />
                    </div>
                    <div
                        class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-1">
                            <Shield class="size-3.5" aria-hidden="true" />
                            {{ row.role_display_name }}
                        </span>
                        <span v-if="row.phone">{{ row.phone }}</span>
                        <span>
                            Last sign-in:
                            {{ formatRelativeTime(row.last_login_at) }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <Button
                            as-child
                            variant="outline"
                            size="sm"
                            class="min-h-9"
                        >
                            <Link :href="edit(row.id)">
                                <Pencil
                                    class="mr-1.5 size-3.5"
                                    aria-hidden="true"
                                />
                                Edit
                            </Link>
                        </Button>
                        <Button
                            v-if="row.id !== page.props.auth.user?.id"
                            type="button"
                            variant="outline"
                            size="sm"
                            class="min-h-9"
                            @click="toggleCandidate = row"
                        >
                            <Power class="mr-1.5 size-3.5" aria-hidden="true" />
                            {{ row.is_active ? 'Deactivate' : 'Activate' }}
                        </Button>
                    </div>
                </div>
            </template>

            <template #row="{ row }">
                <td class="px-4 py-3.5">
                    <div class="font-medium text-foreground">
                        {{ row.name }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ row.email }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-md bg-secondary px-2.5 py-1 text-xs font-medium text-secondary-foreground"
                    >
                        <UserCheck class="size-3.5" aria-hidden="true" />
                        {{ row.role_display_name }}
                    </span>
                </td>
                <td class="px-4 py-3.5 text-muted-foreground tabular-nums">
                    {{ row.phone || '—' }}
                </td>
                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="row.is_active ? 'Active' : 'Inactive'"
                        :variant="row.is_active ? 'success' : 'danger'"
                    />
                </td>
                <td class="px-4 py-3.5 text-xs text-muted-foreground">
                    {{ formatRelativeTime(row.last_login_at) }}
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
                                <Link :href="edit(row.id)">
                                    <Pencil
                                        class="mr-2 size-4"
                                        aria-hidden="true"
                                    />
                                    Edit Account
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="row.id !== page.props.auth.user?.id"
                                @select="toggleCandidate = row"
                            >
                                <Power class="mr-2 size-4" aria-hidden="true" />
                                {{
                                    row.is_active
                                        ? 'Deactivate Account'
                                        : 'Activate Account'
                                }}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </td>
            </template>
        </DataTableCard>

        <ConfirmDialog
            :open="toggleCandidate !== null"
            :title="
                toggleCandidate?.is_active
                    ? 'Deactivate Staff Account'
                    : 'Activate Staff Account'
            "
            :description="
                toggleCandidate?.is_active
                    ? `Are you sure you want to deactivate ${toggleCandidate?.name}? They will immediately lose access to the system.`
                    : `Restore system access for ${toggleCandidate?.name}?`
            "
            :confirm-label="
                toggleCandidate?.is_active ? 'Deactivate' : 'Activate'
            "
            :destructive="Boolean(toggleCandidate?.is_active)"
            :processing="toggling"
            @update:open="(open) => !open && (toggleCandidate = null)"
            @confirm="confirmToggleStatus"
        />
    </div>
</template>
