<script setup lang="ts">
import {
    AlertCircle,
    AlertTriangle,
    CheckCircle2,
    Clock,
    Info,
    MinusCircle,
} from '@lucide/vue';
import { computed } from 'vue';

export type StatusVariant =
    | 'success'
    | 'warning'
    | 'danger'
    | 'info'
    | 'pending'
    | 'neutral';

const props = withDefaults(
    defineProps<{
        label: string;
        variant?: StatusVariant;
    }>(),
    {
        variant: 'neutral',
    },
);

const styles = computed(() => {
    switch (props.variant) {
        case 'success':
            return {
                classes:
                    'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/50 dark:text-emerald-300',
                icon: CheckCircle2,
            };
        case 'warning':
            return {
                classes:
                    'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/50 dark:text-amber-300',
                icon: AlertTriangle,
            };
        case 'danger':
            return {
                classes:
                    'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/50 dark:text-rose-300',
                icon: AlertCircle,
            };
        case 'info':
            return {
                classes:
                    'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-800/60 dark:bg-sky-950/50 dark:text-sky-300',
                icon: Info,
            };
        case 'pending':
            return {
                classes:
                    'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-800/60 dark:bg-violet-950/50 dark:text-violet-300',
                icon: Clock,
            };
        default:
            return {
                classes:
                    'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300',
                icon: MinusCircle,
            };
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
            styles.classes,
        ]"
    >
        <component
            :is="styles.icon"
            class="size-3.5 shrink-0"
            aria-hidden="true"
        />
        <span>{{ label }}</span>
    </span>
</template>
