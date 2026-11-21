<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, FileDown, Lock, Send } from '@lucide/vue';
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
import { show as showSale } from '@/routes/sales';
import { index, statementPdf } from '@/routes/settlements';
import { store as storePayout } from '@/routes/settlements/payouts';

const props = defineProps<{
    settlement: {
        id: number;
        reference_number: string;
        consignment_id: number;
        consignment_reference: string;
        sale_id: number;
        sale_reference: string;
        customer_amount_paid: string;
        customer_balance_due: string;
        customer_fully_paid: boolean;
        vehicle_title: string;
        vehicle_reference: string;
        owner_name: string;
        owner_phone: string | null;
        owner_bank_name: string | null;
        owner_bank_account_name: string | null;
        owner_bank_account_number: string | null;
        gross_sale_price: string;
        commission_amount: string;
        total_expense_deductions: string;
        total_charge_deductions: string;
        net_payable_to_owner: string;
        amount_paid: string;
        balance_due: string;
        status: string;
        status_label: string;
        status_variant: StatusVariant;
        settled_at: string | null;
        items: Array<{
            id: number;
            item_type: string;
            type_label: string;
            label: string;
            amount: string;
        }>;
        payments: Array<{
            id: number;
            reference_number: string;
            amount: string;
            paid_at: string;
            payment_method: string;
            financial_account: string;
            payment_reference: string | null;
            recorded_by: string;
        }>;
    };
    canDisburse: boolean;
    paymentMethods: Array<{ id: number; name: string }>;
    financialAccounts: Array<{ id: number; name: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Owner Settlements', href: index() },
            { title: 'Settlement Details', href: index() },
        ],
    },
});

const { formatMoney, formatDate } = useFormatting();
const payoutOpen = ref(false);

