<script setup lang="ts" generic="T extends Record<string, unknown>">
import { router } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
    Search,
    X,
} from '@lucide/vue';
import { ref, useId, watch } from 'vue';
import EmptyState from '@/components/anka/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import type { Paginated } from '@/types';

export type DataTableColumn = {
    key: string;
    label: string;
    sortable?: boolean;
    align?: 'left' | 'right' | 'center';
    headerClass?: string;
    cellClass?: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        caption?: string;
        columns: DataTableColumn[];
        rows: Paginated<T>;
        search?: string;
        searchPlaceholder?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        perPage?: number;
        loading?: boolean;
        emptyTitle?: string;
        emptyDescription?: string;
        showSearch?: boolean;
        showPerPage?: boolean;
        rowKey?: string;
    }>(),
    {
        search: '',
        searchPlaceholder: 'Search records...',
        direction: 'asc',
        perPage: 15,
        loading: false,
        emptyTitle: 'No records found',
        emptyDescription:
            'Try adjusting your search or filter criteria, or create a new record.',
        showSearch: true,
        showPerPage: true,
        rowKey: 'id',
    },
);

const emit = defineEmits<{
    'update:search': [value: string];
    sort: [column: string, direction: 'asc' | 'desc'];
    'update:perPage': [perPage: number];
}>();

const tableId = useId();
const captionId = `${tableId}-caption`;
const localSearch = ref(props.search);
const sortAnnouncement = ref('');
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(
    () => props.search,
    (value) => {
        if (value !== localSearch.value) {
            localSearch.value = value;
        }
    },
);

function handleSearchInput(value: string | number): void {
    const str = String(value);
    localSearch.value = str;

    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => {
        emit('update:search', str);
    }, 300);
}

function clearSearch(): void {
    localSearch.value = '';
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    emit('update:search', '');
}

function handleSortClick(column: DataTableColumn): void {
    if (!column.sortable) {
        return;
    }

    const nextDirection: 'asc' | 'desc' =
        props.sort === column.key && props.direction === 'asc' ? 'desc' : 'asc';

    sortAnnouncement.value = `Sorted by ${column.label}, ${nextDirection === 'asc' ? 'ascending' : 'descending'}`;
    emit('sort', column.key, nextDirection);
}

function handlePerPageChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    emit('update:perPage', Number(target.value));
}

function visitPage(url: string | null): void {
    if (!url) {
        return;
    }

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    });
}

function getRowKey(row: T, index: number): string | number {
    const value = row[props.rowKey];
    if (typeof value === 'string' || typeof value === 'number') {
        return value;
    }

    return index;
}

