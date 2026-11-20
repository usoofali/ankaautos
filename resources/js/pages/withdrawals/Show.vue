<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    CreditCard,
    FileDown,
    KeyRound,
    Lock,
    Plus,
} from '@lucide/vue';
import { ref } from 'vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatusBadge, {
    type StatusVariant,
} from '@/components/anka/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormatting } from '@/composables/useFormatting';
import { dashboard } from '@/routes';
import { complete, index, statementPdf } from '@/routes/withdrawals';
import { store as storeCharge } from '@/routes/withdrawals/charges';
import { store as storePayment } from '@/routes/withdrawals/payments';

const props = defineProps<{
    withdrawal: {
        id: number;
        reference_number: string;
        consignment_id: number;
        consignment_reference: string;
        vehicle_id: number;
        vehicle_reference: string;
        vehicle_title: string;
        owner_name: string;
        owner_phone: string | null;
        reason: string;
        total_owner_expenses: string;
        total_withdrawal_charges: string;
        total_due: string;
        amount_paid: string;
        balance_due: string;
        is_cleared_for_handover: boolean;
        status: string;
        status_label: string;
        status_variant: StatusVariant;
        requested_at: string;
        completed_at: string | null;
        owner_expenses: Array<{
            id: number;
            reference_number: string;
            description: string;
            amount: string;
            expense_date: string;
        }>;
        charges: Array<{
            id: number;
            label: string;
            amount: string;
            notes: string | null;
        }>;
        payments: Array<{
            id: number;
            reference_number: string;
            amount: string;
            paid_at: string;
            payment_method: string;
            financial_account: string;
        }>;
        handover: {
            reference_number: string;
            recipient_name: string;
            handed_over_at: string;
        } | null;
    };
    canAddCharge: boolean;
    canRecordPayment: boolean;
    canCompleteHandover: boolean;
    chargeCategories: Array<{
        id: number;
        name: string;
        default_amount: string | null;
    }>;
    paymentMethods: Array<{ id: number; name: string }>;
    financialAccounts: Array<{ id: number; name: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Withdrawals', href: index() },
            { title: 'Withdrawal Details', href: index() },
        ],
    },
});

const { formatMoney, formatDate, formatDateTime } = useFormatting();

const chargeOpen = ref(false);
const paymentOpen = ref(false);
const handoverOpen = ref(false);

const chargeForm = useForm({
    charge_category_id: '' as number | '',
    custom_label: '',
    amount: '',
    notes: '',
});

const paymentForm = useForm({
    amount: props.withdrawal.balance_due,
    payment_method_id: props.paymentMethods[0]?.id ?? 0,
    financial_account_id: props.financialAccounts[0]?.id ?? 0,
    paid_at: new Date().toISOString().slice(0, 10),
    external_reference: '',
    notes: '',
});

const handoverForm = useForm({
    recipient_name: props.withdrawal.owner_name,
    recipient_phone: props.withdrawal.owner_phone ?? '',
    recipient_id_number: '',
    odometer_reading: '',
    fuel_level: '',
    condition_summary: '',
    notes: '',
    items: [
        { item_name: 'Vehicle Keys Returned', is_included: true, remarks: '' },
        {
            item_name: 'Original Papers Returned',
            is_included: true,
            remarks: '',
        },
    ],
});

function onCategorySelect(): void {
    const cat = props.chargeCategories.find(
        (c) => c.id === Number(chargeForm.charge_category_id),
    );
    if (cat?.default_amount && !chargeForm.amount) {
        chargeForm.amount = cat.default_amount;
    }
}

function submitCharge(): void {
    chargeForm.post(storeCharge.url(props.withdrawal.id), {
        preserveScroll: true,
        onSuccess: () => {
            chargeOpen.value = false;
            chargeForm.reset();
        },
    });
}

function openPayment(): void {
    paymentForm.amount = props.withdrawal.balance_due;
    paymentOpen.value = true;
}

function submitPayment(): void {
    paymentForm.post(storePayment.url(props.withdrawal.id), {
        preserveScroll: true,
        onSuccess: () => {
            paymentOpen.value = false;
            paymentForm.reset('external_reference', 'notes');
        },
    });
}

