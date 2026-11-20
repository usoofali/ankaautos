<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <section
        class="overflow-hidden rounded-xl border border-border/80 bg-card shadow-xs"
    >
        <div
            class="flex flex-col gap-1 border-b border-border/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
        >
            <div class="space-y-0.5">
                <h2 class="text-base font-semibold text-card-foreground">
                    {{ title }}
                </h2>
                <p v-if="description" class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>
            <div v-if="$slots.headerAction" class="shrink-0">
                <slot name="headerAction" />
            </div>
        </div>
        <div class="p-4 sm:p-6">
            <slot />
        </div>
        <div
            v-if="$slots.footer"
            class="flex flex-wrap items-center justify-end gap-2 border-t border-border/70 bg-muted/30 px-4 py-3 sm:px-6"
        >
            <slot name="footer" />
        </div>
    </section>
</template>