function getAriaSort(
    column: DataTableColumn,
): 'ascending' | 'descending' | undefined {
    if (!column.sortable || props.sort !== column.key) {
        return undefined;
    }

    return props.direction === 'asc' ? 'ascending' : 'descending';
}
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-border/90 bg-card shadow-sm"
    >
        <!-- Toolbar -->
        <div
            v-if="showSearch || $slots.filters || showPerPage || title"
            class="flex flex-col gap-3 border-b border-border/75 bg-gradient-to-r from-muted/45 via-muted/20 to-transparent p-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
        >
            <div
                class="flex flex-1 flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-center"
            >
                <div v-if="showSearch" class="relative w-full sm:max-w-xs">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        :model-value="localSearch"
                        type="search"
                        :placeholder="searchPlaceholder"
                        class="min-h-10 pr-9 pl-9"
                        @update:model-value="handleSearchInput"
                    />
                    <button
                        v-if="localSearch"
                        type="button"
                        class="absolute top-1/2 right-2.5 flex size-6 -translate-y-1/2 items-center justify-center rounded-sm text-muted-foreground hover:text-foreground"
                        aria-label="Clear search"
                        @click="clearSearch"
                    >
                        <X class="size-3.5" aria-hidden="true" />
                    </button>
                </div>

                <slot name="filters" />
            </div>

            <div
                v-if="showPerPage"
                class="flex items-center justify-between gap-2 text-xs text-muted-foreground sm:justify-end"
            >
                <label :for="`${tableId}-per-page`" class="whitespace-nowrap">
                    Rows per page
                </label>
                <select
                    :id="`${tableId}-per-page`"
                    :value="perPage"
                    class="min-h-9 rounded-md border border-input bg-background px-2.5 py-1 text-xs font-medium text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                    @change="handlePerPageChange"
                >
                    <option :value="10">10</option>
                    <option :value="15">15</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                </select>
            </div>
        </div>

        <!-- Screen-reader sort announcement (sortable-table guidance) -->
        <p role="status" class="sr-only">{{ sortAnnouncement }}</p>

        <!-- Loading Skeleton -->
        <div v-if="loading" class="space-y-3 p-4">
            <Skeleton v-for="i in 5" :key="i" class="h-12 w-full rounded-lg" />
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else-if="rows.data.length === 0"
            :title="emptyTitle"
            :description="emptyDescription"
        >
            <template v-if="$slots.emptyAction" #action>
                <slot name="emptyAction" />
            </template>
        </EmptyState>

        <template v-else>
            <!-- Mobile Card Layout (< md) when #mobile-card slot is provided -->
            <div
                v-if="$slots['mobile-card']"
                class="divide-y divide-border/70 md:hidden"
            >
                <div
                    v-for="(row, index) in rows.data"
                    :key="getRowKey(row, index)"
                    class="p-4 transition-colors hover:bg-muted/30"
                >
                    <slot name="mobile-card" :row="row" :index="index" />
                </div>
            </div>

            <!-- Semantic Desktop / Responsive Scrollable Table -->
            <div
                tabindex="0"
                role="region"
                :aria-labelledby="captionId"
                :class="[
                    'overflow-x-auto focus-visible:outline-2 focus-visible:outline-ring',
                    $slots['mobile-card'] ? 'hidden md:block' : 'block',
                ]"
            >
                <table class="w-full min-w-[640px] border-collapse text-sm">
                    <caption :id="captionId" class="sr-only">
                        {{
                            caption || title || 'Data table'
                        }}
                    </caption>
                    <thead class="border-b border-border/70 bg-muted/45">
                        <tr>
                            <th
                                v-for="column in columns"
                                :key="column.key"
                                scope="col"
                                :aria-sort="getAriaSort(column)"
                                :class="[
                                    'text-xs font-semibold tracking-wide text-muted-foreground uppercase',
                                    column.sortable ? 'p-0' : 'px-4 py-3',
                                    column.align === 'right'
                                        ? 'text-right'
                                        : column.align === 'center'
                                          ? 'text-center'
                                          : 'text-left',
                                    column.headerClass,
                                ]"
                            >
                                <button
                                    v-if="column.sortable"
                                    type="button"
                                    :class="[
                                        'inline-flex min-h-10 w-full items-center gap-1.5 px-4 py-3 text-xs font-semibold tracking-wide text-muted-foreground uppercase transition-colors hover:text-foreground focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
                                        column.align === 'right'
                                            ? 'justify-end'
                                            : column.align === 'center'
                                              ? 'justify-center'
                                              : 'justify-start',
                                    ]"
                                    @click="handleSortClick(column)"
                                >
                                    <span>{{ column.label }}</span>
                                    <ArrowUp
                                        v-if="
                                            sort === column.key &&
                                            direction === 'asc'
                                        "
                                        class="size-3.5 text-foreground"
                                        aria-hidden="true"
                                    />
                                    <ArrowDown
                                        v-else-if="
                                            sort === column.key &&
                                            direction === 'desc'
                                        "
                                        class="size-3.5 text-foreground"
                                        aria-hidden="true"
                                    />
                                    <ArrowUpDown
                                        v-else
                                        class="size-3.5 opacity-50"
                                        aria-hidden="true"
                                    />
                                </button>
                                <span v-else>{{ column.label }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr
                            v-for="(row, index) in rows.data"
                            :key="getRowKey(row, index)"
                            class="transition-colors hover:bg-muted/35"
                        >
                            <slot name="row" :row="row" :index="index" />
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div
                class="flex flex-col gap-3 border-t border-border/70 bg-muted/20 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-xs text-muted-foreground tabular-nums">
                    Showing
                    <span class="font-medium text-foreground">{{
                        rows.from ?? 0
                    }}</span>
                    to
                    <span class="font-medium text-foreground">{{
                        rows.to ?? 0
                    }}</span>
                    of
                    <span class="font-medium text-foreground">{{
                        rows.total
                    }}</span>
                    records
                    <span v-if="rows.last_page > 1" class="ml-1">
                        (Page {{ rows.current_page }} of {{ rows.last_page }})
                    </span>
                </p>

                <div
                    v-if="rows.last_page > 1"
                    class="flex items-center justify-between gap-1.5 sm:justify-end"
                >
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="min-h-9"
                        :disabled="!rows.prev_page_url"
                        @click="visitPage(rows.prev_page_url)"
                    >
                        <ChevronLeft class="size-4" aria-hidden="true" />
                        <span>Previous</span>
                    </Button>

                    <div class="hidden items-center gap-1 sm:flex">
                        <template
                            v-for="(link, idx) in rows.links.slice(1, -1)"
                            :key="idx"
                        >
                            <Button
                                type="button"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                class="min-h-9 min-w-9 px-2.5 tabular-nums"
                                :disabled="!link.url"
                                @click="visitPage(link.url)"
                            >
                                {{ link.label }}
                            </Button>
                        </template>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="min-h-9"
                        :disabled="!rows.next_page_url"
                        @click="visitPage(rows.next_page_url)"
                    >
                        <span>Next</span>
                        <ChevronRight class="size-4" aria-hidden="true" />
                    </Button>
                </div>
            </div>
        </template>
    </div>
</template>
