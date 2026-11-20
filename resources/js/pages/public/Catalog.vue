<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Car,
    Fuel,
    Gauge,
    MapPin,
    MessageCircle,
    Phone,
    Search,
    Settings2,
    ShieldCheck,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import StatusBadge, {
    type StatusVariant,
} from '@/components/anka/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFormatting } from '@/composables/useFormatting';
import { dashboard, home, login } from '@/routes';
import { show as showListing } from '@/routes/public/listings';
import type { Paginated } from '@/types';

type PublicVehicleCard = {
    id: number;
    slug: string;
    title: string;
    year: number;
    brand: string;
    category: string;
    condition: string;
    transmission: string;
    fuel_type: string;
    mileage: number | null;
    exterior_colour: string;
    advertised_price: string | null;
    status: string;
    status_label: string;
    status_variant: StatusVariant;
    photo_url: string | null;
};

const props = defineProps<{
    vehicles: Paginated<PublicVehicleCard>;
    brands: Array<{ id: number; name: string }>;
    categories: Array<{ id: number; name: string }>;
    business: {
        name: string;
        tagline: string;
        phone: string;
        whatsapp: string;
        email: string;
        address: string;
    };
    filters: {
        search: string;
        brand_id: number | null;
        category_id: number | null;
        max_price: number | null;
    };
}>();

const page = usePage();
const isLoggedIn = computed(() => Boolean(page.props.auth?.user));
const { formatMoney } = useFormatting();

const search = ref(props.filters.search);
const brandId = ref<string | number>(props.filters.brand_id ?? '');
const categoryId = ref<string | number>(props.filters.category_id ?? '');
const maxPrice = ref<string | number>(props.filters.max_price ?? '');

