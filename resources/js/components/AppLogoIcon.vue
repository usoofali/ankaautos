<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

defineProps<{
    className?: HTMLAttributes['class'];
}>();
</script>

<template>
    <img
        src="/logo-mark-transparent.png"
        alt="Anka Auto Sales"
        :class="['object-contain', className]"
        v-bind="$attrs"
    />
</template>
