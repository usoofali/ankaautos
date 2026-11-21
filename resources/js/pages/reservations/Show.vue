<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, BadgeDollarSign, XCircle } from '@lucide/vue';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormatting } from '@/composables/useFormatting';
import { dashboard } from '@/routes';
import { cancel, index } from '@/routes/reservations';
import { create as createSale, show as showSale } from '@/routes/sales';

const props = defineProps<{
    reservation: {
        id: number;
        reference_number: string;
        vehicle_title: string;
        vehicle_reference: string;
        customer_name: string;
        customer_phone: string | null;
        agreed_price: string;
        deposit_amount: string;
        status_label: string;
        status_variant: StatusVariant;
        reserved_at: string;
        notes: string | null;
        cancellation_reason: string | null;
        deposit_disposition: string | null;
        created_by_name: string;
        converted_sale_id: number | null;
        converted_sale_reference: string | null;
        payments: Array<{
            id: number;
            reference_number: string;
            amount: string;
            paid_at: string;
            payment_method: string;
            financial_account: string;
        }>;
    };
    canConvert: boolean;
    canCancel: boolean;
    paymentMethods: Array<{ id: number; name: string }>;
    financialAccounts: Array<{ id: number; name: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Reservations', href: index() },
            { title: 'Reservation Details', href: index() },
        ],
    },
});

const { formatMoney, formatDate } = useFormatting();
const cancelOpen = ref(false);

const cancelForm = useForm({
    deposit_disposition: 'refunded',
    cancellation_reason: '',
    payment_method_id: props.paymentMethods[0]?.id ?? 0,
    financial_account_id: props.financialAccounts[0]?.id ?? 0,
});

function submitCancel(): void {
    cancelForm.post(cancel.url(props.reservation.id), {
        preserveScroll: true,
        onSuccess: () => (cancelOpen.value = false),
    });
}
</script>

<template>
    <Head :title="`Reservation ${reservation.reference_number}`" />

    <div class="flex min-w-0 flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="reservation.reference_number"
            :description="`${reservation.vehicle_title} · Reserved by ${reservation.customer_name}`"
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
                            Reservations
                        </Link>
                    </Button>
                    <Button v-if="canConvert" as-child class="min-h-10">
                        <Link
                            :href="
                                createSale({
                                    query: { reservation_id: reservation.id },
                                })
                            "
                        >
                            <BadgeDollarSign
                                class="mr-1.5 size-4 shrink-0"
                                aria-hidden="true"
                            />
                            Convert to Sale
                        </Link>
                    </Button>
                    <Button
                        v-if="canCancel"
                        type="button"
                        variant="destructive"
                        class="col-span-2 min-h-10 sm:col-span-1"
                        @click="cancelOpen = true"
                    >
                        <XCircle
                            class="mr-1.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        Cancel Reservation
                    </Button>
                </div>
            </template>
        </PageHeader>

        <FormSectionCard title="Reservation Overview">
            <template #headerAction>
                <StatusBadge
                    :label="reservation.status_label"
                    :variant="reservation.status_variant"
                />
            </template>

            <dl class="grid gap-3 text-sm sm:grid-cols-3">
                <div class="rounded-lg bg-muted/40 p-3">
                    <dt class="text-xs text-muted-foreground">Agreed Price</dt>
                    <dd class="text-lg font-bold tabular-nums">
                        {{ formatMoney(reservation.agreed_price) }}
                    </dd>
                </div>
                <div class="rounded-lg bg-muted/40 p-3">
                    <dt class="text-xs text-muted-foreground">Deposit Paid</dt>
                    <dd
                        class="text-lg font-semibold text-emerald-700 tabular-nums dark:text-emerald-300"
                    >
                        {{ formatMoney(reservation.deposit_amount) }}
                    </dd>
                </div>
                <div class="rounded-lg bg-muted/40 p-3">
                    <dt class="text-xs text-muted-foreground">Reserved Date</dt>
                    <dd class="font-medium tabular-nums">
                        {{ formatDate(reservation.reserved_at) }}
                    </dd>
                </div>
            </dl>

            <div
                v-if="reservation.converted_sale_id"
                class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200"
            >
                Converted to Sale:
                <Link
                    :href="showSale(reservation.converted_sale_id)"
                    class="font-mono font-semibold underline"
                >
                    {{ reservation.converted_sale_reference }}
                </Link>
            </div>
        </FormSectionCard>

        <Dialog :open="cancelOpen" @update:open="cancelOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Cancel Reservation</DialogTitle>
                    <DialogDescription>
                        Specify whether the
                        {{ formatMoney(reservation.deposit_amount) }} deposit is
                        refunded or forfeited. The vehicle will return to
                        Available.
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitCancel">
                    <div class="space-y-1.5">
                        <Label for="disp">Deposit Disposition *</Label>
                        <select
                            id="disp"
                            v-model="cancelForm.deposit_disposition"
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option value="refunded">
                                Refund Deposit to Customer
                            </option>
                            <option value="forfeited">
                                Forfeit Deposit (Dealership Income)
                            </option>
                        </select>
                    </div>
                    <template
                        v-if="cancelForm.deposit_disposition === 'refunded'"
                    >
                        <div class="space-y-1.5">
                            <Label for="ref_method">Refund Method *</Label>
                            <select
                                id="ref_method"
                                v-model.number="cancelForm.payment_method_id"
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
                            <Label for="ref_acc">Refund From Account *</Label>
                            <select
                                id="ref_acc"
                                v-model.number="cancelForm.financial_account_id"
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
                    </template>
                    <div class="space-y-1.5">
                        <Label for="cancel_reason">Cancellation Reason *</Label>
                        <textarea
                            id="cancel_reason"
                            v-model="cancelForm.cancellation_reason"
                            rows="2"
                            required
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                        <InputError
                            :message="cancelForm.errors.cancellation_reason"
                        />
                    </div>
                    <DialogFooter class="flex-col-reverse gap-2 sm:flex-row">
                        <Button
                            type="button"
                            variant="outline"
                            class="min-h-10"
                            @click="cancelOpen = false"
                        >
                            Keep Active
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            class="min-h-10"
                            :disabled="cancelForm.processing"
                        >
                            <Spinner
                                v-if="cancelForm.processing"
                                class="mr-1.5"
                            />
                            Confirm Cancellation
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