function applyFilters(): void {
    router.get(
        home.url(),
        {
            search: search.value || undefined,
            brand_id: brandId.value || undefined,
            category_id: categoryId.value || undefined,
            max_price: maxPrice.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function resetFilters(): void {
    search.value = '';
    brandId.value = '';
    categoryId.value = '';
    maxPrice.value = '';
    applyFilters();
}

function whatsappHref(message: string): string {
    const clean = props.business.whatsapp.replace(/[^\d]/g, '');
    return `https://wa.me/${clean}?text=${encodeURIComponent(message)}`;
}
</script>

<template>
    <Head :title="`${business.name} — ${business.tagline}`" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Branded Top Bar -->
        <header
            class="sticky top-0 z-30 border-b border-border/70 bg-background/95 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6"
            >
                <Link :href="home()" class="flex items-center gap-3">
                    <img
                        src="/logo-transparent.webp"
                        :alt="business.name"
                        class="h-10 w-auto object-contain sm:h-11"
                    />
                </Link>

                <div class="flex items-center gap-2 sm:gap-3">
                    <a
                        v-if="business.whatsapp"
                        :href="
                            whatsappHref(
                                `Hello ${business.name}, I would like to inquire about available vehicles.`,
                            )
                        "
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hidden min-h-10 items-center gap-1.5 rounded-md bg-emerald-600 px-3.5 text-sm font-medium text-white transition hover:bg-emerald-700 sm:inline-flex"
                    >
                        <MessageCircle class="size-4" aria-hidden="true" />
                        WhatsApp Sales
                    </a>
                    <Button
                        v-if="isLoggedIn"
                        as-child
                        variant="default"
                        class="min-h-10"
                    >
                        <Link :href="dashboard()">Staff Portal</Link>
                    </Button>
                    <Button v-else as-child variant="outline" class="min-h-10">
                        <Link :href="login()">Staff Login</Link>
                    </Button>
                </div>
            </div>
        </header>

        <!-- Hero Section (Anka Deep Navy + Crimson Accent) -->
        <section
            class="relative overflow-hidden bg-[#0A2E5C] text-white"
        >
            <div
                class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:py-20"
            >
                <div class="max-w-3xl">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-[#DC141E]/20 px-3 py-1 text-xs font-semibold tracking-wide text-rose-200 ring-1 ring-[#DC141E]/40"
                    >
                        <ShieldCheck class="size-3.5" aria-hidden="true" />
                        {{ business.tagline }}
                    </span>
                    <h1
                        class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl"
                    >
                        Verified Vehicles. Transparent Pricing.
                    </h1>
                    <p
                        class="mt-3 max-w-2xl text-sm text-slate-200 sm:text-base"
                    >
                        Explore inspected company-owned and verified consignment vehicles currently on our stand. Live availability updated directly from our inventory.
                    </p>
                </div>

                <!-- Search & Filter Bar -->
                <form
                    class="mt-8 grid gap-3 rounded-2xl bg-white p-3 text-slate-900 shadow-xl sm:grid-cols-2 lg:grid-cols-5 dark:bg-slate-900 dark:text-slate-100"
                    @submit.prevent="applyFilters"
                >
                    <div class="relative lg:col-span-2">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            placeholder="Search make, model, colour..."
                            class="min-h-11 pl-9"
                        />
                    </div>
                    <select
                        v-model="brandId"
                        aria-label="Filter by brand"
                        class="min-h-11 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">All Brands</option>
                        <option
                            v-for="b in brands"
                            :key="b.id"
                            :value="b.id"
                        >
                            {{ b.name }}
                        </option>
                    </select>
                    <select
                        v-model="categoryId"
                        aria-label="Filter by category"
                        class="min-h-11 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">All Categories</option>
                        <option
                            v-for="c in categories"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select>
                    <div class="flex gap-2">
                        <Button
                            type="submit"
                            class="min-h-11 flex-1 bg-[#DC141E] text-white hover:bg-[#b81018]"
                        >
                            Search
                        </Button>
                        <Button
                            v-if="search || brandId || categoryId || maxPrice"
                            type="button"
                            variant="outline"
                            class="min-h-11"
                            @click="resetFilters"
                        >
                            Clear
                        </Button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Vehicle Catalog Grid -->
        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-10 sm:px-6">
            <div class="mb-6 flex items-center justify-between gap-2">
                <div>
                    <h2 class="text-xl font-bold tracking-tight sm:text-2xl">
                        Available Inventory
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Showing {{ vehicles.total }} listed vehicle{{
                            vehicles.total === 1 ? '' : 's'
                        }}
                    </p>
                </div>
            </div>

            <div
                v-if="vehicles.data.length === 0"
                class="rounded-2xl border border-dashed bg-card p-12 text-center"
            >
                <Car
                    class="mx-auto size-12 text-muted-foreground/60"
                    aria-hidden="true"
                />
                <h3 class="mt-4 text-lg font-semibold">
                    No matching vehicles found
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Try adjusting your filters or reach out to our sales team for incoming stock.
                </p>
                <Button
                    v-if="search || brandId || categoryId || maxPrice"
                    variant="outline"
                    class="mt-4 min-h-10"
                    @click="resetFilters"
                >
                    Show All Vehicles
                </Button>
            </div>

            <div
                v-else
                class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
            >
                <article
                    v-for="v in vehicles.data"
                    :key="v.id"
                    class="group flex flex-col overflow-hidden rounded-2xl border bg-card shadow-xs transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <Link
                        :href="showListing(v.slug)"
                        class="relative block aspect-[16/10] overflow-hidden bg-muted"
                    >
                        <img
                            v-if="v.photo_url"
                            :src="v.photo_url"
                            :alt="v.title"
                            loading="lazy"
                            class="size-full object-cover transition duration-300 group-hover:scale-105"
                        />
                        <div
                            v-else
                            class="flex size-full items-center justify-center bg-[#0A2E5C]/10"
                        >
                            <Car class="size-12 text-[#0A2E5C]/40" />
                        </div>
                        <div class="absolute top-3 left-3">
                            <StatusBadge
                                :label="v.status_label"
                                :variant="v.status_variant"
                            />
                        </div>
                    </Link>

                    <div class="flex flex-1 flex-col p-4">
                        <div class="flex items-start justify-between gap-2">
                            <Link
                                :href="showListing(v.slug)"
                                class="text-base font-bold text-foreground hover:text-primary"
                            >
                                {{ v.title }}
                            </Link>
                        </div>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ v.condition }} · {{ v.category }} ·
                            {{ v.exterior_colour }}
                        </p>

                        <p
                            class="mt-3 text-xl font-extrabold text-[#0A2E5C] tabular-nums dark:text-sky-300"
                        >
                            {{
                                v.advertised_price
                                    ? formatMoney(v.advertised_price)
                                    : 'Call for Price'
                            }}
                        </p>

                        <div
                            class="mt-4 grid grid-cols-3 gap-2 border-t pt-3 text-xs text-muted-foreground"
                        >
                            <div class="flex items-center gap-1">
                                <Settings2 class="size-3.5 shrink-0" />
                                <span class="truncate">{{
                                    v.transmission
                                }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <Fuel class="size-3.5 shrink-0" />
                                <span class="truncate">{{ v.fuel_type }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <Gauge class="size-3.5 shrink-0" />
                                <span class="truncate">
                                    {{
                                        v.mileage !== null
                                            ? `${v.mileage.toLocaleString()} km`
                                            : '—'
                                    }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <Button as-child class="min-h-10 flex-1">
                                <Link :href="showListing(v.slug)">
                                    View Details
                                </Link>
                            </Button>
                            <Button
                                v-if="business.whatsapp"
                                as-child
                                variant="outline"
                                class="min-h-10"
                            >
                                <a
                                    :href="
                                        whatsappHref(
                                            `Hello ${business.name}, I am interested in the ${v.title}. Is it still available?`,
                                        )
                                    "
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <MessageCircle class="size-4" />
                                </a>
                            </Button>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Simple Pagination -->
            <div
                v-if="vehicles.last_page > 1"
                class="mt-8 flex items-center justify-center gap-2"
            >
                <Button
                    v-if="vehicles.prev_page_url"
                    as-child
                    variant="outline"
                    class="min-h-10"
                >
                    <Link :href="vehicles.prev_page_url">Previous</Link>
                </Button>
                <span class="px-3 text-sm text-muted-foreground">
                    Page {{ vehicles.current_page }} of {{ vehicles.last_page }}
                </span>
                <Button
                    v-if="vehicles.next_page_url"
                    as-child
                    variant="outline"
                    class="min-h-10"
                >
                    <Link :href="vehicles.next_page_url">Next</Link>
                </Button>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t bg-[#0A2E5C] text-slate-200">
            <div
                class="mx-auto flex max-w-7xl flex-col justify-between gap-4 px-4 py-8 text-sm sm:flex-row sm:items-center sm:px-6"
            >
                <div>
                    <p class="font-bold text-white">{{ business.name }}</p>
                    <p class="mt-1 text-xs text-slate-300">
                        {{ business.tagline }}
                    </p>
                    <p
                        v-if="business.address"
                        class="mt-1.5 flex items-center gap-1.5 text-xs text-slate-300"
                    >
                        <MapPin class="size-3.5 shrink-0" />
                        {{ business.address }}
                    </p>
                </div>
                <div class="flex flex-col gap-1 text-xs sm:text-right">
                    <p
                        v-if="business.phone"
                        class="flex items-center gap-1.5 sm:justify-end"
                    >
                        <Phone class="size-3.5" />
                        {{ business.phone }}
                    </p>
                    <p v-if="business.email">{{ business.email }}</p>
                </div>
            </div>
        </footer>
    </div>
</template>
