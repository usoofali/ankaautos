<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Toaster } from '@/components/ui/sonner';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
const companyName = computed(
    () => page.props.business?.company_name || 'Anka Auto Sales',
);
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center bg-gradient-to-b from-background via-background to-muted/50 px-4 py-8 sm:px-6 md:p-10"
    >
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#0A2E5C] via-[#DC141E] to-[#0A2E5C]"
        />
        <div class="w-full max-w-md">
            <div class="mb-6 flex flex-col items-center gap-2 text-center">
                <Link
                    :href="home()"
                    class="group inline-flex items-center justify-center rounded-2xl bg-white/95 px-5 py-3 shadow-xs ring-1 ring-border/70 transition-transform hover:scale-[1.01] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                >
                    <picture>
                        <source
                            srcset="/logo-transparent.webp"
                            type="image/webp"
                        />
                        <img
                            src="/logo-transparent.png"
                            :alt="companyName"
                            class="h-14 w-auto object-contain sm:h-16"
                        />
                    </picture>
                </Link>
            </div>

            <div
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm sm:p-8"
            >
                <div class="mb-6 space-y-1.5 text-center">
                    <h1
                        class="text-xl font-semibold tracking-tight text-card-foreground"
                    >
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>

            <p class="mt-6 text-center text-xs text-muted-foreground">
                &copy; {{ new Date().getFullYear() }} {{ companyName }}.
                Authorized personnel access.
            </p>
        </div>
        <Toaster />
    </div>
</template>
