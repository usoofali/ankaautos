<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Eye, FileClock, ShieldAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
import DataTableCard, {
    type DataTableColumn,
} from '@/components/anka/DataTableCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatusBadge from '@/components/anka/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useFormatting } from '@/composables/useFormatting';
import { dashboard } from '@/routes';
import { index } from '@/routes/admin/audit-logs';
import type { Paginated } from '@/types';

type AuditLogRow = {
    id: number;
    user_name: string;
    user_email: string | null;
    event?: string;
    action?: string;
    description?: string;
    auditable_type?: string;
    auditable_id?: number;
    old_values?: Record<string, unknown> | null;
    new_values?: Record<string, unknown> | null;
    reason?: string | null;
    ip_address: string | null;
    created_at: string | null;
};

const props = defineProps<{
    activeTab: 'audit' | 'activity';
    logs: Paginated<AuditLogRow>;
    filters: {
        search: string;
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Activity & Audit Logs', href: index() },
        ],
    },
});

const { formatDateTime } = useFormatting();
const inspectedLog = ref<AuditLogRow | null>(null);

const columns = computed<DataTableColumn[]>(() =>
    props.activeTab === 'audit'
        ? [
              { key: 'created_at', label: 'Timestamp' },
              { key: 'user', label: 'User' },
              { key: 'event', label: 'Event' },
              { key: 'record', label: 'Target Record' },
              { key: 'details', label: 'Diff', align: 'right' },
          ]
        : [
              { key: 'created_at', label: 'Timestamp' },
              { key: 'user', label: 'User' },
              { key: 'action', label: 'Action' },
              { key: 'description', label: 'Description' },
              { key: 'ip', label: 'IP Address' },
          ],
);

function switchTab(tab: 'audit' | 'activity'): void {
    router.get(
        index.url(),
        { tab, per_page: props.filters.per_page },
        { preserveState: false },
    );
}

