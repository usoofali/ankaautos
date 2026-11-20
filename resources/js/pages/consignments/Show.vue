<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Download, FileEdit, History, Undo2 } from '@lucide/vue';
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
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { agreementPdf, amendTerms, index } from '@/routes/consignments';
import { show as showOwner } from '@/routes/owners';
import { show as showVehicle } from '@/routes/vehicles';
import { create as createWithdrawal } from '@/routes/withdrawals';

const props = defineProps<{
    consignment: {
        id: number;
        reference_number: string;
        status: string;
        status_label: string;
        status_variant: StatusVariant;
        received_at: string;
        expected_review_date: string | null;
        agreed_selling_price: string;
        minimum_acceptable_price: string | null;
        commission_type: string;
        commission_type_label: string;
        commission_value: string;
        commission_notes: string | null;
        expense_responsibility_notes: string | null;
        withdrawal_terms: string | null;
        settlement_terms: string | null;
        remarks: string | null;
        received_by_name: string;
        agreement_version: number;
        duration_days: number | null;
        vehicle: {
            id: number;
            reference_number: string;
            title: string;
            exterior_colour: string;
            status_label: string;
            status_variant: StatusVariant;
            photo_url: string | null;
        } | null;
        owner: {
            id: number;
            reference_number: string;
            name: string;
            phone: string;
            email: string | null;
            bank_name: string | null;
            bank_account_number: string | null;
        } | null;
        versions: Array<{
            id: number;
            version_number: number;
            agreed_selling_price: string;
            minimum_acceptable_price: string | null;
            commission_type: string;
            commission_type_label: string;
            commission_value: string;
            amendment_reason: string | null;
            created_by: string;
            created_at: string | null;
        }>;
        status_histories: Array<{
            id: number;
            from_label: string | null;
            to_label: string;
            to_variant: StatusVariant;
            reason: string | null;
            changed_by: string;
            created_at: string | null;
        }>;
    };
    canAmend: boolean;
    commissionTypes: Array<{
        value: string;
        label: string;
        description: string;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Consignments', href: index() },
            { title: 'Consignment Details', href: index() },
        ],
    },
});

const { can } = usePermissions();
const { formatMoney, formatDate, formatDateTime } = useFormatting();
const amendOpen = ref(false);

const amendForm = useForm({
    agreed_selling_price: props.consignment.agreed_selling_price,
    minimum_acceptable_price: props.consignment.minimum_acceptable_price ?? '',
    commission_type: props.consignment.commission_type,
    commission_value: props.consignment.commission_value,
    commission_notes: props.consignment.commission_notes ?? '',
    expense_responsibility_notes:
        props.consignment.expense_responsibility_notes ?? '',
    withdrawal_terms: props.consignment.withdrawal_terms ?? '',
    settlement_terms: props.consignment.settlement_terms ?? '',
    amendment_reason: '',
});

function submitAmendment(): void {
    amendForm.post(amendTerms.url(props.consignment.id), {
        preserveScroll: true,
        onSuccess: () => {
            amendOpen.value = false;
            amendForm.reset('amendment_reason');
        },
    });
}
</script>

