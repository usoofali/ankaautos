<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        hint?: string;
        icon?: LucideIcon;
        accentClass?: string;
    }>(),
    {
        accentClass:
            'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300',
    },
);
</script>

<template>
    <div
        class="rounded-xl border border-border/80 bg-card p-4 shadow-xs transition-colors sm:p-5"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 space-y-1">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    {{ label }}
                </p>
                <p
                    class="truncate text-xl font-semibold tracking-tight text-card-foreground tabular-nums sm:text-2xl"
                >
                    {{ value }}
                </p>
                <p v-if="hint" class="text-xs text-muted-foreground">
                    {{ hint }}
                </p>
            </div>
            <div
                v-if="icon"
                :class="[
                    'flex size-10 shrink-0 items-center justify-center rounded-lg',
                    accentClass,
                ]"
            >
                <component :is="icon" class="size-5" aria-hidden="true" />
            </div>
        </div>
    </div>
</template>