function updateQuery(overrides: Record<string, string | number>): void {
    router.get(
        index.url(),
        {
            tab: props.activeTab,
            search: props.filters.search || undefined,
            per_page: props.filters.per_page,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
function formatLogValues(
    values: Record<string, unknown> | null | undefined,
    emptyText: string,
): string {
    return values ? JSON.stringify(values, null, 2) : emptyText;
}
</script>

<template>
    <Head title="Activity & Audit Logs" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Activity & Audit Logs"
            description="Immutable record of sensitive modifications, before-and-after values, and staff operational activity."
        />

        <div
            role="tablist"
            class="inline-flex w-full gap-1.5 rounded-xl border border-border/80 bg-card p-1.5 shadow-xs sm:w-auto"
        >
            <button
                type="button"
                role="tab"
                :aria-selected="activeTab === 'audit'"
                :class="[
                    'inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors sm:flex-none',
                    activeTab === 'audit'
                        ? 'bg-primary text-primary-foreground shadow-xs'
                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                ]"
                @click="switchTab('audit')"
            >
                <ShieldAlert class="size-4" aria-hidden="true" />
                <span>Audit Trail (Before / After)</span>
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="activeTab === 'activity'"
                :class="[
                    'inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors sm:flex-none',
                    activeTab === 'activity'
                        ? 'bg-primary text-primary-foreground shadow-xs'
                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                ]"
                @click="switchTab('activity')"
            >
                <FileClock class="size-4" aria-hidden="true" />
                <span>Activity History</span>
            </button>
        </div>

        <DataTableCard
            :caption="
                activeTab === 'audit'
                    ? 'Immutable system audit trail'
                    : 'Operational user activity history'
            "
            :columns="columns"
            :rows="logs"
            :search="filters.search"
            search-placeholder="Search user, event, record, or reason..."
            :per-page="filters.per_page"
            empty-title="No log entries recorded yet"
            empty-description="Sensitive system changes and user activities will appear here automatically."
            @update:search="(value) => updateQuery({ search: value, page: 1 })"
            @update:per-page="
                (perPage) => updateQuery({ per_page: perPage, page: 1 })
            "
        >
            <template #mobile-card="{ row }">
                <div class="space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <StatusBadge
                            :label="row.event || row.action || 'Event'"
                            variant="info"
                        />
                        <span
                            class="text-xs text-muted-foreground tabular-nums"
                        >
                            {{ formatDateTime(row.created_at) }}
                        </span>
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        {{ row.user_name }}
                    </p>
                    <p
                        v-if="activeTab === 'activity'"
                        class="text-xs text-muted-foreground"
                    >
                        {{ row.description }}
                    </p>
                    <div
                        v-else
                        class="flex items-center justify-between gap-2 pt-1"
                    >
                        <span class="text-xs text-muted-foreground">
                            {{ row.auditable_type }} #{{ row.auditable_id }}
                        </span>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="min-h-9"
                            @click="inspectedLog = row"
                        >
                            <Eye class="mr-1.5 size-3.5" aria-hidden="true" />
                            Inspect Diff
                        </Button>
                    </div>
                </div>
            </template>

            <template #row="{ row }">
                <td
                    class="px-4 py-3.5 text-xs whitespace-nowrap text-muted-foreground tabular-nums"
                >
                    {{ formatDateTime(row.created_at) }}
                </td>
                <td class="px-4 py-3.5">
                    <div class="font-medium text-foreground">
                        {{ row.user_name }}
                    </div>
                    <div
                        v-if="row.user_email"
                        class="text-xs text-muted-foreground"
                    >
                        {{ row.user_email }}
                    </div>
                </td>
                <template v-if="activeTab === 'audit'">
                    <td class="px-4 py-3.5">
                        <StatusBadge
                            :label="row.event || 'event'"
                            variant="info"
                        />
                    </td>
                    <td class="px-4 py-3.5 text-xs text-muted-foreground">
                        <span class="font-medium text-foreground">{{
                            row.auditable_type
                        }}</span>
                        #{{ row.auditable_id }}
                        <p v-if="row.reason" class="mt-0.5 italic">
                            "{{ row.reason }}"
                        </p>
                    </td>
                    <td class="px-4 py-3.5 text-right">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="min-h-9"
                            @click="inspectedLog = row"
                        >
                            <Eye class="mr-1.5 size-3.5" aria-hidden="true" />
                            Inspect
                        </Button>
                    </td>
                </template>
                <template v-else>
                    <td class="px-4 py-3.5">
                        <StatusBadge
                            :label="row.action || 'activity'"
                            variant="neutral"
                        />
                    </td>
                    <td class="px-4 py-3.5 text-sm text-foreground">
                        {{ row.description }}
                    </td>
                    <td
                        class="px-4 py-3.5 font-mono text-xs text-muted-foreground"
                    >
                        {{ row.ip_address || '—' }}
                    </td>
                </template>
            </template>
        </DataTableCard>

        <Dialog
            :open="inspectedLog !== null"
            @update:open="(open) => !open && (inspectedLog = null)"
        >
            <DialogContent
                class="max-h-[85svh] grid-cols-1 overflow-y-auto overscroll-contain sm:max-w-3xl"
            >
                <DialogHeader class="min-w-0 pr-8">
                    <DialogTitle>Audit Change Details</DialogTitle>
                    <DialogDescription
                        class="[overflow-wrap:anywhere] break-words"
                    >
                        {{ inspectedLog?.event }} on
                        {{ inspectedLog?.auditable_type }} #{{
                            inspectedLog?.auditable_id
                        }}
                        by {{ inspectedLog?.user_name }} ·
                        {{ formatDateTime(inspectedLog?.created_at) }}
                    </DialogDescription>
                </DialogHeader>

                <div v-if="inspectedLog" class="min-w-0 space-y-4 text-xs">
                    <div
                        v-if="inspectedLog.reason"
                        class="rounded-lg border border-amber-200 bg-amber-50 p-3 [overflow-wrap:anywhere] break-words text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200"
                    >
                        <span class="font-semibold">Recorded Reason: </span>
                        {{ inspectedLog.reason }}
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div
                            v-for="panel in [
                                {
                                    label: 'Previous Values',
                                    content: formatLogValues(
                                        inspectedLog.old_values,
                                        'None (New Record)',
                                    ),
                                },
                                {
                                    label: 'Updated Values',
                                    content: formatLogValues(
                                        inspectedLog.new_values,
                                        'None (Deleted)',
                                    ),
                                },
                            ]"
                            :key="panel.label"
                            class="min-w-0 space-y-1.5"
                        >
                            <p class="font-semibold text-foreground">
                                {{ panel.label }}
                            </p>
                            <pre
                                class="max-h-72 max-w-full overflow-auto overscroll-contain rounded-lg border border-border bg-muted/50 p-3 font-mono text-[11px] leading-relaxed [overflow-wrap:anywhere] break-words whitespace-pre-wrap"
                                >{{ panel.content }}</pre>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
