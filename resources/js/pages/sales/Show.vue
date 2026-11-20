<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CreditCard,
    FileDown,
    Handshake,
    KeyRound,
    Plus,
    Trash2,
    XCircle,
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
import {
    agreementPdf,
    cancel,
    handoverPdf,
    index,
    receiptPdf,
} from '@/routes/sales';
import { store as storeHandover } from '@/routes/sales/handover';
import { store as storePayment } from '@/routes/sales/payments';
import { show as showSettlement } from '@/routes/settlements';
import { show as showVehicle } from '@/routes/vehicles';

const props = defineProps<{
    sale: {
        id: number;
        reference_number: string;
        vehicle_id: number;
        vehicle_reference: string;
        vehicle_title: string;
        ownership_label: string;
        customer_id: number;
        customer_name: string;
        customer_phone: string | null;
        reservation_reference: string | null;
        reservation_deposit: string | null;
        consignment_id: number | null;
        consignment_reference: string | null;
        list_price: string;
        discount_amount: string;
        final_price: string;
        amount_paid: string;
        balance_due: string;
        is_installment: boolean;
        installment_terms: string | null;
        is_fully_paid: boolean;
        status: string;
        status_label: string;
        status_variant: StatusVariant;
        confirmed_at: string;
        completed_at: string | null;
        cancelled_at: string | null;
        cancellation_reason: string | null;
        early_handover_reason: string | null;
        sales_officer_name: string;
        payments: Array<{
            id: number;
            reference_number: string;
            payment_type_label: string;
            amount: string;
            paid_at: string;
            payment_method: string;
            financial_account: string;
            external_reference: string | null;
            recorded_by: string;
        }>;
        handover: {
            id: number;
            reference_number: string;
            recipient_name: string;
            recipient_phone: string;
            odometer_reading: number | null;
            fuel_level: string | null;
            handed_over_at: string;
            officer_name: string;
            items: Array<{
                item_name: string;
                is_included: boolean;
                remarks: string | null;
            }>;
        } | null;
        owner_settlement: {
            id: number;
            reference_number: string;
            status_label: string;
            status_variant: StatusVariant;
            net_payable_to_owner: string;
            balance_due: string;
        } | null;
    };
    canRecordPayment: boolean;
    canRecordHandover: boolean;
    canApproveEarlyHandover: boolean;
    canCancelSale: boolean;
    paymentMethods: Array<{
        id: number;
        name: string;
        requires_reference: boolean;
    }>;
    financialAccounts: Array<{ id: number; name: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Sales', href: index() },
            { title: 'Sale Details', href: index() },
        ],
    },
});

const { formatMoney, formatDate, formatDateTime } = useFormatting();

