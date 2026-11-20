<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Bell, Check, CheckCheck, ExternalLink } from '@lucide/vue';
import DataTableCard, {
    type DataTableColumn,
} from '@/components/anka/DataTableCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatusBadge, {
    type StatusVariant,
} from '@/components/anka/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useFormatting } from '@/composables/useFormatting';
import { dashboard } from '@/routes';
import { index, read, readAll } from '@/routes/notifications';
import type { NotificationItem, Paginated } from '@/types';

const props = defineProps<{
    notifications: Paginated<NotificationItem>;
    filters: {
        filter: string;
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Notifications', href: index() },
        ],
    },
});

const { formatDateTime } = useFormatting();

const columns: DataTableColumn[] = [
    { key: 'title', label: 'Notification' },
    { key: 'category', label: 'Category' },
    { key: 'status', label: 'State' },
    { key: 'created_at', label: 'Received' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

function updateQuery(overrides: Record<string, string | number>): void {
    router.get(
        index.url(),
        {
            filter: props.filters.filter,
            per_page: props.filters.per_page,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function markRead(id: string): void {
    router.patch(read.url(id), {}, { preserveScroll: true });
}

function markAll(): void {
    router.post(readAll.url(), {}, { preserveScroll: true });
}

function severityVariant(severity: string): StatusVariant {
    if (severity === 'success') return 'success';
    if (severity === 'warning') return 'warning';
    if (severity === 'error') return 'danger';
    return 'info';
}
</script>

<template>
    <Head title="Notifications" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Notifications"
            description="Track dealership events, sales confirmations, consignment updates, and settlement alerts."
        >
            <template #actions>
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-10 w-full sm:w-auto"
                    @click="markAll"
                >
                    <CheckCheck class="mr-1.5 size-4" aria-hidden="true" />
                    Mark All Read
                </Button>
            </template>
        </PageHeader>

        <DataTableCard
            caption="User notifications history"
            :columns="columns"
            :rows="notifications"
            :show-search="false"
            :per-page="filters.per_page"
            empty-title="You're all caught up"
            empty-description="No notifications match this filter."
            @update:per-page="
                (perPage) => updateQuery({ per_page: perPage, page: 1 })
            "
        >
            <template #filters>
                <div class="inline-flex gap-1 rounded-lg bg-muted p-1">
                    <button
                        v-for="tab in [
                            { key: 'all', label: 'All' },
                            { key: 'unread', label: 'Unread' },
                            { key: 'read', label: 'Read' },
                        ]"
                        :key="tab.key"
                        type="button"
                        :class="[
                            'min-h-8 rounded-md px-3 text-xs font-medium transition-colors',
                            filters.filter === tab.key
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                        @click="updateQuery({ filter: tab.key, page: 1 })"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </template>

            <template #mobile-card="{ row }">
                <div
                    class="space-y-2.5"
                    :class="
                        !row.read_at ? 'border-l-2 border-brand-amber pl-3' : ''
                    "
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-semibold text-foreground">
                            {{ row.title }}
                        </p>
                        <StatusBadge
                            :label="row.read_at ? 'Read' : 'Unread'"
                            :variant="row.read_at ? 'neutral' : 'warning'"
                        />
                    </div>
                    <p class="text-xs leading-relaxed text-muted-foreground">
                        {{ row.message }}
                    </p>
                    <div
                        class="flex flex-wrap items-center justify-between gap-2 pt-1"
                    >
                        <span
                            class="text-[11px] text-muted-foreground tabular-nums"
                        >
                            {{ formatDateTime(row.created_at) }}
                        </span>
                        <div class="flex gap-2">
                            <Button
                                v-if="!row.read_at"
                                type="button"
                                variant="outline"
                                size="sm"
                                class="min-h-8"
                                @click="markRead(row.id)"
                            >
                                <Check
                                    class="mr-1 size-3.5"
                                    aria-hidden="true"
                                />
                                Mark Read
                            </Button>
                            <Button
                                v-if="row.action_url"
                                type="button"
                                size="sm"
                                class="min-h-8"
                                @click="router.visit(row.action_url)"
                            >
                                Open
                                <ExternalLink
                                    class="ml-1 size-3.5"
                                    aria-hidden="true"
                                />
                            </Button>
                        </div>
                    </div>
                </div>
            </template>

            <template #row="{ row }">
                <td class="px-4 py-3.5">
                    <div class="flex items-start gap-2.5">
                        <Bell
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <div class="min-w-0">
                            <p class="font-medium text-foreground">
                                {{ row.title }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ row.message }}
                            </p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="row.category"
                        :variant="severityVariant(row.severity)"
                    />
                </td>
                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="row.read_at ? 'Read' : 'Unread'"
                        :variant="row.read_at ? 'neutral' : 'warning'"
                    />
                </td>
                <td
                    class="px-4 py-3.5 text-xs whitespace-nowrap text-muted-foreground tabular-nums"
                >
                    {{ formatDateTime(row.created_at) }}
                </td>
                <td class="px-4 py-3.5 text-right">
                    <div class="inline-flex items-center gap-1.5">
                        <Button
                            v-if="!row.read_at"
                            type="button"
                            variant="outline"
                            size="sm"
                            class="min-h-8"
                            @click="markRead(row.id)"
                        >
                            <Check class="mr-1 size-3.5" aria-hidden="true" />
                            Read
                        </Button>
                        <Button
                            v-if="row.action_url"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="min-h-8"
                            @click="router.visit(row.action_url)"
                        >
                            <ExternalLink class="size-4" aria-hidden="true" />
                        </Button>
                    </div>
                </td>
            </template>
        </DataTableCard>
    </div>
</template>
