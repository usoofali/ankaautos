<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    Copy,
    Download,
    ExternalLink,
    MessageCircle,
    Save,
    Share2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatusBadge, {
    type StatusVariant,
} from '@/components/anka/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormatting } from '@/composables/useFormatting';
import { dashboard } from '@/routes';
import { index, photoPack } from '@/routes/marketing';
import { save as saveAdvertisement } from '@/routes/marketing/advertisement';
import { log as logShare } from '@/routes/marketing/share';

const props = defineProps<{
    vehicle: {
        id: number;
        reference_number: string;
        slug: string;
        title: string;
        advertised_price: string | null;
        is_publicly_listed: boolean;
        status_label: string;
        status_variant: StatusVariant;
        photos: Array<{
            id: number;
            url: string;
            caption: string | null;
            is_primary: boolean;
        }>;
    };
    advertisement: {
        id: number | null;
        headline: string;
        caption: string;
        selected_photo_ids: number[];
    };
    listingUrl: string;
    shares: Array<{
        id: number;
        platform: string;
        shared_by: string;
        shared_at: string;
    }>;
    canManage: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Marketing & Ads', href: index() },
            { title: 'Ad Builder', href: index() },
        ],
    },
});

const { formatDateTime } = useFormatting();
const copiedCaption = ref(false);

const form = useForm({
    headline: props.advertisement.headline,
    caption: props.advertisement.caption,
    selected_photo_ids: [...props.advertisement.selected_photo_ids],
    is_publicly_listed: props.vehicle.is_publicly_listed,
});

function togglePhoto(photoId: number): void {
    const idx = form.selected_photo_ids.indexOf(photoId);
    if (idx === -1) {
        form.selected_photo_ids.push(photoId);
    } else {
        form.selected_photo_ids.splice(idx, 1);
    }
}

const photoPackHref = computed(() => {
    const params = new URLSearchParams();
    form.selected_photo_ids.forEach((id) => params.append('photo_ids[]', String(id)));
    const query = params.toString();
    return `${photoPack.url(props.vehicle.id)}${query ? `?${query}` : ''}`;
});

function save(): void {
    form.post(saveAdvertisement.url(props.vehicle.id), {
        preserveScroll: true,
    });
}

function recordShare(platform: 'whatsapp' | 'facebook' | 'instagram' | 'link_copy'): void {
    if (!props.canManage) {
        return;
    }
    router.post(
        logShare.url(props.vehicle.id),
        { platform },
        { preserveScroll: true, preserveState: true },
    );
}

function shareWhatsApp(): void {
    const url = `https://wa.me/?text=${encodeURIComponent(form.caption)}`;
    window.open(url, '_blank', 'noopener,noreferrer');
    recordShare('whatsapp');
}

