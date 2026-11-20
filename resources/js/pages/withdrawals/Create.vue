<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormatting } from '@/composables/useFormatting';
import { dashboard } from '@/routes';
import { index, store } from '@/routes/withdrawals';

type ChargeRow = {
    charge_category_id: number | '';
    custom_label: string;
    amount: string;
    notes: string;
};

const props = defineProps<{
    consignments: Array<{
        id: number;
        label: string;
        owner_expenses_total: string;
    }>;
    chargeCategories: Array<{
        id: number;
        name: string;
        default_amount: string | null;
    }>;
    preselectedConsignmentId: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Withdrawals', href: index() },
            { title: 'Initiate Withdrawal', href: index() },
        ],
    },
});

const { formatMoney } = useFormatting();

const form = useForm({
    consignment_id:
        props.preselectedConsignmentId ?? (props.consignments[0]?.id ?? ''),
    reason: '',
    charges: [] as ChargeRow[],
});

const selectedConsignment = computed(() =>
    props.consignments.find((c) => c.id === Number(form.consignment_id)),
);

const chargesTotal = computed(() =>
    form.charges.reduce((sum, c) => sum + Number(c.amount || 0), 0),
);

const totalDuePreview = computed(
    () =>
        Number(selectedConsignment.value?.owner_expenses_total ?? 0) +
        chargesTotal.value,
);

function addChargeRow(): void {
    form.charges.push({
        charge_category_id: '',
        custom_label: '',
        amount: '',
        notes: '',
    });
}

function removeChargeRow(idx: number): void {
    form.charges.splice(idx, 1);
}

function onCategoryChange(row: ChargeRow): void {
    const cat = props.chargeCategories.find(
        (c) => c.id === Number(row.charge_category_id),
    );
    if (cat?.default_amount && !row.amount) {
        row.amount = cat.default_amount;
    }
}

function submit(): void {
    form.transform((data) => ({
        ...data,
        charges: data.charges.filter(
            (c) => Number(c.amount) > 0 && (c.charge_category_id || c.custom_label),
        ),
    })).post(store.url());
}
</script>

<template>
    <Head title="Initiate Consignment Withdrawal" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Initiate Consignment Withdrawal"
            description="Automatically aggregates unsettled owner-deductible vehicle expenses and lets you apply withdrawal or parking charges."
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Withdrawals
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="grid gap-5 lg:grid-cols-3" @submit.prevent="submit">
            <div class="flex flex-col gap-5 lg:col-span-2">
                <FormSectionCard
                    title="Consignment & Reason"
                    description="Select the active consignment being withdrawn by its owner."
                >
                    <div class="space-y-4">
                        <div class="grid gap-1.5">
                            <Label for="consignment_id">Consignment</Label>
                            <select
                                id="consignment_id"
                                v-model="form.consignment_id"
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                required
                            >
                                <option
                                    v-for="c in consignments"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ c.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.consignment_id" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="reason">Withdrawal Reason</Label>
                            <textarea
                                id="reason"
                                v-model="form.reason"
                                rows="3"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="e.g., Owner requested return of vehicle for personal use."
                                required
                            />
                            <InputError :message="form.errors.reason" />
                        </div>
                    </div>
                </FormSectionCard>

                <FormSectionCard
                    title="Withdrawal & Service Charges"
                    description="Add applicable parking, detailing, documentation, or administrative withdrawal fees."
                >
                    <div class="space-y-3">
                        <div
                            v-for="(charge, idx) in form.charges"
                            :key="idx"
                            class="grid gap-3 rounded-lg border p-3 sm:grid-cols-12"
                        >
                            <div class="sm:col-span-4">
                                <Label class="text-xs">Charge Category</Label>
                                <select
                                    v-model="charge.charge_category_id"
                                    class="mt-1 h-10 w-full rounded-md border border-input bg-background px-2.5 text-sm"
                                    @change="onCategoryChange(charge)"
                                >
                                    <option value="">Custom Charge</option>
                                    <option
                                        v-for="cat in chargeCategories"
                                        :key="cat.id"
                                        :value="cat.id"
                                    >
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="sm:col-span-4">
                                <Label class="text-xs">Custom Label</Label>
                                <Input
                                    v-model="charge.custom_label"
                                    placeholder="Optional label"
                                    class="mt-1 min-h-10"
                                />
                            </div>
                            <div class="sm:col-span-3">
                                <Label class="text-xs">Amount (₦)</Label>
                                <Input
                                    v-model="charge.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="mt-1 min-h-10"
                                />
                            </div>
                            <div class="flex items-end justify-end sm:col-span-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    @click="removeChargeRow(idx)"
                                >
                                    <Trash2 class="size-4 text-destructive" />
                                </Button>
                            </div>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="min-h-10"
                            @click="addChargeRow"
                        >
                            <Plus class="mr-1.5 size-4" />
                            Add Withdrawal Charge
                        </Button>
                    </div>
                </FormSectionCard>
            </div>

            <FormSectionCard
                title="Clearance Preview"
                description="Owner must pay 100% of this total before vehicle release."
            >
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">
                            Unsettled Owner Expenses
                        </dt>
                        <dd class="font-medium tabular-nums">
                            {{
                                formatMoney(
                                    selectedConsignment?.owner_expenses_total ??
                                        0,
                                )
                            }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">
                            Withdrawal Charges
                        </dt>
                        <dd class="font-medium tabular-nums">
                            {{ formatMoney(chargesTotal) }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between border-t pt-2.5 text-base font-bold"
                    >
                        <dt>Total Clearance Due</dt>
                        <dd class="tabular-nums">
                            {{ formatMoney(totalDuePreview) }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-5 flex flex-col gap-2">
                    <Button
                        type="submit"
                        class="min-h-11 w-full"
                        :disabled="form.processing || consignments.length === 0"
                    >
                        <Spinner
                            v-if="form.processing"
                            class="mr-2 size-4"
                        />
                        Initiate Withdrawal
                    </Button>
                    <Button as-child variant="outline" class="min-h-10 w-full">
                        <Link :href="index()">Cancel</Link>
                    </Button>
                </div>
            </FormSectionCard>
        </form>
    </div>
</template>
