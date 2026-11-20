<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck, ExternalLink } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useFormatting } from '@/composables/useFormatting';
import { index, read, readAll } from '@/routes/notifications';
import type { NotificationItem } from '@/types';

const page = usePage();
const { formatRelativeTime } = useFormatting();

const unreadCount = computed(() => page.props.notifications?.unread_count ?? 0);
const recent = computed<NotificationItem[]>(
    () => page.props.notifications?.recent ?? [],
);

function handleOpenItem(item: NotificationItem): void {
    if (!item.read_at) {
        router.patch(
            read.url(item.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (item.action_url) {
                        router.visit(item.action_url);
                    }
                },
            },
        );
        return;
    }

    if (item.action_url) {
        router.visit(item.action_url);
    }
}

function handleMarkAllRead(): void {
    router.post(readAll.url(), {}, { preserveScroll: true });
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="relative size-10 rounded-full"
                aria-label="Open notifications"
            >
                <Bell class="size-5" aria-hidden="true" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute top-1.5 right-1.5 flex min-w-4 items-center justify-center rounded-full bg-brand-amber px-1 text-[10px] font-bold text-brand-amber-foreground tabular-nums"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align="end"
            class="w-[calc(100vw-2rem)] max-w-sm p-0"
        >
            <div
                class="flex items-center justify-between border-b border-border px-3.5 py-2.5"
            >
                <DropdownMenuLabel class="p-0 text-sm font-semibold">
                    Notifications
                    <span
                        v-if="unreadCount > 0"
                        class="ml-1.5 rounded-full bg-accent px-2 py-0.5 text-xs font-medium text-accent-foreground"
                    >
                        {{ unreadCount }} unread
                    </span>
                </DropdownMenuLabel>
                <button
                    v-if="unreadCount > 0"
                    type="button"
                    class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
                    @click="handleMarkAllRead"
                >
                    <CheckCheck class="size-3.5" aria-hidden="true" />
                    Mark all read
                </button>
            </div>

            <div class="max-h-80 overflow-y-auto">
                <div
                    v-if="recent.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    No notifications yet.
                </div>

                <DropdownMenuItem
                    v-for="item in recent"
                    :key="item.id"
                    class="flex cursor-pointer flex-col items-start gap-1 rounded-none border-b border-border/50 px-3.5 py-3 last:border-b-0"
                    :class="!item.read_at ? 'bg-accent/35' : ''"
                    @select.prevent="handleOpenItem(item)"
                >
                    <div class="flex w-full items-start justify-between gap-2">
                        <span
                            class="text-sm leading-snug font-medium text-foreground"
                        >
                            {{ item.title }}
                        </span>
                        <span
                            v-if="!item.read_at"
                            class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-amber"
                            aria-label="Unread"
                        />
                    </div>
                    <p
                        class="line-clamp-2 text-xs leading-relaxed text-muted-foreground"
                    >
                        {{ item.message }}
                    </p>
                    <span class="text-[11px] text-muted-foreground">
                        {{ formatRelativeTime(item.created_at) }}
                    </span>
                </DropdownMenuItem>
            </div>

            <DropdownMenuSeparator class="m-0" />
            <div class="p-2">
                <Link
                    :href="index()"
                    class="flex w-full items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium text-foreground transition-colors hover:bg-muted"
                >
                    <span>View all notifications</span>
                    <ExternalLink class="size-3.5" aria-hidden="true" />
                </Link>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