function uuid(): string {
    if (
        typeof crypto !== 'undefined' &&
        typeof crypto.randomUUID === 'function'
    ) {
        return crypto.randomUUID();
    }
    return `payout-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

const payoutForm = useForm({
    amount: props.settlement.balance_due,
    payment_method_id: props.paymentMethods[0]?.id ?? 0,
    financial_account_id: props.financialAccounts[0]?.id ?? 0,
    paid_at: new Date().toISOString().slice(0, 10),
    payment_reference: '',
    notes: '',
    idempotency_key: uuid(),
});

function openPayout(): void {
    payoutForm.amount = props.settlement.balance_due;
    payoutForm.idempotency_key = uuid();
    payoutOpen.value = true;
}

function submitPayout(): void {
    payoutForm.post(storePayout.url(props.settlement.id), {
        preserveScroll: true,
        onSuccess: () => {
            payoutOpen.value = false;
            payoutForm.reset('payment_reference', 'notes');
        },
    });
}
</script>

<template>
    <Head :title="`Settlement ${settlement.reference_number}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="settlement.reference_number"
            :description="`${settlement.vehicle_title} · Owner: ${settlement.owner_name}`"
        >
            <template #actions>
                <div
                    class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:flex-wrap"
                >
                    <Button as-child variant="outline" class="min-h-10">
                        <Link :href="index()">
                            <ArrowLeft
                                class="mr-1.5 size-4 shrink-0"
                                aria-hidden="true"
                            />
                            Settlements
                        </Link>
                    </Button>
                    <Button as-child variant="outline" class="min-h-10">
                        <a :href="statementPdf.url(settlement.id)">
                            <FileDown
                                class="mr-1.5 size-4 shrink-0"
                                aria-hidden="true"
                            />
                            Statement PDF
                        </a>
                    </Button>
                    <Button
                        v-if="canDisburse"
                        class="col-span-2 min-h-10 sm:col-span-1"
                        @click="openPayout"
                    >
                        <Send
                            class="mr-1.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        Record Owner Payout
                    </Button>
                </div>
            </template>
        </PageHeader>

        <!-- Strict Cash-First Lock Banner -->
        <div
            v-if="
                settlement.status !== 'voided' &&
                settlement.status !== 'settled'
            "
            class="flex items-start gap-3 rounded-xl border p-4 text-sm"
            :class="
                settlement.customer_fully_paid
                    ? 'border-emerald-300 bg-emerald-50 text-emerald-950 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200'
                    : 'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200'
            "
        >
            <CheckCircle2
                v-if="settlement.customer_fully_paid"
                class="mt-0.5 size-5 shrink-0 text-emerald-600"
            />
            <Lock v-else class="mt-0.5 size-5 shrink-0 text-amber-600" />
            <div>
                <p class="font-semibold">
                    {{
                        settlement.customer_fully_paid
                            ? 'Customer Payment 100% Received — Ready for Owner Disbursement'
                            : 'Owner Payout Locked — Awaiting Full Customer Payment'
                    }}
                </p>
                <p class="mt-0.5 text-xs opacity-90">
                    {{
                        settlement.customer_fully_paid
                            ? 'All customer proceeds have been collected. Full or partial disbursements to the consignment owner are enabled.'
                            : `Customer still owes ${formatMoney(settlement.customer_balance_due)} on sale ${settlement.sale_reference}. Disbursements are strictly locked until customer balance reaches ₦0.00.`
                    }}
                </p>
            </div>
        </div>

        <div class="grid min-w-0 gap-5 lg:grid-cols-3">
            <div class="flex min-w-0 flex-col gap-5 lg:col-span-2">
                <FormSectionCard
                    title="Itemized Settlement Breakdown"
                    description="Exact gross proceeds, commission, expense deductions, and service charges."
                >
                    <!-- Mobile Stacked Breakdown (< md) -->
                    <ul class="divide-y md:hidden">
                        <li
                            v-for="item in settlement.items"
                            :key="item.id"
                            class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0">
                                <p
                                    class="text-[11px] font-medium text-muted-foreground uppercase"
                                >
                                    {{ item.type_label }}
                                </p>
                                <p
                                    class="mt-0.5 text-sm font-medium break-words"
                                >
                                    {{ item.label }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 text-sm font-semibold whitespace-nowrap tabular-nums"
                                :class="
                                    item.item_type === 'gross_sale'
                                        ? 'text-foreground'
                                        : 'text-destructive'
                                "
                            >
                                {{
                                    item.item_type === 'gross_sale'
                                        ? formatMoney(item.amount)
                                        : `-${formatMoney(item.amount)}`
                                }}
                            </span>
                        </li>
                    </ul>

                    <!-- Desktop Table (md+) -->
                    <div
                        class="hidden overflow-x-auto md:block"
                        role="region"
                        tabindex="0"
                        aria-label="Itemized settlement breakdown"
                    >
                        <table class="w-full min-w-[460px] text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th
                                        scope="col"
                                        class="pr-3 pb-2.5 font-medium whitespace-nowrap"
                                    >
                                        Type
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-3 pb-2.5 font-medium"
                                    >
                                        Description
                                    </th>
                                    <th
                                        scope="col"
                                        class="pb-2.5 pl-3 text-right font-medium whitespace-nowrap"
                                    >
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="item in settlement.items"
                                    :key="item.id"
                                >
                                    <td
                                        class="py-3 pr-3 text-xs whitespace-nowrap text-muted-foreground"
                                    >
                                        {{ item.type_label }}
                                    </td>
                                    <td class="px-3 py-3 font-medium">
                                        {{ item.label }}
                                    </td>
                                    <td
                                        class="py-3 pl-3 text-right font-semibold whitespace-nowrap tabular-nums"
                                        :class="
                                            item.item_type === 'gross_sale'
                                                ? 'text-foreground'
                                                : 'text-destructive'
                                        "
                                    >
                                        {{
                                            item.item_type === 'gross_sale'
                                                ? formatMoney(item.amount)
                                                : `-${formatMoney(item.amount)}`
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </FormSectionCard>

                <FormSectionCard
                    title="Owner Disbursement History"
                    description="All full or partial payouts made to the vehicle owner."
                >
                    <p
                        v-if="settlement.payments.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No owner payouts disbursed yet.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="p in settlement.payments"
                            :key="p.id"
                            class="flex items-start justify-between gap-3 py-2.5"
                        >
                            <div class="min-w-0">
                                <span class="font-mono text-xs font-semibold">
                                    {{ p.reference_number }}
                                </span>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(p.paid_at) }} ·
                                    {{ p.payment_method }} ·
                                    {{ p.financial_account }}
                                    <span v-if="p.payment_reference">
                                        · Ref: {{ p.payment_reference }}
                                    </span>
                                </p>
                            </div>
                            <span
                                class="shrink-0 font-semibold whitespace-nowrap text-emerald-700 tabular-nums dark:text-emerald-400"
                            >
                                {{ formatMoney(p.amount) }}
                            </span>
                        </li>
                    </ul>
                </FormSectionCard>
            </div>

            <div class="flex min-w-0 flex-col gap-5">
                <FormSectionCard title="Settlement Totals">
                    <div
                        class="mb-3 flex flex-wrap items-center justify-between gap-2"
                    >
                        <StatusBadge
                            :label="settlement.status_label"
                            :variant="settlement.status_variant"
                        />
                        <Link
                            :href="showSale(settlement.sale_id)"
                            class="font-mono text-xs text-primary hover:underline"
                        >
                            Sale {{ settlement.sale_reference }}
                        </Link>
                    </div>
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-muted-foreground">Gross Sale</dt>
                            <dd class="shrink-0 tabular-nums">
                                {{ formatMoney(settlement.gross_sale_price) }}
                            </dd>
                        </div>
                        <div
                            class="flex items-baseline justify-between gap-3 text-destructive"
                        >
                            <dt>Dealership Commission</dt>
                            <dd class="shrink-0 tabular-nums">
                                -{{ formatMoney(settlement.commission_amount) }}
                            </dd>
                        </div>
                        <div
                            class="flex items-baseline justify-between gap-3 text-destructive"
                        >
                            <dt>Owner Expense Deductions</dt>
                            <dd class="shrink-0 tabular-nums">
                                -{{
                                    formatMoney(
                                        settlement.total_expense_deductions,
                                    )
                                }}
                            </dd>
                        </div>
                        <div
                            v-if="
                                Number(settlement.total_charge_deductions) > 0
                            "
                            class="flex items-baseline justify-between gap-3 text-destructive"
                        >
                            <dt>Service Charges</dt>
                            <dd class="shrink-0 tabular-nums">
                                -{{
                                    formatMoney(
                                        settlement.total_charge_deductions,
                                    )
                                }}
                            </dd>
                        </div>
                        <div
                            class="flex items-baseline justify-between gap-3 border-t pt-2.5 font-semibold"
                        >
                            <dt>Net Payable to Owner</dt>
                            <dd class="shrink-0 tabular-nums">
                                {{
                                    formatMoney(settlement.net_payable_to_owner)
                                }}
                            </dd>
                        </div>
                        <div
                            class="flex items-baseline justify-between gap-3 text-emerald-700 dark:text-emerald-400"
                        >
                            <dt>Total Disbursed</dt>
                            <dd class="shrink-0 font-semibold tabular-nums">
                                {{ formatMoney(settlement.amount_paid) }}
                            </dd>
                        </div>
                        <div
                            class="flex items-baseline justify-between gap-3 border-t pt-2.5 text-base font-bold"
                        >
                            <dt>Remaining Balance</dt>
                            <dd class="shrink-0 tabular-nums">
                                {{ formatMoney(settlement.balance_due) }}
                            </dd>
                        </div>
                    </dl>
                </FormSectionCard>

                <FormSectionCard
                    title="Owner Bank Details"
                    description="Beneficiary account on file."
                >
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-xs text-muted-foreground">Owner</dt>
                            <dd class="font-medium">
                                {{ settlement.owner_name }}
                            </dd>
                        </div>
                        <div v-if="settlement.owner_bank_name">
                            <dt class="text-xs text-muted-foreground">Bank</dt>
                            <dd>{{ settlement.owner_bank_name }}</dd>
                        </div>
                        <div v-if="settlement.owner_bank_account_name">
                            <dt class="text-xs text-muted-foreground">
                                Account Name
                            </dt>
                            <dd>{{ settlement.owner_bank_account_name }}</dd>
                        </div>
                        <div v-if="settlement.owner_bank_account_number">
                            <dt class="text-xs text-muted-foreground">
                                Account Number
                            </dt>
                            <dd class="font-mono font-semibold">
                                {{ settlement.owner_bank_account_number }}
                            </dd>
                        </div>
                        <p
                            v-if="!settlement.owner_bank_account_number"
                            class="text-xs text-muted-foreground"
                        >
                            No bank account recorded on owner profile.
                        </p>
                    </dl>
                </FormSectionCard>
            </div>
        </div>

        <!-- Record Owner Payout Modal -->
        <Dialog v-model:open="payoutOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Disburse Owner Payout</DialogTitle>
                    <DialogDescription>
                        Remaining payable balance:
                        <strong>{{
                            formatMoney(settlement.balance_due)
                        }}</strong
                        >. Supports partial or full disbursement.
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitPayout">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="po_amount">Payout Amount (₦)</Label>
                            <Input
                                id="po_amount"
                                v-model="payoutForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                            />
                            <InputError :message="payoutForm.errors.amount" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="po_date">Date</Label>
                            <Input
                                id="po_date"
                                v-model="payoutForm.paid_at"
                                type="date"
                                required
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="po_method">Payment Method</Label>
                            <select
                                id="po_method"
                                v-model="payoutForm.payment_method_id"
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
                            <Label for="po_acc">Source Account</Label>
                            <select
                                id="po_acc"
                                v-model="payoutForm.financial_account_id"
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
                            <Label for="po_ref"
                                >Transfer / Cheque Reference</Label
                            >
                            <Input
                                id="po_ref"
                                v-model="payoutForm.payment_reference"
                                placeholder="Bank transfer reference"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="payoutOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="payoutForm.processing">
                            <Spinner
                                v-if="payoutForm.processing"
                                class="mr-2 size-4"
                            />
                            Disburse Payout
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
