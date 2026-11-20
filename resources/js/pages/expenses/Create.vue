<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import { computed, watch } from 'vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { create, index, store } from '@/routes/expenses';

type VehicleOption = {
    id: number;
    label: string;
    ownership_type: string;
    has_consignment: boolean;
};

const props = defineProps<{
    vehicles: VehicleOption[];
    categories: Array<{ id: number; name: string }>;
    bearerOptions: Array<{ value: string; label: string }>;
    paymentMethods: Array<{
        id: number;
        name: string;
        requires_reference: boolean;
    }>;
    financialAccounts: Array<{ id: number; name: string }>;
    canPayImmediately: boolean;
    preselectedVehicleId: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Expenses', href: index() },
            { title: 'Record Expense', href: create() },
        ],
    },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    vehicle_id: props.preselectedVehicleId ?? props.vehicles[0]?.id ?? 0,
    expense_category_id: props.categories[0]?.id ?? 0,
    borne_by: 'company',
    description: '',
    amount: '',
    expense_date: today,
    vendor_name: '',
    receipt: null as File | null,
    pay_immediately: false,
    payment_method_id: props.paymentMethods[0]?.id ?? 0,
    financial_account_id: props.financialAccounts[0]?.id ?? 0,
    payment_reference: '',
});

const selectedVehicle = computed(() =>
    props.vehicles.find((v) => v.id === form.vehicle_id),
);

const canSelectOwnerDeductible = computed(
    () =>
        selectedVehicle.value?.ownership_type === 'consignment' &&
        selectedVehicle.value?.has_consignment,
);

watch(canSelectOwnerDeductible, (allowed) => {
    if (!allowed && form.borne_by === 'owner_deductible') {
        form.borne_by = 'company';
    }
});

function handleReceiptFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.receipt = input.files?.[0] ?? null;
}

function submit(): void {
    form.post(store.url(), { forceFormData: true });
}
</script>

<template>
    <Head title="Record Vehicle Expense" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Record Vehicle Expense"
            description="Log a cost incurred for a specific vehicle and specify whether the dealership or consignment owner bears the cost."
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Expenses
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="max-w-4xl space-y-5" @submit.prevent="submit">
            <FormSectionCard title="Expense Details">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="vehicle_id"
                            >Vehicle
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="vehicle_id"
                            v-model.number="form.vehicle_id"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="v in vehicles"
                                :key="v.id"
                                :value="v.id"
                            >
                                {{ v.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.vehicle_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="expense_category_id"
                            >Expense Category
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="expense_category_id"
                            v-model.number="form.expense_category_id"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="cat in categories"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                        <InputError
                            :message="form.errors.expense_category_id"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="borne_by"
                            >Cost Responsibility
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="borne_by"
                            v-model="form.borne_by"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option value="company">Company-Borne</option>
                            <option
                                value="owner_deductible"
                                :disabled="!canSelectOwnerDeductible"
                            >
                                Owner / Deductible (Consignment Only)
                            </option>
                        </select>
                        <p class="text-xs text-muted-foreground">
                            Owner-deductible expenses automatically pull into
                            the owner's sale settlement or withdrawal statement.
                        </p>
                        <InputError :message="form.errors.borne_by" />
                    </div>

                    <div class="space-y-2">
                        <Label for="amount"
                            >Amount (₦)
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            class="min-h-10 tabular-nums"
                        />
                        <InputError :message="form.errors.amount" />
                    </div>

                    <div class="space-y-2">
                        <Label for="expense_date"
                            >Expense Date
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="expense_date"
                            v-model="form.expense_date"
                            type="date"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.expense_date" />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="description"
                            >Description
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="description"
                            v-model="form.description"
                            required
                            class="min-h-10"
                            placeholder="e.g. Front brake pads replacement and wheel balancing"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="space-y-2">
                        <Label for="vendor_name"
                            >Vendor / Mechanic / Workshop</Label
                        >
                        <Input
                            id="vendor_name"
                            v-model="form.vendor_name"
                            class="min-h-10"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="receipt"
                            >Receipt / Invoice Attachment (Optional)</Label
                        >
                        <input
                            id="receipt"
                            type="file"
                            accept=".pdf,image/*"
                            class="block w-full rounded-md border border-input bg-background px-3 py-2 text-xs text-foreground file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1 file:text-xs file:font-medium file:text-primary-foreground"
                            @change="handleReceiptFile"
                        />
                        <InputError :message="form.errors.receipt" />
                    </div>
                </div>
            </FormSectionCard>

            <FormSectionCard
                v-if="canPayImmediately"
                title="Immediate Payment (Optional)"
                description="Mark this expense as paid immediately and post to the financial ledger."
            >
                <div class="space-y-4">
                    <label
                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-border/70 p-3"
                    >
                        <Checkbox
                            :model-value="form.pay_immediately"
                            @update:model-value="
                                (val) => (form.pay_immediately = Boolean(val))
                            "
                        />
                        <span class="text-sm font-medium"
                            >Record full payment immediately</span
                        >
                    </label>

                    <div
                        v-if="form.pay_immediately"
                        class="grid gap-4 sm:grid-cols-3"
                    >
                        <div class="space-y-2">
                            <Label for="payment_method_id"
                                >Payment Method *</Label
                            >
                            <select
                                id="payment_method_id"
                                v-model.number="form.payment_method_id"
                                required
                                class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            >
                                <option
                                    v-for="m in paymentMethods"
                                    :key="m.id"
                                    :value="m.id"
                                >
                                    {{ m.name }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="financial_account_id"
                                >Paid From Account *</Label
                            >
                            <select
                                id="financial_account_id"
                                v-model.number="form.financial_account_id"
                                required
                                class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            >
                                <option
                                    v-for="a in financialAccounts"
                                    :key="a.id"
                                    :value="a.id"
                                >
                                    {{ a.name }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="payment_reference"
                                >Transfer / Receipt Ref</Label
                            >
                            <Input
                                id="payment_reference"
                                v-model="form.payment_reference"
                                class="min-h-10"
                            />
                        </div>
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
                        Record Expense
                    </Button>
                </template>
            </FormSectionCard>

            <div v-else class="flex justify-end gap-2">
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
                    Record Expense
                </Button>
            </div>
        </form>
    </div>
</template>
