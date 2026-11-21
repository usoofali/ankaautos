<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, Download } from '@lucide/vue';
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
import { show as showConsignment } from '@/routes/consignments';
import { index, receipt } from '@/routes/expenses';
import { store as storePayment } from '@/routes/expenses/payments';
import { show as showVehicle } from '@/routes/vehicles';

const props = defineProps<{
    expense: {
        id: number;
        reference_number: string;
        vehicle_id: number;
        vehicle_reference: string;
        vehicle_title: string;
        consignment_id: number | null;
        consignment_reference: string | null;
        category_name: string;
        borne_by: string;
        borne_by_label: string;
        borne_by_variant: StatusVariant;
        description: string;
        amount: string;
        amount_paid: string;
        balance_due: string;
        payment_status: string;
        payment_status_label: string;
        payment_status_variant: StatusVariant;
        expense_date: string;
        vendor_name: string | null;
        has_receipt: boolean;
        receipt_original_name: string | null;
        is_deducted: boolean;
        recorded_by_name: string;
        payments: Array<{
            id: number;
            reference_number: string;
            amount: string;
            paid_at: string;
            payment_method: string;
            financial_account: string;
            payment_reference: string | null;
            notes: string | null;
            recorded_by: string;
        }>;
    };
    canRecordPayment: boolean;
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
            { title: 'Vehicle Expenses', href: index() },
            { title: 'Expense Details', href: index() },
        ],
    },
});

const { formatMoney, formatDate } = useFormatting();
const paymentModalOpen = ref(false);

const paymentForm = useForm({
    amount: props.expense.balance_due,
    payment_method_id: props.paymentMethods[0]?.id ?? 0,
    financial_account_id: props.financialAccounts[0]?.id ?? 0,
    paid_at: new Date().toISOString().slice(0, 10),
    payment_reference: '',
    notes: '',
});

function submitPayment(): void {
    paymentForm.post(storePayment.url(props.expense.id), {
        preserveScroll: true,
        onSuccess: () => {
            paymentModalOpen.value = false;
            paymentForm.reset('payment_reference', 'notes');
        },
    });
}
</script>