<template>
    <Head :title="`Consignment ${consignment.reference_number}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="consignment.reference_number"
            :description="`Agreement v${consignment.agreement_version} · Received ${formatDate(consignment.received_at)} by ${consignment.received_by_name}`"
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Consignments
                    </Link>
                </Button>
                <Button as-child variant="outline" class="min-h-10">
                    <a :href="agreementPdf.url(consignment.id)">
                        <Download class="mr-1.5 size-4" aria-hidden="true" />
                        Agreement PDF
                    </a>
                </Button>
                <Button
                    v-if="
                        can('withdrawals.initiate') &&
                        (consignment.status === 'active' ||
                            consignment.status === 'on_hold')
                    "
                    as-child
                    variant="outline"
                    class="min-h-10"
                >
                    <Link
                        :href="
                            createWithdrawal({
                                query: { consignment_id: consignment.id },
                            })
                        "
                    >
                        <Undo2 class="mr-1.5 size-4" aria-hidden="true" />
                        Initiate Withdrawal
                    </Link>
                </Button>
                <Button
                    v-if="canAmend"
                    type="button"
                    class="min-h-10"
                    @click="amendOpen = true"
                >
                    <FileEdit class="mr-1.5 size-4" aria-hidden="true" />
                    Amend Terms
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <FormSectionCard title="Active Financial & Commission Terms">
                    <template #headerAction>
                        <StatusBadge
                            :label="consignment.status_label"
                            :variant="consignment.status_variant"
                        />
                    </template>

                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Agreed Selling Price
                            </dt>
                            <dd
                                class="text-lg font-bold text-foreground tabular-nums"
                            >
                                {{
                                    formatMoney(
                                        consignment.agreed_selling_price,
                                    )
                                }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Minimum Acceptable / Floor Price
                            </dt>
                            <dd
                                class="font-semibold text-foreground tabular-nums"
                            >
                                {{
                                    consignment.minimum_acceptable_price
                                        ? formatMoney(
                                              consignment.minimum_acceptable_price,
                                          )
                                        : 'Not specified'
                                }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3 sm:col-span-2">
                            <dt class="text-xs text-muted-foreground">
                                Commission Arrangement
                            </dt>
                            <dd class="mt-0.5 font-semibold text-foreground">
                                {{ consignment.commission_type_label }}
                                <span
                                    v-if="
                                        consignment.commission_type ===
                                        'percentage'
                                    "
                                    class="ml-1 text-muted-foreground"
                                >
                                    ({{ consignment.commission_value }}%)
                                </span>
                                <span
                                    v-else-if="
                                        consignment.commission_type === 'fixed'
                                    "
                                    class="ml-1 text-muted-foreground tabular-nums"
                                >
                                    ({{
                                        formatMoney(
                                            consignment.commission_value,
                                        )
                                    }})
                                </span>
                            </dd>
                            <p
                                v-if="consignment.commission_notes"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                {{ consignment.commission_notes }}
                            </p>
                        </div>
                    </dl>
                </FormSectionCard>

                <FormSectionCard
                    title="Agreement Version History"
                    description="Every amendment to selling prices or commission terms is archived permanently."
                >
                    <ol class="divide-y divide-border/60">
                        <li
                            v-for="v in consignment.versions"
                            :key="v.id"
                            class="space-y-1.5 py-3.5 text-sm"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-2"
                            >
                                <div class="flex items-center gap-2">
                                    <StatusBadge
                                        :label="`Version v${v.version_number}`"
                                        :variant="
                                            v.version_number ===
                                            consignment.agreement_version
                                                ? 'success'
                                                : 'neutral'
                                        "
                                    />
                                    <span
                                        class="font-semibold text-foreground tabular-nums"
                                    >
                                        {{
                                            formatMoney(v.agreed_selling_price)
                                        }}
                                    </span>
                                </div>
                                <span
                                    class="text-xs text-muted-foreground tabular-nums"
                                >
                                    {{ formatDateTime(v.created_at) }} ·
                                    {{ v.created_by }}
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ v.commission_type_label }}
                                <span v-if="v.minimum_acceptable_price">
                                    · Min Floor:
                                    {{
                                        formatMoney(v.minimum_acceptable_price)
                                    }}
                                </span>
                            </p>
                            <p
                                v-if="v.amendment_reason"
                                class="text-xs text-foreground italic"
                            >
                                "{{ v.amendment_reason }}"
                            </p>
                        </li>
                    </ol>
                </FormSectionCard>
            </div>

            <div class="space-y-5">
                <FormSectionCard v-if="consignment.vehicle" title="Vehicle">
                    <div class="space-y-2 text-sm">
                        <Link
                            :href="showVehicle(consignment.vehicle.id)"
                            class="block font-semibold text-foreground hover:underline"
                        >
                            {{ consignment.vehicle.title }}
                        </Link>
                        <p class="font-mono text-xs text-muted-foreground">
                            {{ consignment.vehicle.reference_number }} ·
                            {{ consignment.vehicle.exterior_colour }}
                        </p>
                        <StatusBadge
                            :label="consignment.vehicle.status_label"
                            :variant="consignment.vehicle.status_variant"
                        />
                    </div>
                </FormSectionCard>

                <FormSectionCard v-if="consignment.owner" title="Vehicle Owner">
                    <div class="space-y-1.5 text-sm">
                        <Link
                            :href="showOwner(consignment.owner.id)"
                            class="block font-semibold text-foreground hover:underline"
                        >
                            {{ consignment.owner.name }}
                        </Link>
                        <p class="font-mono text-xs text-muted-foreground">
                            {{ consignment.owner.reference_number }}
                        </p>
                        <p class="text-xs text-foreground tabular-nums">
                            {{ consignment.owner.phone }}
                        </p>
                        <p
                            v-if="consignment.owner.bank_name"
                            class="pt-1 text-xs text-muted-foreground"
                        >
                            {{ consignment.owner.bank_name }} ({{
                                consignment.owner.bank_account_number
                            }})
                        </p>
                    </div>
                </FormSectionCard>

                <FormSectionCard title="Status History">
                    <ol class="divide-y divide-border/60">
                        <li
                            v-for="h in consignment.status_histories"
                            :key="h.id"
                            class="space-y-1 py-2.5 text-xs"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span class="inline-flex items-center gap-1.5">
                                    <History
                                        class="size-3.5 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                    <StatusBadge
                                        :label="h.to_label"
                                        :variant="h.to_variant"
                                    />
                                </span>
                                <span
                                    class="text-muted-foreground tabular-nums"
                                >
                                    {{ formatDate(h.created_at) }}
                                </span>
                            </div>
                            <p v-if="h.reason" class="text-muted-foreground">
                                {{ h.reason }}
                            </p>
                        </li>
                    </ol>
                </FormSectionCard>
            </div>
        </div>

        <!-- Manager Amend Terms Dialog -->
        <Dialog :open="amendOpen" @update:open="amendOpen = $event">
            <DialogContent class="max-h-[90svh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Amend Consignment Financial Terms</DialogTitle>
                    <DialogDescription>
                        Creates a new versioned snapshot (v{{
                            consignment.agreement_version + 1
                        }}) with a mandatory audit reason.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitAmendment">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="am_price"
                                >Agreed Selling Price (₦) *</Label
                            >
                            <Input
                                id="am_price"
                                v-model="amendForm.agreed_selling_price"
                                type="number"
                                step="0.01"
                                required
                                class="min-h-10 tabular-nums"
                            />
                            <InputError
                                :message="amendForm.errors.agreed_selling_price"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="am_min">Minimum Floor Price (₦)</Label>
                            <Input
                                id="am_min"
                                v-model="amendForm.minimum_acceptable_price"
                                type="number"
                                step="0.01"
                                class="min-h-10 tabular-nums"
                            />
                            <InputError
                                :message="
                                    amendForm.errors.minimum_acceptable_price
                                "
                            />
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="am_type">Commission Mode *</Label>
                            <select
                                id="am_type"
                                v-model="amendForm.commission_type"
                                class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            >
                                <option
                                    v-for="opt in commissionTypes"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>
                        <div
                            v-if="
                                amendForm.commission_type !==
                                'excess_over_minimum'
                            "
                            class="space-y-1.5"
                        >
                            <Label for="am_val">Commission Value</Label>
                            <Input
                                id="am_val"
                                v-model="amendForm.commission_value"
                                type="number"
                                step="0.01"
                                class="min-h-10 tabular-nums"
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="am_reason"
                            >Reason for Amendment
                            <span class="text-destructive">*</span></Label
                        >
                        <textarea
                            id="am_reason"
                            v-model="amendForm.amendment_reason"
                            rows="2"
                            required
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            placeholder="e.g. Owner agreed to reduce asking price by ₦500,000"
                        />
                        <InputError
                            :message="amendForm.errors.amendment_reason"
                        />
                    </div>

                    <DialogFooter class="flex-col-reverse gap-2 sm:flex-row">
                        <Button
                            type="button"
                            variant="outline"
                            class="min-h-10"
                            @click="amendOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            class="min-h-10"
                            :disabled="amendForm.processing"
                        >
                            <Spinner
                                v-if="amendForm.processing"
                                class="mr-1.5"
                            />
                            Save Amended Version
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
