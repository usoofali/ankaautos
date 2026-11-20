<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const companyName = computed(
    () => page.props.business?.company_name || 'Anka Auto Sales',
);
</script>

<template>
    <div
        class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-lg bg-white p-1 shadow-xs ring-1 ring-white/20"
    >
        <img
            src="/logo-mark-transparent.png"
            :alt="companyName"
            class="h-full w-full object-contain"
        />
    </div>
    <div class="ml-1.5 grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-semibold tracking-tight">{{
            companyName
        }}</span>
        <span class="truncate text-[11px] text-sidebar-foreground/70"
            >Quality Vehicles. Trusted Deals.</span
        >
    </div>
</template>
