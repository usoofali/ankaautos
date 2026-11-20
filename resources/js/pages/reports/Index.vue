<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BarChart3, FileSpreadsheet, FileText } from '@lucide/vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatusBadge from '@/components/anka/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { exportCsv, exportPdf, index, show } from '@/routes/reports';

type ReportCatalogItem = {
    slug: string;
    title: string;
    description: string;
    category: 'operational' | 'financial';
};

defineProps<{
    catalog: ReportCatalogItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Reports & Exports', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Reports & Exports" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Reports & Analytics"
            description="Generate operational and financial management reports with on-screen filtering, CSV spreadsheet exports, and branded PDF downloads."
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="item in catalog"
                :key="item.slug"
                class="flex flex-col justify-between rounded-xl border bg-card p-5 shadow-xs transition hover:border-primary/40"
            >
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <BarChart3 class="size-5" aria-hidden="true" />
                        </div>
                        <StatusBadge
                            :label="
                                item.category === 'financial'
                                    ? 'Financial'
                                    : 'Operational'
                            "
                            :variant="
                                item.category === 'financial'
                                    ? 'info'
                                    : 'neutral'
                            "
                        />
                    </div>
                    <h2 class="mt-4 text-base font-bold text-foreground">
                        {{ item.title }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ item.description }}
                    </p>
                </div>

                <div
                    class="mt-5 flex flex-wrap items-center gap-2 border-t pt-4"
                >
                    <Button as-child size="sm" class="min-h-9 flex-1">
                        <Link :href="show(item.slug)">
                            Open Report
                            <ArrowRight class="ml-1.5 size-3.5" />
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        class="min-h-9"
                        title="Download CSV"
                    >
                        <a :href="exportCsv.url(item.slug)">
                            <FileSpreadsheet class="size-4" />
                        </a>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        class="min-h-9"
                        title="Download PDF"
                    >
                        <a :href="exportPdf.url(item.slug)">
                            <FileText class="size-4" />
                        </a>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
