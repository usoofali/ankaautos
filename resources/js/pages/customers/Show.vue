<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, MessageSquarePlus, Pencil } from '@lucide/vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatusBadge, {
    type StatusVariant,
} from '@/components/anka/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormatting } from '@/composables/useFormatting';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { edit, index } from '@/routes/customers';
import { store as storeInquiry } from '@/routes/customers/inquiries';
import { show as showReservation } from '@/routes/reservations';
import { show as showSale } from '@/routes/sales';

const props = defineProps<{
    customer: {
        id: number;
        reference_number: string;
        name: string;
        category_label: string;
        phone: string;
        alt_phone: string | null;
        email: string | null;
        address: string | null;
        id_type: string | null;
        id_number: string | null;
        notes: string | null;
        inquiries: Array<{
            id: number;
            vehicle_title: string;
            channel: string;
            status_label: string;
            status_variant: StatusVariant;
            offered_price: string | null;
            inspection_date: string | null;
            inspection_notes: string | null;
            negotiation_notes: string | null;
            assigned_to: string;
            created_at: string | null;
        }>;
        reservations: Array<{
            id: number;
            reference_number: string;
            vehicle_title: string;
            deposit_amount: string;
            agreed_price: string;
            status_label: string;
            status_variant: StatusVariant;
        }>;
        sales: Array<{
            id: number;
            reference_number: string;
            vehicle_title: string;
            final_price: string;
            balance_due: string;
            status_label: string;
            status_variant: StatusVariant;
        }>;
    };
    availableVehicles: Array<{ id: number; label: string }>;
    inquiryStatuses: Array<{ value: string; label: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Customers', href: index() },
            { title: 'Customer Profile', href: index() },
        ],
    },
});

const { can } = usePermissions();
const { formatMoney, formatDate } = useFormatting();

const inquiryForm = useForm({
    vehicle_id: props.availableVehicles[0]?.id ?? 0,
    channel: 'walk_in',
    status: 'inspected',
    offered_price: '',
    inspection_date: new Date().toISOString().slice(0, 10),
    inspection_notes: '',
    negotiation_notes: '',
});

function submitInquiry(): void {
    inquiryForm.post(storeInquiry.url(props.customer.id), {
        preserveScroll: true,
        onSuccess: () =>
            inquiryForm.reset(
                'offered_price',
                'inspection_notes',
                'negotiation_notes',
            ),
    });
}
</script>

