<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="min-w-0 space-y-1">
            <h1
                class="text-xl font-semibold tracking-tight text-foreground sm:text-2xl"
            >
                {{ title }}
            </h1>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div
            v-if="$slots.actions"
            class="flex flex-wrap items-center gap-2 sm:shrink-0"
        >
            <slot name="actions" />
        </div>
    </div>
</template>
