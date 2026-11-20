<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { create, index, store } from '@/routes/owners';

defineProps<{
    categoryOptions: Array<{ value: string; label: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Owners', href: index() },
            { title: 'Register Owner', href: create() },
        ],
    },
});

const form = useForm({
    owner_category: 'individual',
    name: '',
    phone: '',
    alt_phone: '',
    email: '',
    address: '',
    id_type: 'NIN',
    id_number: '',
    bank_name: '',
    bank_account_name: '',
    bank_account_number: '',
    remarks: '',
    is_active: true,
});

function submit(): void {
    form.post(store.url());
}
</script>

<template>
    <Head title="Register Vehicle Owner" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Register Vehicle Owner"
            description="Create a third-party consignor profile with contact, identification, and settlement payout bank details."
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Owners
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="max-w-4xl space-y-5" @submit.prevent="submit">
            <FormSectionCard title="Owner Identity & Contact">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="owner_category"
                            >Owner Category
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="owner_category"
                            v-model="form.owner_category"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="opt in categoryOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="name"
                            >Full Name / Organization Name
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="phone"
                            >Phone Number
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
                        <Label for="alt_phone">Alternative Phone</Label>
                        <Input
                            id="alt_phone"
                            v-model="form.alt_phone"
                            type="tel"
                            class="min-h-10"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Email Address</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <Label for="id_type">ID Type</Label>
                            <Input
                                id="id_type"
                                v-model="form.id_type"
                                class="min-h-10"
                                placeholder="NIN / Passport / CAC"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="id_number">ID Number</Label>
                            <Input
                                id="id_number"
                                v-model="form.id_number"
                                class="min-h-10 font-mono"
                            />
                        </div>
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="address"
                            >Residential / Business Address</Label
                        >
                        <textarea
                            id="address"
                            v-model="form.address"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                    </div>
                </div>
            </FormSectionCard>

            <FormSectionCard
                title="Settlement Payout Bank Details"
                description="Used when disbursing net proceeds after a consignment sale."
            >
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="bank_name">Bank Name</Label>
                        <Input
                            id="bank_name"
                            v-model="form.bank_name"
                            class="min-h-10"
                            placeholder="e.g. Zenith Bank"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="bank_account_name">Account Name</Label>
                        <Input
                            id="bank_account_name"
                            v-model="form.bank_account_name"
                            class="min-h-10"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="bank_account_number">Account Number</Label>
                        <Input
                            id="bank_account_number"
                            v-model="form.bank_account_number"
                            class="min-h-10 font-mono tabular-nums"
                        />
                    </div>

                    <div class="space-y-2 sm:col-span-3">
                        <Label for="remarks">Additional Remarks</Label>
                        <textarea
                            id="remarks"
                            v-model="form.remarks"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                    </div>
                </div>

                <template #footer>
                    <Button as-child variant="outline" class="min-h-10">
                        <Link :href="index()">Cancel</Link>
                    </Button>
                    <Button
                        type="submit"
                        class="min-h-10"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" class="mr-1.5" />
                        <Save v-else class="mr-1.5 size-4" aria-hidden="true" />
                        Register Owner
                    </Button>
                </template>
            </FormSectionCard>
        </form>
    </div>
</template>
