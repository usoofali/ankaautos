<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Car,
    Check,
    Copy,
    MessageCircle,
    Phone,
    Share2,
} from '@lucide/vue';
import { ref } from 'vue';
import StatusBadge, {
    type StatusVariant,
} from '@/components/anka/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useFormatting } from '@/composables/useFormatting';
import { home } from '@/routes';

const props = defineProps<{
    vehicle: {
        slug: string;
        reference_number: string;
        title: string;
        year: number;
        brand: string;
        model: string;
        trim: string | null;
        category: string;
        condition: string;
        transmission: string;
        fuel_type: string;
        drive_type: string | null;
        engine_size: string | null;
        mileage: number | null;
        exterior_colour: string;
        interior_colour: string | null;
        advertised_price: string | null;
        public_description: string | null;
        status: string;
        status_label: string;
        status_variant: StatusVariant;
        is_available: boolean;
        photos: Array<{
            id: number;
            url: string;
            caption: string | null;
            is_primary: boolean;
        }>;
    };
    listingUrl: string;
    business: {
        name: string;
        tagline: string;
        phone: string;
        whatsapp: string;
        email: string;
        address: string;
    };
}>();

const { formatMoney } = useFormatting();
const activeIndex = ref(0);
const copied = ref(false);

function whatsappInquiryUrl(): string {
    const clean = props.business.whatsapp.replace(/[^\d]/g, '');
    const msg = `Hello ${props.business.name}, I am interested in the ${props.vehicle.title} (${props.vehicle.reference_number}). Link: ${props.listingUrl}`;
    return `https://wa.me/${clean}?text=${encodeURIComponent(msg)}`;
}

function facebookShareUrl(): string {
    return `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(props.listingUrl)}`;
}

async function copyLink(): Promise<void> {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        await navigator.clipboard.writeText(props.listingUrl);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    }
}
</script>

<template>
    <Head :title="`${vehicle.title} — ${business.name}`" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b bg-background">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6"
            >
                <Link :href="home()" class="flex items-center gap-2">
                    <img
                        src="/logo-transparent.webp"
                        :alt="business.name"
                        class="h-10 w-auto object-contain"
                    />
                </Link>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="home()">
                        <ArrowLeft class="mr-1.5 size-4" />
                        All Vehicles
                    </Link>
                </Button>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6">
            <div class="grid gap-8 lg:grid-cols-12">
                <!-- Gallery -->
                <div class="space-y-3 lg:col-span-7">
                    <div
                        class="relative aspect-[16/10] overflow-hidden rounded-2xl border bg-muted"
                    >
                        <img
                            v-if="vehicle.photos[activeIndex]"
                            :src="vehicle.photos[activeIndex].url"
                            :alt="vehicle.title"
                            class="size-full object-cover"
                        />
                        <div
                            v-else
                            class="flex size-full items-center justify-center bg-[#0A2E5C]/10"
                        >
                            <Car class="size-16 text-[#0A2E5C]/40" />
                        </div>
                        <div class="absolute top-3 left-3">
                            <StatusBadge
                                :label="vehicle.status_label"
                                :variant="vehicle.status_variant"
                            />
                        </div>
                    </div>

                    <div
                        v-if="vehicle.photos.length > 1"
                        class="grid grid-cols-5 gap-2 sm:grid-cols-6"
                    >
                        <button
                            v-for="(photo, idx) in vehicle.photos"
                            :key="photo.id"
                            type="button"
                            class="aspect-square overflow-hidden rounded-lg border-2 transition"
                            :class="
                                idx === activeIndex
                                    ? 'border-[#DC141E]'
                                    : 'border-transparent opacity-75 hover:opacity-100'
                            "
                            @click="activeIndex = idx"
                        >
                            <img
                                :src="photo.url"
                                :alt="photo.caption || vehicle.title"
                                class="size-full object-cover"
                            />
                        </button>
                    </div>
                </div>

                <!-- Summary & Contact -->
                <div class="space-y-5 lg:col-span-5">
                    <div class="rounded-2xl border bg-card p-5 shadow-xs">
                        <p class="font-mono text-xs text-muted-foreground">
                            Ref: {{ vehicle.reference_number }}
                        </p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight">
                            {{ vehicle.title }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ vehicle.condition }} · {{ vehicle.category }}
                        </p>

                        <p
                            class="mt-4 text-3xl font-extrabold text-[#0A2E5C] tabular-nums dark:text-sky-300"
                        >
                            {{
                                vehicle.advertised_price
                                    ? formatMoney(vehicle.advertised_price)
                                    : 'Call for Price'
                            }}
                        </p>

                        <div class="mt-5 flex flex-col gap-2.5">
                            <Button
                                v-if="business.whatsapp"
                                as-child
                                class="min-h-11 w-full bg-emerald-600 text-white hover:bg-emerald-700"
                            >
                                <a
                                    :href="whatsappInquiryUrl()"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <MessageCircle class="mr-2 size-4" />
                                    Enquire on WhatsApp
                                </a>
                            </Button>
                            <Button
                                v-if="business.phone"
                                as-child
                                variant="outline"
                                class="min-h-11 w-full"
                            >
                                <a :href="`tel:${business.phone}`">
                                    <Phone class="mr-2 size-4" />
                                    Call {{ business.phone }}
                                </a>
                            </Button>
                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    class="min-h-10"
                                    @click="copyLink"
                                >
                                    <Check
                                        v-if="copied"
                                        class="mr-1.5 size-4 text-emerald-600"
                                    />
                                    <Copy v-else class="mr-1.5 size-4" />
                                    {{ copied ? 'Copied!' : 'Copy Link' }}
                                </Button>
                                <Button
                                    as-child
                                    variant="secondary"
                                    class="min-h-10"
                                >
                                    <a
                                        :href="facebookShareUrl()"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <Share2 class="mr-1.5 size-4" />
                                        Share
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border bg-card p-5">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            Specifications
                        </h2>
                        <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs text-muted-foreground">Year</dt>
                                <dd class="font-medium">{{ vehicle.year }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Transmission
                                </dt>
                                <dd class="font-medium">
                                    {{ vehicle.transmission }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Fuel Type
                                </dt>
                                <dd class="font-medium">
                                    {{ vehicle.fuel_type }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Mileage
                                </dt>
                                <dd class="font-medium">
                                    {{
                                        vehicle.mileage !== null
                                            ? `${vehicle.mileage.toLocaleString()} km`
                                            : '—'
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Exterior Colour
                                </dt>
                                <dd class="font-medium">
                                    {{ vehicle.exterior_colour }}
                                </dd>
                            </div>
                            <div v-if="vehicle.interior_colour">
                                <dt class="text-xs text-muted-foreground">
                                    Interior Colour
                                </dt>
                                <dd class="font-medium">
                                    {{ vehicle.interior_colour }}
                                </dd>
                            </div>
                            <div v-if="vehicle.engine_size">
                                <dt class="text-xs text-muted-foreground">
                                    Engine
                                </dt>
                                <dd class="font-medium">
                                    {{ vehicle.engine_size }}
                                </dd>
                            </div>
                            <div v-if="vehicle.drive_type">
                                <dt class="text-xs text-muted-foreground">
                                    Drivetrain
                                </dt>
                                <dd class="font-medium">
                                    {{ vehicle.drive_type }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div
                        v-if="vehicle.public_description"
                        class="rounded-2xl border bg-card p-5"
                    >
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            Vehicle Overview
                        </h2>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed">
                            {{ vehicle.public_description }}
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