<template>
    <Head :title="customer.name" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="customer.name"
            :description="`${customer.reference_number} · ${customer.category_label} · ${customer.phone}`"
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Customers
                    </Link>
                </Button>
                <Button
                    v-if="can('customers.manage')"
                    as-child
                    class="min-h-10"
                >
                    <Link :href="edit(customer.id)">
                        <Pencil class="mr-1.5 size-4" aria-hidden="true" />
                        Edit Customer
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <FormSectionCard
                    :title="`Sales & Purchases (${customer.sales.length})`"
                >
                    <div
                        v-if="customer.sales.length === 0"
                        class="py-4 text-sm text-muted-foreground"
                    >
                        No vehicle purchases recorded yet.
                    </div>
                    <div v-else class="divide-y divide-border/60">
                        <div
                            v-for="s in customer.sales"
                            :key="s.id"
                            class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm"
                        >
                            <div>
                                <Link
                                    :href="showSale(s.id)"
                                    class="font-mono text-xs font-semibold hover:underline"
                                >
                                    {{ s.reference_number }}
                                </Link>
                                <p class="font-medium">{{ s.vehicle_title }}</p>
                            </div>
                            <div class="text-right">
                                <StatusBadge
                                    :label="s.status_label"
                                    :variant="s.status_variant"
                                />
                                <p
                                    class="mt-1 text-xs font-semibold tabular-nums"
                                >
                                    {{ formatMoney(s.final_price) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </FormSectionCard>

                <FormSectionCard
                    :title="`Reservations (${customer.reservations.length})`"
                >
                    <div
                        v-if="customer.reservations.length === 0"
                        class="py-4 text-sm text-muted-foreground"
                    >
                        No reservations recorded.
                    </div>
                    <div v-else class="divide-y divide-border/60">
                        <div
                            v-for="r in customer.reservations"
                            :key="r.id"
                            class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm"
                        >
                            <div>
                                <Link
                                    :href="showReservation(r.id)"
                                    class="font-mono text-xs font-semibold hover:underline"
                                >
                                    {{ r.reference_number }}
                                </Link>
                                <p class="font-medium">{{ r.vehicle_title }}</p>
                            </div>
                            <div class="text-right">
                                <StatusBadge
                                    :label="r.status_label"
                                    :variant="r.status_variant"
                                />
                                <p class="mt-1 text-xs tabular-nums">
                                    Deposit: {{ formatMoney(r.deposit_amount) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </FormSectionCard>

                <FormSectionCard
                    :title="`Enquiries, Inspections & Negotiations (${customer.inquiries.length})`"
                >
                    <div
                        v-if="customer.inquiries.length === 0"
                        class="py-4 text-sm text-muted-foreground"
                    >
                        No vehicle enquiries logged yet.
                    </div>
                    <ol v-else class="divide-y divide-border/60">
                        <li
                            v-for="inq in customer.inquiries"
                            :key="inq.id"
                            class="space-y-1 py-3 text-sm"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-2"
                            >
                                <span class="font-semibold">{{
                                    inq.vehicle_title
                                }}</span>
                                <StatusBadge
                                    :label="inq.status_label"
                                    :variant="inq.status_variant"
                                />
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Channel: {{ inq.channel }}
                                <span v-if="inq.offered_price">
                                    · Offer:
                                    {{ formatMoney(inq.offered_price) }}
                                </span>
                                <span v-if="inq.inspection_date">
                                    · Inspected
                                    {{ formatDate(inq.inspection_date) }}
                                </span>
                            </p>
                            <p v-if="inq.inspection_notes" class="text-xs">
                                <strong>Inspection:</strong>
                                {{ inq.inspection_notes }}
                            </p>
                            <p v-if="inq.negotiation_notes" class="text-xs">
                                <strong>Negotiation:</strong>
                                {{ inq.negotiation_notes }}
                            </p>
                        </li>
                    </ol>
                </FormSectionCard>
            </div>

            <FormSectionCard
                v-if="can('customers.manage') && availableVehicles.length > 0"
                title="Log Enquiry / Inspection"
            >
                <form class="space-y-3" @submit.prevent="submitInquiry">
                    <div class="space-y-1.5">
                        <Label for="inq_vehicle">Vehicle *</Label>
                        <select
                            id="inq_vehicle"
                            v-model.number="inquiryForm.vehicle_id"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="v in availableVehicles"
                                :key="v.id"
                                :value="v.id"
                            >
                                {{ v.label }}
                            </option>
                        </select>
                        <InputError :message="inquiryForm.errors.vehicle_id" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-1.5">
                            <Label for="inq_channel">Channel</Label>
                            <select
                                id="inq_channel"
                                v-model="inquiryForm.channel"
                                class="flex min-h-10 w-full rounded-md border border-input bg-background px-2.5 py-2 text-xs text-foreground"
                            >
                                <option value="walk_in">Walk-in</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="phone">Phone Call</option>
                                <option value="facebook">Facebook</option>
                                <option value="instagram">Instagram</option>
                                <option value="website">Website</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="inq_status">Stage</Label>
                            <select
                                id="inq_status"
                                v-model="inquiryForm.status"
                                class="flex min-h-10 w-full rounded-md border border-input bg-background px-2.5 py-2 text-xs text-foreground"
                            >
                                <option
                                    v-for="s in inquiryStatuses"
                                    :key="s.value"
                                    :value="s.value"
                                >
                                    {{ s.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="inq_offer">Customer Offer (₦)</Label>
                        <Input
                            id="inq_offer"
                            v-model="inquiryForm.offered_price"
                            type="number"
                            step="0.01"
                            class="min-h-10 tabular-nums"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="inq_insp">Inspection Notes</Label>
                        <textarea
                            id="inq_insp"
                            v-model="inquiryForm.inspection_notes"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-xs text-foreground"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="inq_neg">Negotiation Notes</Label>
                        <textarea
                            id="inq_neg"
                            v-model="inquiryForm.negotiation_notes"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-xs text-foreground"
                        />
                    </div>
                    <Button
                        type="submit"
                        class="min-h-10 w-full"
                        :disabled="inquiryForm.processing"
                    >
                        <Spinner v-if="inquiryForm.processing" class="mr-1.5" />
                        <MessageSquarePlus
                            v-else
                            class="mr-1.5 size-4"
                            aria-hidden="true"
                        />
                        Log Enquiry
                    </Button>
                </form>
            </FormSectionCard>
        </div>
    </div>
</template>
