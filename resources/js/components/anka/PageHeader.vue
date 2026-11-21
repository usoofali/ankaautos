<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-3 rounded-xl border border-border/80 bg-card/90 px-4 py-4 shadow-xs backdrop-blur-xs sm:flex-row sm:items-center sm:justify-between sm:px-5"
    >
        <div class="flex min-w-0 items-start gap-3">
            <span
                class="mt-0.5 hidden h-10 w-1 shrink-0 rounded-full bg-gradient-to-b from-[#0A2E5C] via-[#1a4b8c] to-[#DC141E] sm:block dark:from-sky-400 dark:to-[#DC141E]"
                aria-hidden="true"
            />
            <div class="min-w-0 space-y-0.5">
                <h1
                    class="text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                >
                    {{ title }}
                </h1>
                <p v-if="description" class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>
        </div>
        <div
            v-if="$slots.actions"
            class="flex flex-wrap items-center gap-2 sm:shrink-0"
        >
            <slot name="actions" />
        </div>
    </div>
</template>
