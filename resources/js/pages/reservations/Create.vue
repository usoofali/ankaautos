<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, UserPlus } from '@lucide/vue';
import { ref } from 'vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import QuickCustomerModal, {
    type QuickCustomer,
} from '@/components/anka/QuickCustomerModal.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { create, index, store } from '@/routes/reservations';

const props = defineProps<{
    vehicles: Array<{
        id: number;
        label: string;
        advertised_price: string | null;
        is_reserved: boolean;
    }>;
    customers: Array<{
        id: number;
        reference_number: string;
        name: string;
        phone: string;
    }>;
    paymentMethods: Array<{ id: number; name: string }>;
    financialAccounts: Array<{ id: number; name: string }>;
    canOverrideConflict: boolean;
    preselectedVehicleId: number | null;
    preselectedCustomerId: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Reservations', href: index() },
            { title: 'New Reservation', href: create() },
        ],
    },
});

const { can } = usePermissions();
const customerList = ref<QuickCustomer[]>([...props.customers]);
const quickCustomerOpen = ref(false);

const form = useForm({
    vehicle_id: props.preselectedVehicleId ?? props.vehicles[0]?.id ?? 0,
    customer_id: props.preselectedCustomerId ?? props.customers[0]?.id ?? 0,
    agreed_price: props.vehicles[0]?.advertised_price ?? '',
    deposit_amount: '',
    payment_method_id: props.paymentMethods[0]?.id ?? 0,
    financial_account_id: props.financialAccounts[0]?.id ?? 0,
    paid_at: new Date().toISOString().slice(0, 10),
    external_reference: '',
    notes: '',
    override_conflict: false,
    idempotency_key: crypto.randomUUID(),
});

function onCustomerCreated(customer: QuickCustomer): void {
    customerList.value.unshift(customer);
    form.customer_id = customer.id;
}

function submit(): void {
    form.post(store.url());
}
</script>

<template>
    <Head title="Create Reservation" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="New Vehicle Reservation"
            description="Every reservation requires a mandatory deposit recorded as a payment and holds the vehicle until converted to a sale or cancelled."
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Reservations
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="max-w-3xl space-y-5" @submit.prevent="submit">
            <FormSectionCard title="Reservation & Mandatory Deposit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="res_vehicle">Vehicle *</Label>
                        <select
                            id="res_vehicle"
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

                    <div class="space-y-2 sm:col-span-2">
                        <div class="flex items-center justify-between gap-2">
                            <Label for="res_customer">Customer *</Label>
                            <Button
                                v-if="can('customers.manage')"
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-8 text-xs"
                                @click="quickCustomerOpen = true"
                            >
                                <UserPlus
                                    class="mr-1.5 size-3.5"
                                    aria-hidden="true"
                                />
                                New Customer
                            </Button>
                        </div>
                        <select
                            id="res_customer"
                            v-model.number="form.customer_id"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="c in customerList"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }} ({{ c.phone }})
                            </option>
                        </select>
                        <InputError :message="form.errors.customer_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="res_price">Agreed Sale Price (₦) *</Label>
                        <Input
                            id="res_price"
                            v-model="form.agreed_price"
                            type="number"
                            step="0.01"
                            min="1"
                            required
                            class="min-h-10 tabular-nums"
                        />
                        <InputError :message="form.errors.agreed_price" />
                    </div>

                    <div class="space-y-2">
                        <Label for="res_deposit">Mandatory Deposit (₦) *</Label>
                        <Input
                            id="res_deposit"
                            v-model="form.deposit_amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            class="min-h-10 tabular-nums"
                        />
                        <InputError :message="form.errors.deposit_amount" />
                    </div>

                    <div class="space-y-2">
                        <Label for="res_method">Payment Method *</Label>
                        <select
                            id="res_method"
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
                        <Label for="res_account">Receiving Account *</Label>
                        <select
                            id="res_account"
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
                        <Label for="res_date">Payment Date *</Label>
                        <Input
                            id="res_date"
                            v-model="form.paid_at"
                            type="date"
                            required
                            class="min-h-10"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="res_ref">Transfer / POS Reference</Label>
                        <Input
                            id="res_ref"
                            v-model="form.external_reference"
                            class="min-h-10"
                        />
                    </div>

                    <label
                        v-if="canOverrideConflict"
                        class="flex cursor-pointer items-center gap-2.5 text-xs sm:col-span-2"
                    >
                        <Checkbox
                            :model-value="form.override_conflict"
                            @update:model-value="
                                (v) => (form.override_conflict = Boolean(v))
                            "
                        />
                        <span
                            >Manager Override: allow reserving even if another
                            active reservation exists</span
                        >
                    </label>
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
                        Confirm Reservation
                    </Button>
                </template>
            </FormSectionCard>
        </form>

        <QuickCustomerModal
            v-model:open="quickCustomerOpen"
            @created="onCustomerCreated"
        />
    </div>
</template>
