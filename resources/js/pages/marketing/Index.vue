<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Car,
    ExternalLink,
    Globe,
    Megaphone,
    Share2,
    Sparkles,
} from '@lucide/vue';
import DataTableCard, {
    type DataTableColumn,
} from '@/components/anka/DataTableCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatCard from '@/components/anka/StatCard.vue';
import StatusBadge, {
    type StatusVariant,
} from '@/components/anka/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useFormatting } from '@/composables/useFormatting';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { builder, index } from '@/routes/marketing';
import { show as showListing } from '@/routes/public/listings';
import { create as createVehicle } from '@/routes/vehicles';
import type { Paginated } from '@/types';

type MarketingVehicleRow = {
    id: number;
    reference_number: string;
    slug: string;
    title: string;
    advertised_price: string | null;
    status_label: string;
    status_variant: StatusVariant;
    is_publicly_listed: boolean;
    photos_count: number;
    photo_url: string | null;
    has_ad: boolean;
    shares_count: number;
};

const props = defineProps<{
    vehicles: Paginated<MarketingVehicleRow>;
    summary: {
        public_count: number;
        ads_count: number;
        shares_count: number;
    };
    filters: {
        search: string;
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Marketing & Ads', href: index() },
        ],
    },
});

const { can } = usePermissions();
const { formatMoney } = useFormatting();

const columns: DataTableColumn[] = [
    { key: 'title', label: 'Vehicle' },
    { key: 'advertised_price', label: 'Advertised Price', align: 'right' },
    { key: 'status', label: 'Inventory Status' },
    { key: 'visibility', label: 'Public Storefront' },
    { key: 'shares_count', label: 'Shares', align: 'right' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

function updateQuery(
    overrides: Record<string, string | number | undefined>,
): void {
    router.get(
        index.url(),
        {
            search: props.filters.search || undefined,
            per_page: props.filters.per_page,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Marketing & Social Sharing" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Marketing & Social Sharing"
            description="Craft vehicle adverts, toggle public storefront listings, share to WhatsApp & Facebook, and export Instagram photo packs."
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <StatCard
                label="Publicly Listed Available"
                :value="String(summary.public_count)"
                :icon="Globe"
                tone="success"
            />
            <StatCard
                label="Saved Ad Campaigns"
                :value="String(summary.ads_count)"
                :icon="Megaphone"
                tone="primary"
            />
            <StatCard
                label="Logged Social Shares"
                :value="String(summary.shares_count)"
                :icon="Share2"
                tone="info"
            />
        </div>

        <DataTableCard
            caption="Vehicle marketing and advertisement records"
            :columns="columns"
            :rows="vehicles"
            :search="filters.search"
            search-placeholder="Search vehicle reference, make, or model..."
            :per-page="filters.per_page"
            empty-title="No vehicles available to promote"
            empty-description="Register vehicles in inventory first to create adverts and share links."
            @update:search="
                (v) => updateQuery({ search: v || undefined, page: 1 })
            "
            @update:per-page="(p) => updateQuery({ per_page: p, page: 1 })"
        >
            <template v-if="can('vehicles.create')" #emptyAction>
                <Button as-child class="min-h-10">
                    <Link :href="createVehicle()">
                        <Car class="mr-1.5 size-4" aria-hidden="true" />
                        Register Vehicle
                    </Link>
                </Button>
            </template>

            <template #mobile-card="{ row }">
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted"
                        >
                            <img
                                v-if="row.photo_url"
                                :src="row.photo_url"
                                :alt="row.title"
                                class="size-full object-cover"
                            />
                            <Car v-else class="size-6 text-muted-foreground" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ row.title }}</p>
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ row.reference_number }} ·
                                {{ row.photos_count }} photos
                            </p>
                            <p class="mt-1 text-sm font-bold tabular-nums">
                                {{
                                    row.advertised_price
                                        ? formatMoney(row.advertised_price)
                                        : '—'
                                }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <StatusBadge
                            :label="
                                row.is_publicly_listed
                                    ? 'Listed Publicly'
                                    : 'Hidden'
                            "
                            :variant="
                                row.is_publicly_listed ? 'success' : 'neutral'
                            "
                        />
                        <Button as-child size="sm" class="min-h-9">
                            <Link :href="builder(row.id)">
                                <Sparkles class="mr-1.5 size-3.5" />
                                Ad Builder
                            </Link>
                        </Button>
                    </div>
                </div>
            </template>

            <template #row="{ row }">
                <td class="px-4 py-3.5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted"
                        >
                            <img
                                v-if="row.photo_url"
                                :src="row.photo_url"
                                :alt="row.title"
                                class="size-full object-cover"
                            />
                            <Car v-else class="size-5 text-muted-foreground" />
                        </div>
                        <div>
                            <div class="font-medium">{{ row.title }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ row.reference_number }} ·
                                {{ row.photos_count }} photos
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3.5 text-right font-medium tabular-nums">
                    {{
                        row.advertised_price
                            ? formatMoney(row.advertised_price)
                            : '—'
                    }}
                </td>
                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="row.status_label"
                        :variant="row.status_variant"
                    />
                </td>
                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="
                            row.is_publicly_listed
                                ? 'Public Catalog'
                                : 'Private'
                        "
                        :variant="
                            row.is_publicly_listed ? 'success' : 'neutral'
                        "
                    />
                </td>
                <td class="px-4 py-3.5 text-right tabular-nums">
                    {{ row.shares_count }}
                </td>
                <td class="px-4 py-3.5 text-right">
                    <div class="flex justify-end gap-2">
                        <Button
                            v-if="row.is_publicly_listed"
                            as-child
                            variant="ghost"
                            size="sm"
                            class="min-h-9"
                        >
                            <a
                                :href="showListing.url(row.slug)"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <ExternalLink class="size-4" />
                            </a>
                        </Button>
                        <Button
                            as-child
                            variant="outline"
                            size="sm"
                            class="min-h-9"
                        >
                            <Link :href="builder(row.id)">
                                <Sparkles class="mr-1.5 size-3.5" />
                                Ad Builder
                            </Link>
                        </Button>
                    </div>
                </td>
            </template>
        </DataTableCard>
    </div>
</template>