<template>
    <Head :title="`Expense ${expense.reference_number}`" />

    <div class="flex min-w-0 flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="expense.reference_number"
            :description="`${expense.category_name} · Recorded ${formatDate(expense.expense_date)} by ${expense.recorded_by_name}`"
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
                            Expenses
                        </Link>
                    </Button>
                    <Button
                        v-if="expense.has_receipt"
                        as-child
                        variant="outline"
                        class="min-h-10"
                    >
                        <a :href="receipt.url(expense.id)">
                            <Download
                                class="mr-1.5 size-4 shrink-0"
                                aria-hidden="true"
                            />
                            Receipt
                        </a>
                    </Button>
                    <Button
                        v-if="canRecordPayment"
                        type="button"
                        class="col-span-2 min-h-10 sm:col-span-1"
                        @click="paymentModalOpen = true"
                    >
                        <CreditCard
                            class="mr-1.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        Record Payment
                    </Button>
                </div>
            </template>
        </PageHeader>

        <div class="grid min-w-0 gap-5 lg:grid-cols-3">
            <div class="min-w-0 space-y-5 lg:col-span-2">
                <FormSectionCard title="Expense Summary">
                    <template #headerAction>
                        <div class="flex flex-wrap gap-1.5">
                            <StatusBadge
                                :label="expense.borne_by_label"
                                :variant="expense.borne_by_variant"
                            />
                            <StatusBadge
                                :label="expense.payment_status_label"
                                :variant="expense.payment_status_variant"
                            />
                        </div>
                    </template>

                    <dl class="grid gap-3 text-sm sm:grid-cols-3">
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Total Expense
                            </dt>
                            <dd class="text-lg font-bold tabular-nums">
                                {{ formatMoney(expense.amount) }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Amount Paid
                            </dt>
                            <dd
                                class="text-lg font-semibold text-emerald-700 tabular-nums dark:text-emerald-300"
                            >
                                {{ formatMoney(expense.amount_paid) }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Balance Due
                            </dt>
                            <dd
                                class="text-lg font-semibold text-destructive tabular-nums"
                            >
                                {{ formatMoney(expense.balance_due) }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4 space-y-2 text-sm">
                        <p class="font-medium text-foreground">
                            {{ expense.description }}
                        </p>
                        <p
                            v-if="expense.vendor_name"
                            class="text-xs text-muted-foreground"
                        >
                            Vendor / Workshop: {{ expense.vendor_name }}
                        </p>
                        <p
                            v-if="expense.is_deducted"
                            class="rounded-md bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300"
                        >
                            This owner-deductible expense has already been
                            deducted in an owner settlement or withdrawal and
                            will not be double-charged.
                        </p>
                    </div>
                </FormSectionCard>

                <FormSectionCard
                    :title="`Payment History (${expense.payments.length})`"
                >
                    <div
                        v-if="expense.payments.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        No payments recorded against this expense yet.
                    </div>
                    <ol v-else class="divide-y divide-border/60">
                        <li
                            v-for="p in expense.payments"
                            :key="p.id"
                            class="flex flex-col gap-1 py-3 text-sm sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="font-mono text-xs font-semibold">
                                    {{ p.reference_number }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ p.payment_method }} ·
                                    {{ p.financial_account }}
                                    <span v-if="p.payment_reference">
                                        · Ref: {{ p.payment_reference }}
                                    </span>
                                </p>
                            </div>
                            <div class="sm:text-right">
                                <p class="font-semibold tabular-nums">
                                    {{ formatMoney(p.amount) }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(p.paid_at) }} ·
                                    {{ p.recorded_by }}
                                </p>
                            </div>
                        </li>
                    </ol>
                </FormSectionCard>
            </div>

            <FormSectionCard title="Linked Vehicle & Consignment">
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-xs text-muted-foreground">Vehicle</p>
                        <Link
                            :href="showVehicle(expense.vehicle_id)"
                            class="font-semibold text-foreground hover:underline"
                        >
                            {{ expense.vehicle_title }}
                        </Link>
                        <p class="font-mono text-xs text-muted-foreground">
                            {{ expense.vehicle_reference }}
                        </p>
                    </div>
                    <div v-if="expense.consignment_id">
                        <p class="text-xs text-muted-foreground">Consignment</p>
                        <Link
                            :href="showConsignment(expense.consignment_id)"
                            class="font-mono text-xs font-semibold text-foreground hover:underline"
                        >
                            {{ expense.consignment_reference }}
                        </Link>
                    </div>
                </div>
            </FormSectionCard>
        </div>

        <Dialog
            :open="paymentModalOpen"
            @update:open="paymentModalOpen = $event"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Record Expense Payment</DialogTitle>
                    <DialogDescription>
                        Remaining balance:
                        {{ formatMoney(expense.balance_due) }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitPayment">
                    <div class="space-y-1.5">
                        <Label for="pay_amount">Amount (₦) *</Label>
                        <Input
                            id="pay_amount"
                            v-model="paymentForm.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            class="min-h-10 tabular-nums"
                        />
                        <InputError :message="paymentForm.errors.amount" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="pay_date">Payment Date *</Label>
                        <Input
                            id="pay_date"
                            v-model="paymentForm.paid_at"
                            type="date"
                            required
                            class="min-h-10"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="pay_method">Payment Method *</Label>
                        <select
                            id="pay_method"
                            v-model.number="paymentForm.payment_method_id"
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
                    <div class="space-y-1.5">
                        <Label for="pay_account">Financial Account *</Label>
                        <select
                            id="pay_account"
                            v-model.number="paymentForm.financial_account_id"
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
                    <div class="space-y-1.5">
                        <Label for="pay_ref">External Reference</Label>
                        <Input
                            id="pay_ref"
                            v-model="paymentForm.payment_reference"
                            class="min-h-10"
                        />
                    </div>
                    <DialogFooter class="flex-col-reverse gap-2 sm:flex-row">
                        <Button
                            type="button"
                            variant="outline"
                            class="min-h-10"
                            @click="paymentModalOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            class="min-h-10"
                            :disabled="paymentForm.processing"
                        >
                            <Spinner
                                v-if="paymentForm.processing"
                                class="mr-1.5"
                            />
                            Save Payment
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