function uuid(): string {
    if (
        typeof crypto !== 'undefined' &&
        typeof crypto.randomUUID === 'function'
    ) {
        return crypto.randomUUID();
    }
    return `pay-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

const paymentOpen = ref(false);
const handoverOpen = ref(false);
const cancelOpen = ref(false);

const paymentForm = useForm({
    amount: props.sale.balance_due,
    payment_method_id: props.paymentMethods[0]?.id ?? 0,
    financial_account_id: props.financialAccounts[0]?.id ?? 0,
    paid_at: new Date().toISOString().slice(0, 10),
    external_reference: '',
    notes: '',
    idempotency_key: uuid(),
});

const handoverForm = useForm({
    recipient_name: props.sale.customer_name,
    recipient_phone: props.sale.customer_phone ?? '',
    recipient_id_number: '',
    odometer_reading: '',
    fuel_level: 'Quarter Tank',
    condition_summary: '',
    notes: '',
    early_handover_reason: '',
    items: [
        { item_name: 'Primary & Spare Keys', is_included: true, remarks: '' },
        {
            item_name: 'Original Vehicle Papers / Customs Docs',
            is_included: true,
            remarks: '',
        },
        {
            item_name: 'Spare Tyre, Jack & Wheel Spanner',
            is_included: true,
            remarks: '',
        },
    ],
});

const cancelForm = useForm({
    cancellation_reason: '',
    payment_method_id: props.paymentMethods[0]?.id ?? 0,
    financial_account_id: props.financialAccounts[0]?.id ?? 0,
});

function openPaymentModal(): void {
    paymentForm.amount = props.sale.balance_due;
    paymentForm.idempotency_key = uuid();
    paymentOpen.value = true;
}

function submitPayment(): void {
    paymentForm.post(storePayment.url(props.sale.id), {
        preserveScroll: true,
        onSuccess: () => {
            paymentOpen.value = false;
            paymentForm.reset('external_reference', 'notes');
        },
    });
}

function addChecklistItem(): void {
    handoverForm.items.push({ item_name: '', is_included: true, remarks: '' });
}

function removeChecklistItem(idx: number): void {
    handoverForm.items.splice(idx, 1);
}

function submitHandover(): void {
    handoverForm.post(storeHandover.url(props.sale.id), {
        preserveScroll: true,
        onSuccess: () => {
            handoverOpen.value = false;
        },
    });
}

function submitCancel(): void {
    cancelForm.post(cancel.url(props.sale.id), {
        preserveScroll: true,
        onSuccess: () => {
            cancelOpen.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Sale ${sale.reference_number}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="sale.reference_number"
            :description="`${sale.vehicle_title} · Sold to ${sale.customer_name}`"
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Sales
                    </Link>
                </Button>
                <Button as-child variant="outline" class="min-h-10">
                    <a :href="agreementPdf.url(sale.id)">
                        <FileDown class="mr-1.5 size-4" aria-hidden="true" />
                        Sales Agreement PDF
                    </a>
                </Button>
                <Button
                    v-if="canRecordPayment"
                    class="min-h-10"
                    @click="openPaymentModal"
                >
                    <CreditCard class="mr-1.5 size-4" aria-hidden="true" />
                    Record Payment
                </Button>
                <Button
                    v-if="canRecordHandover"
                    variant="secondary"
                    class="min-h-10"
                    @click="handoverOpen = true"
                >
                    <KeyRound class="mr-1.5 size-4" aria-hidden="true" />
                    Record Handover
                </Button>
                <Button
                    v-if="canCancelSale"
                    variant="destructive"
                    class="min-h-10"
                    @click="cancelOpen = true"
                >
                    <XCircle class="mr-1.5 size-4" aria-hidden="true" />
                    Cancel Sale
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="flex flex-col gap-5 lg:col-span-2">
                <FormSectionCard title="Transaction Overview">
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <StatusBadge
                            :label="sale.status_label"
                            :variant="sale.status_variant"
                        />
                        <StatusBadge
                            :label="sale.ownership_label"
                            variant="neutral"
                        />
                        <StatusBadge
                            v-if="sale.is_installment"
                            label="Installment Plan"
                            variant="info"
                        />
                    </div>

                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Vehicle
                            </dt>
                            <dd class="mt-0.5 font-medium">
                                <Link
                                    :href="showVehicle(sale.vehicle_id)"
                                    class="text-primary hover:underline"
                                >
                                    {{ sale.vehicle_title }} ({{
                                        sale.vehicle_reference
                                    }})
                                </Link>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Customer
                            </dt>
                            <dd class="mt-0.5 font-medium">
                                {{ sale.customer_name }}
                                <span
                                    v-if="sale.customer_phone"
                                    class="text-muted-foreground"
                                >
                                    · {{ sale.customer_phone }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Confirmed Date
                            </dt>
                            <dd class="mt-0.5">
                                {{ formatDateTime(sale.confirmed_at) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Sales Officer
                            </dt>
                            <dd class="mt-0.5">
                                {{ sale.sales_officer_name }}
                            </dd>
                        </div>
                        <div v-if="sale.reservation_reference">
                            <dt class="text-xs text-muted-foreground">
                                Converted Reservation
                            </dt>
                            <dd class="mt-0.5 font-mono">
                                {{ sale.reservation_reference }} (Deposit
                                {{
                                    formatMoney(sale.reservation_deposit ?? 0)
                                }})
                            </dd>
                        </div>
                        <div
                            v-if="sale.installment_terms"
                            class="sm:col-span-2"
                        >
                            <dt class="text-xs text-muted-foreground">
                                Installment Terms
                            </dt>
                            <dd class="mt-0.5 whitespace-pre-line">
                                {{ sale.installment_terms }}
                            </dd>
                        </div>
                        <div
                            v-if="sale.cancellation_reason"
                            class="rounded-lg border border-destructive/30 bg-destructive/5 p-3 sm:col-span-2"
                        >
                            <dt class="text-xs font-semibold text-destructive">
                                Cancellation Reason
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ sale.cancellation_reason }}
                            </dd>
                        </div>
                    </dl>
                </FormSectionCard>

                <FormSectionCard
                    title="Payment History & Receipts"
                    description="All customer collections applied to this vehicle sale."
                >
                    <div
                        v-if="sale.payments.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        No payments recorded yet.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th class="pb-2 font-medium">Receipt</th>
                                    <th class="pb-2 font-medium">
                                        Type & Method
                                    </th>
                                    <th class="pb-2 font-medium">Date</th>
                                    <th class="pb-2 text-right font-medium">
                                        Amount
                                    </th>
                                    <th class="pb-2 text-right font-medium">
                                        PDF
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="p in sale.payments" :key="p.id">
                                    <td
                                        class="py-3 font-mono text-xs font-semibold"
                                    >
                                        {{ p.reference_number }}
                                    </td>
                                    <td class="py-3">
                                        <div class="font-medium">
                                            {{ p.payment_type_label }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ p.payment_method }} ·
                                            {{ p.financial_account }}
                                            <span v-if="p.external_reference">
                                                · Ref:
                                                {{ p.external_reference }}
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        class="py-3 text-xs text-muted-foreground"
                                    >
                                        {{ formatDate(p.paid_at) }}
                                    </td>
                                    <td
                                        class="py-3 text-right font-semibold tabular-nums"
                                    >
                                        {{ formatMoney(p.amount) }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <Button
                                            as-child
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <a
                                                :href="
                                                    receiptPdf.url({
                                                        sale: sale.id,
                                                        payment: p.id,
                                                    })
                                                "
                                            >
                                                <FileDown class="size-4" />
                                            </a>
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </FormSectionCard>

                <FormSectionCard
                    v-if="sale.handover"
                    title="Vehicle Handover Record"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2 pb-3"
                    >
                        <div class="font-mono text-sm font-semibold">
                            {{ sale.handover.reference_number }}
                        </div>
                        <Button as-child variant="outline" size="sm">
                            <a :href="handoverPdf.url(sale.id)">
                                <FileDown class="mr-1.5 size-4" />
                                Handover Certificate PDF
                            </a>
                        </Button>
                    </div>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Recipient
                            </dt>
                            <dd class="font-medium">
                                {{ sale.handover.recipient_name }} ({{
                                    sale.handover.recipient_phone
                                }})
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Handed Over On
                            </dt>
                            <dd>
                                {{
                                    formatDateTime(sale.handover.handed_over_at)
                                }}
                                by {{ sale.handover.officer_name }}
                            </dd>
                        </div>
                        <div
                            v-if="sale.early_handover_reason"
                            class="sm:col-span-2"
                        >
                            <dt
                                class="text-xs font-semibold text-amber-700 dark:text-amber-400"
                            >
                                Early Handover Exception Reason
                            </dt>
                            <dd>{{ sale.early_handover_reason }}</dd>
                        </div>
                    </dl>
                    <div
                        v-if="sale.handover.items.length > 0"
                        class="mt-4 border-t pt-3"
                    >
                        <p
                            class="mb-2 text-xs font-semibold text-muted-foreground uppercase"
                        >
                            Checklist Items
                        </p>
                        <ul class="space-y-1.5 text-sm">
                            <li
                                v-for="(item, idx) in sale.handover.items"
                                :key="idx"
                                class="flex items-center justify-between"
                            >
                                <span>{{ item.item_name }}</span>
                                <StatusBadge
                                    :label="
                                        item.is_included
                                            ? 'Included'
                                            : 'Missing'
                                    "
                                    :variant="
                                        item.is_included ? 'success' : 'warning'
                                    "
                                />
                            </li>
                        </ul>
                    </div>
                </FormSectionCard>
            </div>

            <div class="flex flex-col gap-5">
                <FormSectionCard title="Financial Summary">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">List Price</dt>
                            <dd class="tabular-nums">
                                {{ formatMoney(sale.list_price) }}
                            </dd>
                        </div>
                        <div
                            v-if="Number(sale.discount_amount) > 0"
                            class="flex justify-between text-destructive"
                        >
                            <dt>Approved Discount</dt>
                            <dd class="tabular-nums">
                                -{{ formatMoney(sale.discount_amount) }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between border-t pt-2.5 font-semibold"
                        >
                            <dt>Final Sale Price</dt>
                            <dd class="tabular-nums">
                                {{ formatMoney(sale.final_price) }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between text-emerald-700 dark:text-emerald-400"
                        >
                            <dt>Amount Paid</dt>
                            <dd class="font-semibold tabular-nums">
                                {{ formatMoney(sale.amount_paid) }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between border-t pt-2.5 text-base font-bold"
                        >
                            <dt>Balance Due</dt>
                            <dd
                                class="tabular-nums"
                                :class="
                                    Number(sale.balance_due) > 0
                                        ? 'text-amber-700 dark:text-amber-400'
                                        : 'text-emerald-700 dark:text-emerald-400'
                                "
                            >
                                {{ formatMoney(sale.balance_due) }}
                            </dd>
                        </div>
                    </dl>
                </FormSectionCard>

                <FormSectionCard
                    v-if="sale.owner_settlement"
                    title="Consignment Owner Settlement"
                    description="Owner payout unlocks automatically once customer payment reaches 100%."
                >
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-semibold">
                                {{ sale.owner_settlement.reference_number }}
                            </span>
                            <StatusBadge
                                :label="sale.owner_settlement.status_label"
                                :variant="sale.owner_settlement.status_variant"
                            />
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">
                                Net Payable to Owner
                            </span>
                            <span class="font-semibold tabular-nums">
                                {{
                                    formatMoney(
                                        sale.owner_settlement
                                            .net_payable_to_owner,
                                    )
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">
                                Unpaid Owner Balance
                            </span>
                            <span class="font-semibold tabular-nums">
                                {{
                                    formatMoney(
                                        sale.owner_settlement.balance_due,
                                    )
                                }}
                            </span>
                        </div>
                        <Button
                            as-child
                            variant="outline"
                            class="mt-2 min-h-10 w-full"
                        >
                            <Link
                                :href="showSettlement(sale.owner_settlement.id)"
                            >
                                <Handshake class="mr-1.5 size-4" />
                                Open Owner Settlement
                            </Link>
                        </Button>
                    </div>
                </FormSectionCard>
            </div>
        </div>

        <!-- Record Payment Modal -->
        <Dialog v-model:open="paymentOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Record Customer Payment</DialogTitle>
                    <DialogDescription>
                        Remaining sale balance:
                        <strong>{{ formatMoney(sale.balance_due) }}</strong>
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitPayment">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="pay_amount">Amount (₦)</Label>
                            <Input
                                id="pay_amount"
                                v-model="paymentForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                            />
                            <InputError :message="paymentForm.errors.amount" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="pay_date">Date</Label>
                            <Input
                                id="pay_date"
                                v-model="paymentForm.paid_at"
                                type="date"
                                required
                            />
                            <InputError :message="paymentForm.errors.paid_at" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="pay_method">Payment Method</Label>
                            <select
                                id="pay_method"
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
                            <Label for="pay_account">Financial Account</Label>
                            <select
                                id="pay_account"
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
                        <div class="grid gap-1.5 sm:col-span-2">
                            <Label for="pay_ref">Bank / POS Reference</Label>
                            <Input
                                id="pay_ref"
                                v-model="paymentForm.external_reference"
                                placeholder="Optional transaction reference"
                            />
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
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Record Physical Vehicle Handover</DialogTitle>
                    <DialogDescription>
                        Confirm buyer receipt details and handover checklist.
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitHandover">
                    <div
                        v-if="!sale.is_fully_paid"
                        class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200"
                    >
                        <strong
                            >Balance Unpaid ({{
                                formatMoney(sale.balance_due)
                            }}):</strong
                        >
                        <span v-if="canApproveEarlyHandover">
                            You have Manager authority to approve an early
                            release. Provide a mandatory justification below.
                        </span>
                        <span v-else>
                            Full payment is required before handover unless a
                            Manager approves an early-release exception.
                        </span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="ho_name">Recipient Name</Label>
                            <Input
                                id="ho_name"
                                v-model="handoverForm.recipient_name"
                                required
                            />
                            <InputError
                                :message="handoverForm.errors.recipient_name"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="ho_phone">Recipient Phone</Label>
                            <Input
                                id="ho_phone"
                                v-model="handoverForm.recipient_phone"
                                required
                            />
                            <InputError
                                :message="handoverForm.errors.recipient_phone"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="ho_odo">Odometer (km)</Label>
                            <Input
                                id="ho_odo"
                                v-model="handoverForm.odometer_reading"
                                type="number"
                                min="0"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="ho_fuel">Fuel Level</Label>
                            <Input
                                id="ho_fuel"
                                v-model="handoverForm.fuel_level"
                            />
                        </div>
                        <div
                            v-if="
                                !sale.is_fully_paid && canApproveEarlyHandover
                            "
                            class="grid gap-1.5 sm:col-span-2"
                        >
                            <Label for="early_reason">
                                Manager Early-Release Justification (Mandatory)
                            </Label>
                            <textarea
                                id="early_reason"
                                v-model="handoverForm.early_handover_reason"
                                rows="2"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required
                            />
                            <InputError
                                :message="
                                    handoverForm.errors.early_handover_reason
                                "
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label>Handover Checklist</Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addChecklistItem"
                            >
                                <Plus class="mr-1 size-3.5" /> Add Item
                            </Button>
                        </div>
                        <div
                            v-for="(item, idx) in handoverForm.items"
                            :key="idx"
                            class="flex items-center gap-2"
                        >
                            <input
                                v-model="item.is_included"
                                type="checkbox"
                                class="size-4 accent-primary"
                            />
                            <Input
                                v-model="item.item_name"
                                placeholder="Item description"
                                class="flex-1"
                                required
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                @click="removeChecklistItem(idx)"
                            >
                                <Trash2 class="size-4 text-destructive" />
                            </Button>
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
                            :disabled="
                                handoverForm.processing ||
                                (!sale.is_fully_paid &&
                                    !canApproveEarlyHandover)
                            "
                        >
                            <Spinner
                                v-if="handoverForm.processing"
                                class="mr-2 size-4"
                            />
                            Confirm Handover
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Cancel Sale Modal -->
        <Dialog v-model:open="cancelOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Cancel Vehicle Sale</DialogTitle>
                    <DialogDescription>
                        Cancelling logs a customer refund of
                        <strong>{{ formatMoney(sale.amount_paid) }}</strong
                        >, returns the vehicle to Available, and voids any
                        unpaid owner settlement. Blocked if the consignment
                        owner has already been paid.
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitCancel">
                    <div class="grid gap-1.5">
                        <Label for="cancel_reason">
                            Cancellation Reason (Mandatory)
                        </Label>
                        <textarea
                            id="cancel_reason"
                            v-model="cancelForm.cancellation_reason"
                            rows="3"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            required
                        />
                        <InputError
                            :message="cancelForm.errors.cancellation_reason"
                        />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="refund_method">Refund Method</Label>
                            <select
                                id="refund_method"
                                v-model="cancelForm.payment_method_id"
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
                            <Label for="refund_account">Refund Account</Label>
                            <select
                                id="refund_account"
                                v-model="cancelForm.financial_account_id"
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
                            @click="cancelOpen = false"
                        >
                            Keep Sale
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="cancelForm.processing"
                        >
                            <Spinner
                                v-if="cancelForm.processing"
                                class="mr-2 size-4"
                            />
                            Cancel Sale & Log Refund
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
