<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { index, show, update } from '@/routes/owners';

const props = defineProps<{
    owner: {
        id: number;
        reference_number: string;
        owner_category: string;
        name: string;
        phone: string;
        alt_phone: string;
        email: string;
        address: string;
        id_type: string;
        id_number: string;
        bank_name: string;
        bank_account_name: string;
        bank_account_number: string;
        remarks: string;
        is_active: boolean;
    };
    categoryOptions: Array<{ value: string; label: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Owners', href: index() },
            { title: 'Edit Owner', href: index() },
        ],
    },
});

const form = useForm({
    owner_category: props.owner.owner_category,
    name: props.owner.name,
    phone: props.owner.phone,
    alt_phone: props.owner.alt_phone,
    email: props.owner.email,
    address: props.owner.address,
    id_type: props.owner.id_type,
    id_number: props.owner.id_number,
    bank_name: props.owner.bank_name,
    bank_account_name: props.owner.bank_account_name,
    bank_account_number: props.owner.bank_account_number,
    remarks: props.owner.remarks,
    is_active: props.owner.is_active,
});

function submit(): void {
    form.put(update.url(props.owner.id));
}
</script>

<template>
    <Head :title="`Edit ${owner.name}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="`Edit ${owner.name}`"
            :description="owner.reference_number"
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="show(owner.id)">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Profile
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="max-w-4xl space-y-5" @submit.prevent="submit">
            <FormSectionCard title="Owner Information & Bank Details">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="owner_category">Owner Category</Label>
                        <select
                            id="owner_category"
                            v-model="form.owner_category"
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
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="phone">Phone</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.phone" />
                    </div>
                    <div class="space-y-2">
                        <Label for="alt_phone">Alt Phone</Label>
                        <Input
                            id="alt_phone"
                            v-model="form.alt_phone"
                            class="min-h-10"
                        />
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
                    <div class="space-y-2">
                        <Label for="bank_name">Bank Name</Label>
                        <Input
                            id="bank_name"
                            v-model="form.bank_name"
                            class="min-h-10"
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
                            class="min-h-10 font-mono"
                        />
                    </div>
                    <label
                        class="flex cursor-pointer items-center gap-2.5 self-end pb-2 text-sm"
                    >
                        <Checkbox
                            :model-value="form.is_active"
                            @update:model-value="
                                (v) => (form.is_active = Boolean(v))
                            "
                        />
                        <span>Owner profile active</span>
                    </label>
                </div>

                <template #footer>
                    <Button as-child variant="outline" class="min-h-10">
                        <Link :href="show(owner.id)">Cancel</Link>
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
