<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import NotificationDropdown from '@/components/anka/NotificationDropdown.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { resolvedAppearance, updateAppearance } = useAppearance();

function toggleTheme(): void {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-14 shrink-0 items-center justify-between gap-2 border-b border-border/80 bg-background/90 px-4 shadow-[0_1px_2px_0_rgb(10_46_92/0.04)] backdrop-blur-md transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 sm:h-16 sm:px-6"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1 size-9 shrink-0" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex shrink-0 items-center gap-1.5">
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-9 rounded-lg text-muted-foreground transition hover:bg-muted hover:text-foreground"
                :aria-label="
                    resolvedAppearance === 'dark'
                        ? 'Switch to light theme'
                        : 'Switch to dark theme'
                "
                :title="
                    resolvedAppearance === 'dark'
                        ? 'Switch to light mode'
                        : 'Switch to dark mode'
                "
                @click="toggleTheme"
            >
                <Sun
                    v-if="resolvedAppearance === 'dark'"
                    class="size-4.5 text-amber-400"
                    aria-hidden="true"
                />
                <Moon v-else class="size-4.5 text-primary" aria-hidden="true" />
            </Button>
            <NotificationDropdown />
        </div>
    </header>
</template>
