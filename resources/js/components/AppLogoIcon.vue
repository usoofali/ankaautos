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
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
        aria-hidden="true"
    >
        <rect
            width="40"
            height="40"
            rx="10"
            fill="currentColor"
            opacity="0.12"
        />
        <path
            d="M20 7L32 31H26.4L23.9 25.6H16.1L13.6 31H8L20 7ZM20 15.2L17.9 20.6H22.1L20 15.2Z"
            fill="currentColor"
        />
        <rect
            x="13"
            y="33"
            width="14"
            height="2.4"
            rx="1.2"
            fill="currentColor"
            opacity="0.7"
        />
    </svg>
</template>