function shareFacebook(): void {
    const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(props.listingUrl)}&quote=${encodeURIComponent(form.headline)}`;
    window.open(url, '_blank', 'noopener,noreferrer');
    recordShare('facebook');
}

async function copyInstagramCaption(): Promise<void> {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        await navigator.clipboard.writeText(form.caption);
        copiedCaption.value = true;
        recordShare('instagram');
        setTimeout(() => {
            copiedCaption.value = false;
        }, 2000);
    }
}
</script>

<template>
    <Head :title="`Ad Builder — ${vehicle.title}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="`Ad Builder: ${vehicle.title}`"
            :description="`Reference ${vehicle.reference_number} · Prepare social copy, pick listing photos, and share across WhatsApp, Facebook, and Instagram.`"
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" />
                        Marketing
                    </Link>
                </Button>
                <Button
                    v-if="vehicle.is_publicly_listed"
                    as-child
                    variant="outline"
                    class="min-h-10"
                >
                    <a
                        :href="listingUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <ExternalLink class="mr-1.5 size-4" />
                        View Public Listing
                    </a>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-5 lg:grid-cols-3">
            <form
                class="flex flex-col gap-5 lg:col-span-2"
                @submit.prevent="save"
            >
                <FormSectionCard
                    title="Advertisement Copy & Public Visibility"
                    description="Customize your headline and social caption. Links resolve with live OpenGraph previews."
                >
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5">
                            <input
                                id="is_publicly_listed"
                                v-model="form.is_publicly_listed"
                                type="checkbox"
                                :disabled="!canManage"
                                class="size-4 accent-primary"
                            />
                            <Label
                                for="is_publicly_listed"
                                class="cursor-pointer"
                            >
                                Publish vehicle on the public Anka Auto Sales storefront
                            </Label>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="headline">Headline</Label>
                            <Input
                                id="headline"
                                v-model="form.headline"
                                :disabled="!canManage"
                                class="min-h-10"
                                required
                            />
                            <InputError :message="form.errors.headline" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="caption">Social Media Caption</Label>
                            <textarea
                                id="caption"
                                v-model="form.caption"
                                :disabled="!canManage"
                                rows="9"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 font-sans text-sm"
                                required
                            />
                            <InputError :message="form.errors.caption" />
                        </div>
                    </div>
                </FormSectionCard>

                <FormSectionCard
                    title="Select Campaign Photos"
                    description="Choose which gallery photos to bundle into your Instagram/Social ZIP download pack."
                >
                    <p
                        v-if="vehicle.photos.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No photos uploaded for this vehicle yet. Upload photos on the vehicle detail page.
                    </p>
                    <div
                        v-else
                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"
                    >
                        <button
                            v-for="photo in vehicle.photos"
                            :key="photo.id"
                            type="button"
                            class="group relative aspect-square overflow-hidden rounded-xl border-2 text-left transition"
                            :class="
                                form.selected_photo_ids.includes(photo.id)
                                    ? 'border-[#DC141E] ring-2 ring-[#DC141E]/30'
                                    : 'border-border opacity-70 hover:opacity-100'
                            "
                            @click="togglePhoto(photo.id)"
                        >
                            <img
                                :src="photo.url"
                                :alt="photo.caption || vehicle.title"
                                class="size-full object-cover"
                            />
                            <span
                                v-if="form.selected_photo_ids.includes(photo.id)"
                                class="absolute top-2 right-2 flex size-6 items-center justify-center rounded-full bg-[#DC141E] text-white shadow"
                            >
                                <Check class="size-3.5" />
                            </span>
                        </button>
                    </div>

                    <div v-if="canManage" class="mt-5 flex justify-end">
                        <Button
                            type="submit"
                            class="min-h-10"
                            :disabled="form.processing"
                        >
                            <Spinner
                                v-if="form.processing"
                                class="mr-2 size-4"
                            />
                            <Save v-else class="mr-1.5 size-4" />
                            Save Advertisement
                        </Button>
                    </div>
                </FormSectionCard>
            </form>

            <div class="flex flex-col gap-5">
                <FormSectionCard
                    title="Share Channels"
                    description="One-click sharing with automatic marketing history tracking."
                >
                    <div class="flex flex-col gap-2.5">
                        <Button
                            type="button"
                            class="min-h-11 w-full bg-emerald-600 text-white hover:bg-emerald-700"
                            @click="shareWhatsApp"
                        >
                            <MessageCircle class="mr-2 size-4" />
                            Share on WhatsApp
                        </Button>
                        <Button
                            type="button"
                            variant="default"
                            class="min-h-11 w-full"
                            @click="shareFacebook"
                        >
                            <Share2 class="mr-2 size-4" />
                            Share on Facebook
                        </Button>
                        <Button
                            type="button"
                            variant="secondary"
                            class="min-h-11 w-full"
                            @click="copyInstagramCaption"
                        >
                            <Check
                                v-if="copiedCaption"
                                class="mr-2 size-4 text-emerald-600"
                            />
                            <Copy v-else class="mr-2 size-4" />
                            {{
                                copiedCaption
                                    ? 'Caption Copied!'
                                    : 'Copy Instagram Caption'
                            }}
                        </Button>
                        <Button
                            v-if="vehicle.photos.length > 0"
                            as-child
                            variant="outline"
                            class="min-h-11 w-full"
                        >
                            <a :href="photoPackHref">
                                <Download class="mr-2 size-4" />
                                Download Photo Pack (.zip)
                            </a>
                        </Button>
                    </div>
                </FormSectionCard>

                <FormSectionCard
                    title="Recent Share Activity"
                    description="Logged shares for this vehicle."
                >
                    <p
                        v-if="shares.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No shares logged yet.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="s in shares"
                            :key="s.id"
                            class="flex items-center justify-between py-2"
                        >
                            <div>
                                <StatusBadge
                                    :label="s.platform.toUpperCase()"
                                    variant="info"
                                />
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    by {{ s.shared_by }}
                                </p>
                            </div>
                            <span class="text-xs text-muted-foreground">
                                {{ formatDateTime(s.shared_at) }}
                            </span>
                        </li>
                    </ul>
                </FormSectionCard>
            </div>
        </div>
    </div>
</template>
