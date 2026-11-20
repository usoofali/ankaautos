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
import { create, index, store } from '@/routes/customers';

defineProps<{
    categoryOptions: Array<{ value: string; label: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Customers', href: index() },
            { title: 'Register Customer', href: create() },
        ],
    },
});

const form = useForm({
    customer_type: 'individual',
    name: '',
    phone: '',
    alt_phone: '',
    email: '',
    address: '',
    id_type: 'NIN',
    id_number: '',
    notes: '',
});

function submit(): void {
    form.post(store.url());
}
</script>

<template>
    <Head title="Register Customer" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Register Customer"
            description="Create a buyer record for enquiries, reservations, and vehicle purchases."
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Customers
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="max-w-3xl space-y-5" @submit.prevent="submit">
            <FormSectionCard title="Customer Information">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="customer_type">Customer Type *</Label>
                        <select
                            id="customer_type"
                            v-model="form.customer_type"
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
                        <Label for="name">Full / Company Name *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="phone">Phone Number *</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            required
                            class="min-h-10"
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
                        <Label for="address">Address</Label>
                        <textarea
                            id="address"
                            v-model="form.address"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="notes">Notes</Label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
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
                        Register Customer
                    </Button>
                </template>
            </FormSectionCard>
        </form>
    </div>
</template>