function submitHandover(): void {
    handoverForm.post(complete.url(props.withdrawal.id), {
        preserveScroll: true,
        onSuccess: () => {
            handoverOpen.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Withdrawal ${withdrawal.reference_number}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="withdrawal.reference_number"
            :description="`${withdrawal.vehicle_title} · Owner: ${withdrawal.owner_name}`"
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Withdrawals
                    </Link>
                </Button>
                <Button as-child variant="outline" class="min-h-10">
                    <a :href="statementPdf.url(withdrawal.id)">
                        <FileDown class="mr-1.5 size-4" aria-hidden="true" />
                        Statement PDF
                    </a>
                </Button>
                <Button
                    v-if="canAddCharge"
                    variant="outline"
                    class="min-h-10"
                    @click="chargeOpen = true"
                >
                    <Plus class="mr-1.5 size-4" aria-hidden="true" />
                    Add Charge
                </Button>
                <Button
                    v-if="canRecordPayment"
                    class="min-h-10"
                    @click="openPayment"
                >
                    <CreditCard class="mr-1.5 size-4" aria-hidden="true" />
                    Record Payment
                </Button>
                <Button
                    v-if="canCompleteHandover"
                    variant="secondary"
                    class="min-h-10"
                    @click="handoverOpen = true"
                >
                    <KeyRound class="mr-1.5 size-4" aria-hidden="true" />
                    Complete Handover
                </Button>
            </template>
        </PageHeader>

        <!-- Strict Cash-First Banner -->
        <div
            v-if="withdrawal.status !== 'completed'"
            class="flex items-start gap-3 rounded-xl border p-4 text-sm"
            :class="
                withdrawal.is_cleared_for_handover
                    ? 'border-emerald-300 bg-emerald-50 text-emerald-950 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200'
                    : 'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200'
            "
        >
            <CheckCircle2
                v-if="withdrawal.is_cleared_for_handover"
                class="mt-0.5 size-5 shrink-0 text-emerald-600"
            />
            <Lock v-else class="mt-0.5 size-5 shrink-0 text-amber-600" />
            <div>
                <p class="font-semibold">
                    {{
                        withdrawal.is_cleared_for_handover
                            ? 'Cleared for Vehicle Release'
                            : 'Handover Locked — Strict Cash-First Clearance Required'
                    }}
                </p>
                <p class="mt-0.5 text-xs opacity-90">
                    {{
                        withdrawal.is_cleared_for_handover
                            ? 'All owner-deductible expenses and withdrawal charges have been paid in full. You may now complete physical handover.'
                            : `Remaining unpaid balance of ${formatMoney(withdrawal.balance_due)} must be collected in full (no waivers) before the vehicle can be released.`
                    }}
                </p>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="flex flex-col gap-5 lg:col-span-2">
                <FormSectionCard title="Withdrawal Summary">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <StatusBadge
                            :label="withdrawal.status_label"
                            :variant="withdrawal.status_variant"
                        />
                        <span class="font-mono text-xs text-muted-foreground">
                            Consignment {{ withdrawal.consignment_reference }}
                        </span>
                    </div>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Vehicle
                            </dt>
                            <dd class="font-medium">
                                {{ withdrawal.vehicle_title }} ({{
                                    withdrawal.vehicle_reference
                                }})
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">Owner</dt>
                            <dd class="font-medium">
                                {{ withdrawal.owner_name }}
                                <span
                                    v-if="withdrawal.owner_phone"
                                    class="text-muted-foreground"
                                >
                                    · {{ withdrawal.owner_phone }}
                                </span>
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-muted-foreground">
                                Reason
                            </dt>
                            <dd>{{ withdrawal.reason }}</dd>
                        </div>
                    </dl>
                </FormSectionCard>

                <FormSectionCard
                    title="Owner-Deductible Vehicle Expenses"
                    description="Unsettled expenses incurred on this consignment vehicle."
                >
                    <p
                        v-if="withdrawal.owner_expenses.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No unsettled owner-deductible expenses on this
                        consignment.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="e in withdrawal.owner_expenses"
                            :key="e.id"
                            class="flex items-center justify-between py-2.5"
                        >
                            <div>
                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ e.reference_number }}
                                </span>
                                <p class="font-medium">{{ e.description }}</p>
                            </div>
                            <span class="font-semibold tabular-nums">
                                {{ formatMoney(e.amount) }}
                            </span>
                        </li>
                    </ul>
                </FormSectionCard>

                <FormSectionCard title="Applied Withdrawal Charges">
                    <p
                        v-if="withdrawal.charges.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No withdrawal charges applied.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="c in withdrawal.charges"
                            :key="c.id"
                            class="flex items-center justify-between py-2.5"
                        >
                            <div>
                                <p class="font-medium">{{ c.label }}</p>
                                <p
                                    v-if="c.notes"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ c.notes }}
                                </p>
                            </div>
                            <span class="font-semibold tabular-nums">
                                {{ formatMoney(c.amount) }}
                            </span>
                        </li>
                    </ul>
                </FormSectionCard>

                <FormSectionCard title="Owner Payments Collected">
                    <p
                        v-if="withdrawal.payments.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No clearance payments recorded yet.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="p in withdrawal.payments"
                            :key="p.id"
                            class="flex items-center justify-between py-2.5"
                        >
                            <div>
                                <span class="font-mono text-xs font-semibold">
                                    {{ p.reference_number }}
                                </span>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(p.paid_at) }} ·
                                    {{ p.payment_method }} ·
                                    {{ p.financial_account }}
                                </p>
                            </div>
                            <span
                                class="font-semibold text-emerald-700 tabular-nums dark:text-emerald-400"
                            >
                                {{ formatMoney(p.amount) }}
                            </span>
                        </li>
                    </ul>
                </FormSectionCard>
            </div>

            <div class="flex flex-col gap-5">
                <FormSectionCard title="Clearance Totals">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">
                                Owner Expenses
                            </dt>
                            <dd class="tabular-nums">
                                {{
                                    formatMoney(withdrawal.total_owner_expenses)
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">
                                Withdrawal Charges
                            </dt>
                            <dd class="tabular-nums">
                                {{
                                    formatMoney(
                                        withdrawal.total_withdrawal_charges,
                                    )
                                }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between border-t pt-2.5 font-semibold"
                        >
                            <dt>Total Due</dt>
                            <dd class="tabular-nums">
                                {{ formatMoney(withdrawal.total_due) }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between text-emerald-700 dark:text-emerald-400"
                        >
                            <dt>Amount Paid</dt>
                            <dd class="font-semibold tabular-nums">
                                {{ formatMoney(withdrawal.amount_paid) }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between border-t pt-2.5 text-base font-bold"
                        >
                            <dt>Balance Due</dt>
                            <dd class="tabular-nums">
                                {{ formatMoney(withdrawal.balance_due) }}
                            </dd>
                        </div>
                    </dl>
                </FormSectionCard>

                <FormSectionCard
                    v-if="withdrawal.handover"
                    title="Release Handover"
                >
                    <p class="font-mono text-sm font-semibold">
                        {{ withdrawal.handover.reference_number }}
                    </p>
                    <p class="mt-1 text-sm">
                        Released to
                        <strong>{{
                            withdrawal.handover.recipient_name
                        }}</strong>
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ formatDateTime(withdrawal.handover.handed_over_at) }}
                    </p>
                </FormSectionCard>
            </div>
        </div>

        <!-- Add Charge Modal -->
        <Dialog v-model:open="chargeOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add Withdrawal Charge</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitCharge">
                    <div class="grid gap-1.5">
                        <Label for="ch_cat">Charge Category</Label>
                        <select
                            id="ch_cat"
                            v-model="chargeForm.charge_category_id"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            @change="onCategorySelect"
                        >
                            <option value="">Custom Charge</option>
                            <option
                                v-for="c in chargeCategories"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="ch_label">Custom Label</Label>
                        <Input
                            id="ch_label"
                            v-model="chargeForm.custom_label"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="ch_amount">Amount (₦)</Label>
                        <Input
                            id="ch_amount"
                            v-model="chargeForm.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                        />
                        <InputError :message="chargeForm.errors.amount" />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="chargeOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="chargeForm.processing">
                            <Spinner
                                v-if="chargeForm.processing"
                                class="mr-2 size-4"
                            />
                            Apply Charge
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Payment Modal -->
        <Dialog v-model:open="paymentOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Record Withdrawal Payment</DialogTitle>
                    <DialogDescription>
                        Balance due: {{ formatMoney(withdrawal.balance_due) }}
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitPayment">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="wp_amt">Amount (₦)</Label>
                            <Input
                                id="wp_amt"
                                v-model="paymentForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                            />
                            <InputError :message="paymentForm.errors.amount" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="wp_date">Date</Label>
                            <Input
                                id="wp_date"
                                v-model="paymentForm.paid_at"
                                type="date"
                                required
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="wp_method">Method</Label>
                            <select
                                id="wp_method"
                                v-model="paymentForm.payment_method_id"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                required
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
                        <div class="grid gap-1.5">
                            <Label for="wp_acc">Account</Label>
                            <select
                                id="wp_acc"
                                v-model="paymentForm.financial_account_id"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                required
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
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="paymentOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="paymentForm.processing"
                        >
                            <Spinner
                                v-if="paymentForm.processing"
                                class="mr-2 size-4"
                            />
                            Save Payment
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Handover Modal -->
        <Dialog v-model:open="handoverOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle
                        >Complete Vehicle Withdrawal Handover</DialogTitle
                    >
                    <DialogDescription>
                        Marks the consignment and vehicle as Withdrawn and locks
                        all settled owner expenses.
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitHandover">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="who_name">Recipient Name</Label>
                            <Input
                                id="who_name"
                                v-model="handoverForm.recipient_name"
                                required
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="who_phone">Recipient Phone</Label>
                            <Input
                                id="who_phone"
                                v-model="handoverForm.recipient_phone"
                                required
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="who_odo">Odometer (km)</Label>
                            <Input
                                id="who_odo"
                                v-model="handoverForm.odometer_reading"
                                type="number"
                                min="0"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="who_fuel">Fuel Level</Label>
                            <Input
                                id="who_fuel"
                                v-model="handoverForm.fuel_level"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="handoverOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="handoverForm.processing"
                        >
                            <Spinner
                                v-if="handoverForm.processing"
                                class="mr-2 size-4"
                            />
                            Complete Withdrawal
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
