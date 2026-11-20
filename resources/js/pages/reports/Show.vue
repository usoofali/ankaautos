<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, FileSpreadsheet, FileText, Filter } from '@lucide/vue';
import { ref } from 'vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { exportCsv, exportPdf, index, show } from '@/routes/reports';

const props = defineProps<{
    report: {
        slug: string;
        title: string;
        description: string;
        category: 'operational' | 'financial';
        headers: string[];
        rows: string[][];
        summary: Array<{ label: string; value: string }>;
    };
    filters: {
        from: string;
        to: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Reports & Exports', href: index() },
            { title: 'View Report', href: index() },
        ],
    },
});

const fromDate = ref(props.filters.from);
const toDate = ref(props.filters.to);

function applyDateFilter(): void {
    router.get(
        show.url(props.report.slug),
        {
            from: fromDate.value || undefined,
            to: toDate.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilter(): void {
    fromDate.value = '';
    toDate.value = '';
    applyDateFilter();
}

function queryParams(): Record<string, string> {
    const q: Record<string, string> = {};
    if (fromDate.value) {
        q.from = fromDate.value;
    }
    if (toDate.value) {
        q.to = toDate.value;
    }
    return q;
}
</script>

<template>
    <Head :title="report.title" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader :title="report.title" :description="report.description">
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" />
                        All Reports
                    </Link>
                </Button>
                <Button as-child variant="outline" class="min-h-10">
                    <a
                        :href="
                            exportCsv.url(report.slug, {
                                query: queryParams(),
                            })
                        "
                    >
                        <FileSpreadsheet class="mr-1.5 size-4" />
                        Export CSV
                    </a>
                </Button>
                <Button as-child class="min-h-10">
                    <a
                        :href="
                            exportPdf.url(report.slug, {
                                query: queryParams(),
                            })
                        "
                    >
                        <FileText class="mr-1.5 size-4" />
                        Export PDF
                    </a>
                </Button>
            </template>
        </PageHeader>

        <!-- Date Range Filter -->
        <form
            class="flex flex-wrap items-end gap-3 rounded-xl border bg-card p-4"
            @submit.prevent="applyDateFilter"
        >
            <div class="grid gap-1">
                <Label for="from_date" class="text-xs">From Date</Label>
                <Input
                    id="from_date"
                    v-model="fromDate"
                    type="date"
                    class="min-h-10 w-44"
                />
            </div>
            <div class="grid gap-1">
                <Label for="to_date" class="text-xs">To Date</Label>
                <Input
                    id="to_date"
                    v-model="toDate"
                    type="date"
                    class="min-h-10 w-44"
                />
            </div>
            <Button type="submit" variant="secondary" class="min-h-10">
                <Filter class="mr-1.5 size-4" />
                Apply Period
            </Button>
            <Button
                v-if="fromDate || toDate"
                type="button"
                variant="ghost"
                class="min-h-10"
                @click="clearFilter"
            >
                Clear
            </Button>
        </form>

        <!-- Summary Strip -->
        <div
            v-if="report.summary.length > 0"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <div
                v-for="item in report.summary"
                :key="item.label"
                class="rounded-xl border bg-card p-4 shadow-xs"
            >
                <p class="text-xs font-medium text-muted-foreground">
                    {{ item.label }}
                </p>
                <p class="mt-1 text-xl font-bold text-foreground tabular-nums">
                    {{ item.value }}
                </p>
            </div>
        </div>

        <!-- Report Table Card -->
        <div class="overflow-hidden rounded-xl border bg-card shadow-xs">
            <div
                role="region"
                tabindex="0"
                :aria-label="report.title"
                class="overflow-x-auto"
            >
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b bg-muted/40 text-left text-xs font-semibold text-muted-foreground uppercase"
                        >
                            <th
                                v-for="header in report.headers"
                                :key="header"
                                scope="col"
                                class="px-4 py-3 whitespace-nowrap"
                            >
                                {{ header }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="report.rows.length === 0">
                            <td
                                :colspan="report.headers.length"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No records matched the selected period.
                            </td>
                        </tr>
                        <tr
                            v-for="(row, rIdx) in report.rows"
                            :key="rIdx"
                            class="hover:bg-muted/20"
                        >
                            <td
                                v-for="(cell, cIdx) in row"
                                :key="cIdx"
                                class="px-4 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{ cell }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
