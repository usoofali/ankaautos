<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import { computed } from 'vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { create, index, store } from '@/routes/consignments';

type VehicleOption = {
    id: number;
    label: string;
    advertised_price: string | null;
};

type OwnerOption = {
    id: number;
    reference_number: string;
    name: string;
    phone: string;
};

type CommissionOption = {
    value: string;
    label: string;
    description: string;
};

const props = defineProps<{
    vehicles: VehicleOption[];
    owners: OwnerOption[];
    commissionTypes: CommissionOption[];
    preselectedVehicleId: number | null;
    preselectedOwnerId: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Consignments', href: index() },
            { title: 'New Consignment', href: create() },
        ],
    },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    vehicle_id: props.preselectedVehicleId ?? props.vehicles[0]?.id ?? 0,
    vehicle_owner_id: props.preselectedOwnerId ?? props.owners[0]?.id ?? 0,
    status: 'active',
    received_at: today,
    expected_review_date: '',
    duration_days: '60',
    agreed_selling_price: '',
    minimum_acceptable_price: '',
    commission_type: 'percentage',
    commission_value: '5',
    commission_notes: '',
    expense_responsibility_notes: '',
    withdrawal_terms: '',
    settlement_terms: '',
    remarks: '',
});

const selectedCommission = computed(() =>
    props.commissionTypes.find((c) => c.value === form.commission_type),
);

function submit(): void {
    form.post(store.url());
}
</script>

<template>
    <Head title="New Consignment Agreement" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="New Consignment Agreement"
            description="Link a registered consignment vehicle to its third-party owner and lock in version 1 pricing and commission terms."
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Consignments
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="max-w-4xl space-y-5" @submit.prevent="submit">
            <FormSectionCard
                title="1. Vehicle & Consignor"
                description="Only consignment-classified vehicles without an open agreement are listed."
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="vehicle_id"
                            >Consignment Vehicle
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
                        <p
                            v-if="vehicles.length === 0"
                            class="text-xs text-destructive"
                        >
                            No unlinked consignment vehicles available. Register
                            a vehicle under Third-Party Consignment first.
                        </p>
                        <InputError :message="form.errors.vehicle_id" />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="vehicle_owner_id"
                            >Vehicle Owner (Consignor)
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="vehicle_owner_id"
                            v-model.number="form.vehicle_owner_id"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="o in owners"
                                :key="o.id"
                                :value="o.id"
                            >
                                {{ o.name }} ({{ o.reference_number }} ·
                                {{ o.phone }})
                            </option>
                        </select>
                        <InputError :message="form.errors.vehicle_owner_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="received_at"
                            >Commencement / Date Received
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="received_at"
                            v-model="form.received_at"
                            type="date"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.received_at" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <Label for="duration_days">Duration (Days)</Label>
                            <Input
                                id="duration_days"
                                v-model="form.duration_days"
                                type="number"
                                min="1"
                                class="min-h-10 tabular-nums"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="expected_review_date"
                                >Review Date</Label
                            >
                            <Input
                                id="expected_review_date"
                                v-model="form.expected_review_date"
                                type="date"
                                class="min-h-10"
                            />
                        </div>
                    </div>
                </div>
            </FormSectionCard>

            <FormSectionCard
                title="2. Pricing & Commission Arrangement"
                description="Choose how Anka Auto Sales' commission is calculated when this vehicle is sold."
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="agreed_selling_price"
                            >Agreed Selling Price (₦)
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="agreed_selling_price"
                            v-model="form.agreed_selling_price"
                            type="number"
                            step="0.01"
                            min="1"
                            required
                            class="min-h-10 tabular-nums"
                        />
                        <InputError
                            :message="form.errors.agreed_selling_price"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="minimum_acceptable_price">
                            Minimum Acceptable / Owner-Net Price (₦)
                            <span
                                v-if="
                                    form.commission_type ===
                                    'excess_over_minimum'
                                "
                                class="text-destructive"
                                >*</span
                            >
                        </Label>
                        <Input
                            id="minimum_acceptable_price"
                            v-model="form.minimum_acceptable_price"
                            type="number"
                            step="0.01"
                            min="0"
                            :required="
                                form.commission_type === 'excess_over_minimum'
                            "
                            class="min-h-10 tabular-nums"
                        />
                        <InputError
                            :message="form.errors.minimum_acceptable_price"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="commission_type"
                            >Commission Mode
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="commission_type"
                            v-model="form.commission_type"
                            required
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
                        <p
                            v-if="selectedCommission"
                            class="text-xs text-muted-foreground"
                        >
                            {{ selectedCommission.description }}
                        </p>
                    </div>

                    <div
                        v-if="form.commission_type !== 'excess_over_minimum'"
                        class="space-y-2"
                    >
                        <Label for="commission_value">
                            {{
                                form.commission_type === 'percentage'
                                    ? 'Commission Percentage (%)'
                                    : 'Commission Amount (₦)'
                            }}
                            <span
                                v-if="form.commission_type !== 'custom'"
                                class="text-destructive"
                                >*</span
                            >
                        </Label>
                        <Input
                            id="commission_value"
                            v-model="form.commission_value"
                            type="number"
                            step="0.01"
                            min="0"
                            :max="
                                form.commission_type === 'percentage'
                                    ? '100'
                                    : undefined
                            "
                            class="min-h-10 tabular-nums"
                        />
                        <InputError :message="form.errors.commission_value" />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="commission_notes">Commission Notes</Label>
                        <Input
                            id="commission_notes"
                            v-model="form.commission_notes"
                            class="min-h-10"
                            placeholder="Optional clarification of agreed commission terms"
                        />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="expense_responsibility_notes"
                            >Expense Responsibility Terms</Label
                        >
                        <textarea
                            id="expense_responsibility_notes"
                            v-model="form.expense_responsibility_notes"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="withdrawal_terms"
                            >Withdrawal Conditions</Label
                        >
                        <textarea
                            id="withdrawal_terms"
                            v-model="form.withdrawal_terms"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="settlement_terms"
                            >Settlement Conditions</Label
                        >
                        <textarea
                            id="settlement_terms"
                            v-model="form.settlement_terms"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                    </div>
                </div>

                <template #footer>
                    <Button as-child variant="outline" class="min-h-10">
                        <Link :href="index()">Cancel</Link>
                    </Button>
                    <Button
                        type="submit"
                        class="min-h-10"
                        :disabled="form.processing || vehicles.length === 0"
                    >
                        <Spinner v-if="form.processing" class="mr-1.5" />
                        <Save v-else class="mr-1.5 size-4" aria-hidden="true" />
                        Create Consignment Agreement
                    </Button>
                </template>
            </FormSectionCard>
        </form>
    </div>
</template>
