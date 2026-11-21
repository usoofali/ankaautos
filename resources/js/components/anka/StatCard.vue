<script setup lang="ts">
import { type InertiaLinkProps, Link } from '@inertiajs/vue3';
import { ArrowUpRight, type LucideIcon } from '@lucide/vue';
import { computed } from 'vue';

export type StatCardTone =
    | 'primary'
    | 'success'
    | 'warning'
    | 'info'
    | 'danger'
    | 'neutral';

const props = withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        description?: string;
        hint?: string;
        icon?: LucideIcon;
        tone?: StatCardTone;
        accentClass?: string;
        href?: InertiaLinkProps['href'];
    }>(),
    {
        tone: 'primary',
    },
);

const toneMap: Record<
    StatCardTone,
    { bar: string; iconBg: string; hoverBorder: string }
> = {
    primary: {
        bar: 'from-[#0A2E5C] to-[#1a4b8c] dark:from-sky-500 dark:to-blue-600',
        iconBg: 'bg-[#0A2E5C]/10 text-[#0A2E5C] ring-[#0A2E5C]/15 dark:bg-sky-500/15 dark:text-sky-300 dark:ring-sky-400/20',
        hoverBorder: 'hover:border-[#0A2E5C]/45 dark:hover:border-sky-400/45',
    },
    success: {
        bar: 'from-emerald-600 to-teal-500',
        iconBg: 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-400/20',
        hoverBorder: 'hover:border-emerald-500/50',
    },
    warning: {
        bar: 'from-amber-500 to-orange-500',
        iconBg: 'bg-amber-500/10 text-amber-700 ring-amber-500/20 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-400/20',
        hoverBorder: 'hover:border-amber-500/50',
    },
    info: {
        bar: 'from-sky-600 to-indigo-500',
        iconBg: 'bg-sky-500/10 text-sky-700 ring-sky-500/20 dark:bg-sky-500/15 dark:text-sky-300 dark:ring-sky-400/20',
        hoverBorder: 'hover:border-sky-500/50',
    },
    danger: {
        bar: 'from-[#DC141E] to-rose-500',
        iconBg: 'bg-[#DC141E]/10 text-[#DC141E] ring-[#DC141E]/20 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-400/20',
        hoverBorder: 'hover:border-[#DC141E]/50',
    },
    neutral: {
        bar: 'from-slate-500 to-slate-400',
        iconBg: 'bg-muted text-muted-foreground ring-border',
        hoverBorder: 'hover:border-foreground/30',
    },
};

const activeTone = computed(() => toneMap[props.tone] ?? toneMap.primary);
const subtitle = computed(() => props.description ?? props.hint);

const valueSizeClass = computed(() => {
    const length = String(props.value ?? '').length;
    if (length >= 16) {
        return 'text-base sm:text-lg';
    }
    if (length >= 12) {
        return 'text-lg sm:text-xl';
    }
    return 'text-xl sm:text-2xl';
});
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        :class="[
            'group relative flex h-full flex-col justify-between overflow-hidden rounded-xl border border-border/90 bg-card p-4 shadow-sm transition-all duration-200 sm:p-5',
            href
                ? `cursor-pointer hover:-translate-y-0.5 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden ${activeTone.hoverBorder}`
                : '',
        ]"
    >
        <!-- Top Brand/Tone Accent Strip -->
        <span
            :class="[
                'pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r',
                activeTone.bar,
            ]"
            aria-hidden="true"
        />

        <!-- Subtle ambient sheen -->
        <span
            class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/40 via-transparent to-transparent opacity-60 dark:from-white/[0.03]"
            aria-hidden="true"
        />

        <!-- Row 1: Label & Icon -->
        <div class="relative flex items-start justify-between gap-2.5 pt-0.5">
            <p
                class="flex items-center gap-1 text-[11px] leading-snug font-semibold tracking-wider text-muted-foreground uppercase"
            >
                <span>{{ label }}</span>
                <ArrowUpRight
                    v-if="href"
                    class="size-3.5 shrink-0 text-muted-foreground/60 transition-transform duration-200 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-foreground"
                    aria-hidden="true"
                />
            </p>
            <div
                v-if="icon"
                :class="[
                    'flex size-9 shrink-0 items-center justify-center rounded-lg ring-1 transition-transform duration-200',
                    href ? 'group-hover:scale-105' : '',
                    accentClass ?? activeTone.iconBg,
                ]"
            >
                <component :is="icon" class="size-4.5" aria-hidden="true" />
            </div>
        </div>

        <!-- Row 2 & 3: Full-Width Unclipped Value & Subtitle -->
        <div class="relative mt-3 space-y-1">
            <p
                :class="[
                    'leading-tight font-bold tracking-tight break-words text-card-foreground tabular-nums',
                    valueSizeClass,
                ]"
                :title="String(value)"
            >
                {{ value }}
            </p>
            <p
                v-if="subtitle"
                class="text-xs leading-snug text-muted-foreground tabular-nums"
            >
                {{ subtitle }}
            </p>
        </div>
    </component>
</template>
