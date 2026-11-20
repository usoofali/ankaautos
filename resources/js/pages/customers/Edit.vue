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
import { index, show, update } from '@/routes/customers';

const props = defineProps<{
    customer: {
        id: number;
        reference_number: string;
        customer_type: string;
        name: string;
        phone: string;
        alt_phone: string;
        email: string;
        address: string;
        id_type: string;
        id_number: string;
        notes: string;
    };
    categoryOptions: Array<{ value: string; label: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Customers', href: index() },
            { title: 'Edit Customer', href: index() },
        ],
    },
});

const form = useForm({ ...props.customer });

function submit(): void {
    form.put(update.url(props.customer.id));
}
</script>

<template>
    <Head :title="`Edit ${customer.name}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="`Edit ${customer.name}`"
            :description="customer.reference_number"
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="show(customer.id)">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Profile
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="max-w-3xl space-y-5" @submit.prevent="submit">
            <FormSectionCard title="Customer Information">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="customer_type">Customer Type</Label>
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
                        <Label for="name">Name *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="phone">Phone *</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.phone" />
                    </div>
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="min-h-10"
                        />
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
                        <Link :href="show(customer.id)">Cancel</Link>
                    </Button>
                    <Button
                        type="submit"
                        class="min-h-10"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" class="mr-1.5" />
                        <Save v-else class="mr-1.5 size-4" aria-hidden="true" />
                        Save Changes
                    </Button>
                </template>
            </FormSectionCard>
        </form>
    </div>
</template>
