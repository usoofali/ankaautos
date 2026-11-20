<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { Inbox } from '@lucide/vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: LucideIcon;
    }>(),
    {
        icon: Inbox,
    },
);
</script>

<template>
    <div
        class="flex flex-col items-center justify-center px-4 py-12 text-center sm:px-6"
    >
        <div
            class="mb-3 flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
        >
            <component :is="icon" class="size-6" aria-hidden="true" />
        </div>
        <h3 class="text-sm font-semibold text-foreground sm:text-base">
            {{ title }}
        </h3>
        <p
            v-if="description"
            class="mt-1 max-w-md text-xs text-muted-foreground sm:text-sm"
        >
            {{ description }}
        </p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
