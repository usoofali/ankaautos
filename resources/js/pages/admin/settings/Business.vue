<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { edit, update } from '@/routes/admin/business-settings';

const props = defineProps<{
    settings: Record<string, string>;
    timezones: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Business Settings', href: edit() },
        ],
    },
});

const form = useForm({
    company_name: props.settings.company_name ?? 'Anka Auto Sales',
    tagline: props.settings.tagline ?? '',
    address: props.settings.address ?? '',
    phone: props.settings.phone ?? '',
    whatsapp_number: props.settings.whatsapp_number ?? '',
    email: props.settings.email ?? '',
    currency_code: props.settings.currency_code ?? 'NGN',
    currency_symbol: props.settings.currency_symbol ?? '₦',
    timezone: props.settings.timezone ?? 'Africa/Lagos',
    reference_prefix: props.settings.reference_prefix ?? 'ANK',
    receipt_footer: props.settings.receipt_footer ?? '',
});

function submit(): void {
    form.put(update.url());
}
</script>

<template>
    <Head title="Business Settings" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Business Settings"
            description="Configure dealership branding, WhatsApp sales contact, currency formatting, timezone, and official document headers."
        />

        <form class="max-w-4xl space-y-5" @submit.prevent="submit">
            <FormSectionCard
                title="Dealership Identity & Contact"
                description="Used across public vehicle listings, PDF receipts, consignment agreements, and WhatsApp sharing."
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="company_name"
                            >Dealership Name
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="company_name"
                            v-model="form.company_name"
                            type="text"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.company_name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="tagline">Tagline</Label>
                        <Input
                            id="tagline"
                            v-model="form.tagline"
                            type="text"
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.tagline" />
                    </div>

                    <div class="space-y-2">
                        <Label for="phone"
                            >Primary Phone
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            required
                            class="min-h-10"
                            placeholder="+234 803 000 0000"
                        />
                        <InputError :message="form.errors.phone" />
                    </div>

                    <div class="space-y-2">
                        <Label for="whatsapp_number"
                            >WhatsApp Business Number
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="whatsapp_number"
                            v-model="form.whatsapp_number"
                            type="tel"
                            required
                            class="min-h-10"
                            placeholder="2348030000000"
                        />
                        <p class="text-xs text-muted-foreground">
                            Include country code without leading zero (e.g.
                            2348030000000).
                        </p>
                        <InputError :message="form.errors.whatsapp_number" />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="email"
                            >Official Email
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="address"
                            >Showroom Address
                            <span class="text-destructive">*</span></Label
                        >
                        <textarea
                            id="address"
                            v-model="form.address"
                            rows="2"
                            required
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                        />
                        <InputError :message="form.errors.address" />
                    </div>
                </div>
            </FormSectionCard>

            <FormSectionCard
                title="Localization, Numbering & Documents"
                description="Nigerian Naira formatting, local timezone presentation, and document reference prefix."
            >
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-2">
                        <Label for="currency_code"
                            >Currency Code
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="currency_code"
                            v-model="form.currency_code"
                            type="text"
                            maxlength="3"
                            required
                            class="min-h-10 uppercase"
                        />
                        <InputError :message="form.errors.currency_code" />
                    </div>

                    <div class="space-y-2">
                        <Label for="currency_symbol"
                            >Currency Symbol
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="currency_symbol"
                            v-model="form.currency_symbol"
                            type="text"
                            maxlength="10"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.currency_symbol" />
                    </div>

                    <div class="space-y-2">
                        <Label for="reference_prefix"
                            >Reference Prefix
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="reference_prefix"
                            v-model="form.reference_prefix"
                            type="text"
                            maxlength="8"
                            required
                            class="min-h-10 uppercase"
                        />
                        <p class="text-[11px] text-muted-foreground">
                            Example:
                            {{ form.reference_prefix || 'ANK' }}-VEH-2026-0001
                        </p>
                        <InputError :message="form.errors.reference_prefix" />
                    </div>

                    <div class="space-y-2">
                        <Label for="timezone"
                            >Local Timezone
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="timezone"
                            v-model="form.timezone"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                        >
                            <option
                                v-for="tz in timezones"
                                :key="tz"
                                :value="tz"
                            >
                                {{ tz }}
                            </option>
                        </select>
                        <InputError :message="form.errors.timezone" />
                    </div>

                    <div class="space-y-2 sm:col-span-2 lg:col-span-4">
                        <Label for="receipt_footer"
                            >Official Receipt & Statement Footer</Label
                        >
                        <textarea
                            id="receipt_footer"
                            v-model="form.receipt_footer"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                        />
                        <InputError :message="form.errors.receipt_footer" />
                    </div>
                </div>

                <template #footer>
                    <Button
                        type="submit"
                        class="min-h-10 w-full sm:w-auto"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" class="mr-1.5" />
                        <Save v-else class="mr-1.5 size-4" aria-hidden="true" />
                        Save Business Settings
                    </Button>
                </template>
            </FormSectionCard>
        </form>
    </div>
</template>
