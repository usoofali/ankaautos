<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2 } from '@lucide/vue';
import { computed, watch } from 'vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormatting } from '@/composables/useFormatting';
import { dashboard } from '@/routes';
import { index, store } from '@/routes/sales';

type ChargeRow = {
    charge_category_id: number | '';
    custom_label: string;
    amount: string;
    notes: string;
};

const props = defineProps<{
    vehicles: Array<{
        id: number;
        label: string;
        advertised_price: string | null;
        ownership_type: string;
        commission_type: string | null;
    }>;
    customers: Array<{
        id: number;
        reference_number: string;
        name: string;
        phone: string;
    }>;
    paymentMethods: Array<{
        id: number;
        name: string;
        requires_reference: boolean;
    }>;
    financialAccounts: Array<{ id: number; name: string }>;
    chargeCategories: Array<{
        id: number;
        name: string;
        default_amount: string | null;
    }>;
    canApproveDiscount: boolean;
    preselectedReservation: {
        id: number;
        reference_number: string;
        vehicle_id: number;
        customer_id: number;
        agreed_price: string;
        deposit_amount: string;
    } | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Sales', href: index() },
            { title: 'Record Sale', href: index() },
        ],
    },
});

const { formatMoney } = useFormatting();

function generateIdempotencyKey(): string {
    if (
        typeof crypto !== 'undefined' &&
        typeof crypto.randomUUID === 'function'
    ) {
        return crypto.randomUUID();
    }
    return `sale-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

const form = useForm({
    vehicle_id:
        props.preselectedReservation?.vehicle_id ?? props.vehicles[0]?.id ?? '',
    customer_id:
        props.preselectedReservation?.customer_id ??
        props.customers[0]?.id ??
        '',
    reservation_id: props.preselectedReservation?.id ?? null,
    list_price:
        props.preselectedReservation?.agreed_price ??
        props.vehicles[0]?.advertised_price ??
        '',
    discount_amount: '0',
    discount_reason: '',
    is_installment: false,
    installment_terms: '',
    initial_payment_amount: '',
    payment_method_id: props.paymentMethods[0]?.id ?? '',
    financial_account_id: props.financialAccounts[0]?.id ?? '',
    paid_at: new Date().toISOString().slice(0, 10),
    external_reference: '',
    custom_commission_amount: '',
    notes: '',
    idempotency_key: generateIdempotencyKey(),
    charges: [] as ChargeRow[],
});

const selectedVehicle = computed(() =>
    props.vehicles.find((v) => v.id === Number(form.vehicle_id)),
);

watch(
    () => form.vehicle_id,
    (id) => {
        if (props.preselectedReservation !== null) {
            return;
        }
        const vehicle = props.vehicles.find((v) => v.id === Number(id));
        if (vehicle?.advertised_price) {
            form.list_price = vehicle.advertised_price;
        }
    },
);

const depositCredit = computed(() =>
    Number(props.preselectedReservation?.deposit_amount ?? 0),
);

const finalPricePreview = computed(() => {
    const list = Number(form.list_price || 0);
    const discount = props.canApproveDiscount
        ? Number(form.discount_amount || 0)
        : 0;
    return Math.max(0, list - discount);
});

const totalCollectedNow = computed(
    () => depositCredit.value + Number(form.initial_payment_amount || 0),
);

const balancePreview = computed(() =>
    Math.max(0, finalPricePreview.value - totalCollectedNow.value),
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

function onChargeCategoryChange(row: ChargeRow): void {
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
        discount_amount: props.canApproveDiscount ? data.discount_amount : '0',
        initial_payment_amount:
            data.initial_payment_amount === ''
                ? null
                : data.initial_payment_amount,
        custom_commission_amount:
            data.custom_commission_amount === ''
                ? null
                : data.custom_commission_amount,
        charges: data.charges.filter(
            (c) =>
                Number(c.amount) > 0 &&
                (c.charge_category_id || c.custom_label),
        ),
    })).post(store.url());
}
</script>

<template>
    <Head title="Record Vehicle Sale" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Record Vehicle Sale"
            :description="
                preselectedReservation
                    ? `Converting reservation ${preselectedReservation.reference_number} — deposit of ${formatMoney(preselectedReservation.deposit_amount)} will be carried over automatically.`
                    : 'Confirm a direct vehicle sale with full payment or an approved installment plan.'
            "
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Sales
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <div
                v-if="preselectedReservation"
                class="rounded-xl border border-primary/25 bg-primary/5 p-4 text-sm"
            >
                <p class="font-semibold text-primary">
                    Reservation Conversion:
                    {{ preselectedReservation.reference_number }}
                </p>
                <p class="mt-1 text-muted-foreground">
                    Customer deposit of
                    <strong class="text-foreground">{{
                        formatMoney(preselectedReservation.deposit_amount)
                    }}</strong>
                    is already recorded and will be credited toward this sale.
                </p>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="flex flex-col gap-5 lg:col-span-2">
                    <FormSectionCard
                        title="Vehicle & Buyer"
                        description="Select the vehicle being sold and the purchasing customer."
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-1.5 sm:col-span-2">
                                <Label for="vehicle_id">Vehicle</Label>
                                <select
                                    id="vehicle_id"
                                    v-model="form.vehicle_id"
                                    :disabled="preselectedReservation !== null"
                                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm disabled:opacity-60"
                                    required
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

                            <div class="grid gap-1.5 sm:col-span-2">
                                <Label for="customer_id">Customer</Label>
                                <select
                                    id="customer_id"
                                    v-model="form.customer_id"
                                    :disabled="preselectedReservation !== null"
                                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm disabled:opacity-60"
                                    required
                                >
                                    <option
                                        v-for="c in customers"
                                        :key="c.id"
                                        :value="c.id"
                                    >
                                        {{ c.name }} ({{ c.phone }} ·
                                        {{ c.reference_number }})
                                    </option>
                                </select>
                                <InputError
                                    :message="form.errors.customer_id"
                                />
                            </div>
                        </div>
                    </FormSectionCard>

                    <FormSectionCard
                        title="Pricing & Payment Terms"
                        description="Full payment is required unless the sale is recorded on an installment plan."
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="list_price"
                                    >Agreed List Price (₦)</Label
                                >
                                <Input
                                    id="list_price"
                                    v-model="form.list_price"
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    class="min-h-10"
                                    required
                                />
                                <InputError :message="form.errors.list_price" />
                            </div>

                            <div v-if="canApproveDiscount" class="grid gap-1.5">
                                <Label for="discount_amount">
                                    Manager Discount (₦)
                                </Label>
                                <Input
                                    id="discount_amount"
                                    v-model="form.discount_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="min-h-10"
                                />
                                <InputError
                                    :message="form.errors.discount_amount"
                                />
                            </div>

                            <div
                                v-if="
                                    canApproveDiscount &&
                                    Number(form.discount_amount) > 0
                                "
                                class="grid gap-1.5 sm:col-span-2"
                            >
                                <Label for="discount_reason">
                                    Discount Justification (Mandatory)
                                </Label>
                                <Input
                                    id="discount_reason"
                                    v-model="form.discount_reason"
                                    placeholder="State reason for approved price reduction"
                                    class="min-h-10"
                                    required
                                />
                                <InputError
                                    :message="form.errors.discount_reason"
                                />
                            </div>

                            <div
                                v-if="
                                    selectedVehicle?.commission_type ===
                                    'custom'
                                "
                                class="grid gap-1.5 sm:col-span-2"
                            >
                                <Label for="custom_commission_amount">
                                    Custom Consignment Commission (₦)
                                </Label>
                                <Input
                                    id="custom_commission_amount"
                                    v-model="form.custom_commission_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="min-h-10"
                                    placeholder="Enter agreed commission for this custom consignment"
                                />
                                <InputError
                                    :message="
                                        form.errors.custom_commission_amount
                                    "
                                />
                            </div>

                            <div
                                class="flex items-center gap-2.5 sm:col-span-2"
                            >
                                <input
                                    id="is_installment"
                                    v-model="form.is_installment"
                                    type="checkbox"
                                    class="size-4 rounded border-input accent-primary"
                                />
                                <Label
                                    for="is_installment"
                                    class="cursor-pointer font-normal"
                                >
                                    Approve as an installment sale (allows
                                    partial payment while tracking customer
                                    receivable)
                                </Label>
                            </div>

                            <div
                                v-if="form.is_installment"
                                class="grid gap-1.5 sm:col-span-2"
                            >
                                <Label for="installment_terms">
                                    Installment Schedule & Terms
                                </Label>
                                <textarea
                                    id="installment_terms"
                                    v-model="form.installment_terms"
                                    rows="2"
                                    placeholder="e.g., 60% upfront, remaining 40% due within 30 days."
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                                <InputError
                                    :message="form.errors.installment_terms"
                                />
                            </div>
                        </div>
                    </FormSectionCard>

                    <FormSectionCard
                        title="Initial Payment Collection"
                        description="Record cash or transfer received today toward the sale price."
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="initial_payment_amount">
                                    Payment Received Now (₦)
                                </Label>
                                <Input
                                    id="initial_payment_amount"
                                    v-model="form.initial_payment_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="min-h-10"
                                    placeholder="0.00"
                                />
                                <InputError
                                    :message="
                                        form.errors.initial_payment_amount
                                    "
                                />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="paid_at">Payment Date</Label>
                                <Input
                                    id="paid_at"
                                    v-model="form.paid_at"
                                    type="date"
                                    class="min-h-10"
                                />
                                <InputError :message="form.errors.paid_at" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="payment_method_id">
                                    Payment Method
                                </Label>
                                <select
                                    id="payment_method_id"
                                    v-model="form.payment_method_id"
                                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option
                                        v-for="m in paymentMethods"
                                        :key="m.id"
                                        :value="m.id"
                                    >
                                        {{ m.name }}
                                    </option>
                                </select>
                                <InputError
                                    :message="form.errors.payment_method_id"
                                />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="financial_account_id">
                                    Receiving Account
                                </Label>
                                <select
                                    id="financial_account_id"
                                    v-model="form.financial_account_id"
                                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option
                                        v-for="acc in financialAccounts"
                                        :key="acc.id"
                                        :value="acc.id"
                                    >
                                        {{ acc.name }}
                                    </option>
                                </select>
                                <InputError
                                    :message="form.errors.financial_account_id"
                                />
                            </div>

                            <div class="grid gap-1.5 sm:col-span-2">
                                <Label for="external_reference">
                                    Bank / POS Reference (Optional)
                                </Label>
                                <Input
                                    id="external_reference"
                                    v-model="form.external_reference"
                                    class="min-h-10"
                                    placeholder="Transfer session ID or receipt slip"
                                />
                            </div>
                        </div>
                    </FormSectionCard>

                    <FormSectionCard
                        title="Additional Sale Charges (Optional)"
                        description="Attach service, documentation, or processing charges that apply to this sale."
                    >
                        <div class="flex flex-col gap-3">
                            <div
                                v-for="(charge, idx) in form.charges"
                                :key="idx"
                                class="grid gap-3 rounded-lg border p-3 sm:grid-cols-12"
                            >
                                <div class="sm:col-span-4">
                                    <Label class="text-xs"
                                        >Charge Category</Label
                                    >
                                    <select
                                        v-model="charge.charge_category_id"
                                        class="mt-1 h-10 w-full rounded-md border border-input bg-background px-2.5 text-sm"
                                        @change="onChargeCategoryChange(charge)"
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
                                <div
                                    class="flex items-end justify-end sm:col-span-1"
                                >
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove charge"
                                        @click="removeChargeRow(idx)"
                                    >
                                        <Trash2
                                            class="size-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </div>

                            <div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="min-h-10"
                                    @click="addChargeRow"
                                >
                                    <Plus class="mr-1.5 size-4" />
                                    Add Charge
                                </Button>
                            </div>
                        </div>
                    </FormSectionCard>

                    <FormSectionCard title="Sale Notes">
                        <div class="grid gap-1.5">
                            <Label for="notes">Internal Notes</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Optional remarks about this sale..."
                            />
                        </div>
                    </FormSectionCard>
                </div>

                <div class="flex flex-col gap-5">
                    <FormSectionCard
                        title="Sale Summary"
                        description="Live breakdown before confirmation."
                    >
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">
                                    List Price
                                </dt>
                                <dd class="font-medium tabular-nums">
                                    {{ formatMoney(form.list_price || 0) }}
                                </dd>
                            </div>
                            <div
                                v-if="
                                    canApproveDiscount &&
                                    Number(form.discount_amount) > 0
                                "
                                class="flex justify-between text-destructive"
                            >
                                <dt>Approved Discount</dt>
                                <dd class="font-medium tabular-nums">
                                    -{{ formatMoney(form.discount_amount) }}
                                </dd>
                            </div>
                            <div
                                class="flex justify-between border-t pt-2.5 text-base font-semibold"
                            >
                                <dt>Final Sale Price</dt>
                                <dd class="tabular-nums">
                                    {{ formatMoney(finalPricePreview) }}
                                </dd>
                            </div>
                            <div
                                v-if="depositCredit > 0"
                                class="flex justify-between text-emerald-700 dark:text-emerald-400"
                            >
                                <dt>Reservation Deposit</dt>
                                <dd class="font-medium tabular-nums">
                                    {{ formatMoney(depositCredit) }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">
                                    Payment Now
                                </dt>
                                <dd class="font-medium tabular-nums">
                                    {{
                                        formatMoney(
                                            form.initial_payment_amount || 0,
                                        )
                                    }}
                                </dd>
                            </div>
                            <div
                                class="flex justify-between border-t pt-2.5 font-semibold"
                            >
                                <dt>Projected Balance Due</dt>
                                <dd
                                    class="tabular-nums"
                                    :class="
                                        balancePreview > 0
                                            ? 'text-amber-700 dark:text-amber-400'
                                            : 'text-emerald-700 dark:text-emerald-400'
                                    "
                                >
                                    {{ formatMoney(balancePreview) }}
                                </dd>
                            </div>
                        </dl>

                        <div
                            v-if="balancePreview > 0 && !form.is_installment"
                            class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200"
                        >
                            Full payment is required unless you check
                            <strong>Approve as an installment sale</strong>.
                        </div>

                        <div class="mt-5 flex flex-col gap-2">
                            <Button
                                type="submit"
                                class="min-h-11 w-full"
                                :disabled="form.processing"
                            >
                                <Spinner
                                    v-if="form.processing"
                                    class="mr-2 size-4"
                                />
                                Confirm Vehicle Sale
                            </Button>
                            <Button
                                as-child
                                variant="outline"
                                class="min-h-10 w-full"
                            >
                                <Link :href="index()">Cancel</Link>
                            </Button>
                        </div>
                    </FormSectionCard>
                </div>
            </div>
        </form>
    </div>
</template>
