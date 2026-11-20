<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const companyName = computed(
    () => page.props.business?.company_name || 'Anka Auto Sales',
);
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-brand-amber text-brand-amber-foreground shadow-xs"
    >
        <AppLogoIcon class="size-5" />
    </div>
    <div class="ml-1.5 grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-semibold tracking-tight">{{
            companyName
        }}</span>
        <span class="truncate text-[11px] text-sidebar-foreground/65"
            >Dealership Suite</span
        >
    </div>
</template>
