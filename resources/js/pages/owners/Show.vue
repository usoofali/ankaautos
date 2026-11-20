<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, FileCheck2, Pencil, Plus } from '@lucide/vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatusBadge, {
    type StatusVariant,
} from '@/components/anka/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useFormatting } from '@/composables/useFormatting';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import {
    create as createConsignment,
    show as showConsignment,
} from '@/routes/consignments';
import { edit, index } from '@/routes/owners';
import { show as showVehicle } from '@/routes/vehicles';

defineProps<{
    owner: {
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
        bank_name: string | null;
        bank_account_name: string | null;
        bank_account_number: string | null;
        remarks: string | null;
        is_active: boolean;
        consignments: Array<{
            id: number;
            reference_number: string;
            vehicle_id: number;
            vehicle_reference: string;
            vehicle_title: string;
            status_label: string;
            status_variant: StatusVariant;
            agreed_selling_price: string;
            commission_label: string;
            received_at: string;
        }>;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Owners', href: index() },
            { title: 'Owner Profile', href: index() },
        ],
    },
});

const { can } = usePermissions();
const { formatMoney, formatDate } = useFormatting();
</script>

<template>
    <Head :title="owner.name" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="owner.name"
            :description="`${owner.reference_number} · ${owner.category_label}`"
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Owners
                    </Link>
                </Button>
                <Button
                    v-if="can('consignments.create')"
                    as-child
                    variant="outline"
                    class="min-h-10"
                >
                    <Link
                        :href="
                            createConsignment({ query: { owner_id: owner.id } })
                        "
                    >
                        <Plus class="mr-1.5 size-4" aria-hidden="true" />
                        New Consignment
                    </Link>
                </Button>
                <Button v-if="can('owners.manage')" as-child class="min-h-10">
                    <Link :href="edit(owner.id)">
                        <Pencil class="mr-1.5 size-4" aria-hidden="true" />
                        Edit Owner
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-5 lg:grid-cols-3">
            <FormSectionCard title="Contact & Settlement Details">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-muted-foreground">Phone</dt>
                        <dd class="font-medium tabular-nums">
                            {{ owner.phone }}
                            <span
                                v-if="owner.alt_phone"
                                class="text-muted-foreground"
                            >
                                / {{ owner.alt_phone }}
                            </span>
                        </dd>
                    </div>
                    <div v-if="owner.email">
                        <dt class="text-xs text-muted-foreground">Email</dt>
                        <dd>{{ owner.email }}</dd>
                    </div>
                    <div v-if="owner.address">
                        <dt class="text-xs text-muted-foreground">Address</dt>
                        <dd>{{ owner.address }}</dd>
                    </div>
                    <div v-if="owner.id_number">
                        <dt class="text-xs text-muted-foreground">
                            Identification
                        </dt>
                        <dd class="font-mono text-xs">
                            {{ owner.id_type }}: {{ owner.id_number }}
                        </dd>
                    </div>
                    <div class="border-t border-border/60 pt-3">
                        <dt class="text-xs text-muted-foreground">
                            Payout Bank Account
                        </dt>
                        <dd v-if="owner.bank_account_number" class="mt-0.5">
                            <div class="font-medium">
                                {{ owner.bank_name }}
                            </div>
                            <div class="font-mono text-xs tabular-nums">
                                {{ owner.bank_account_number }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ owner.bank_account_name }}
                            </div>
                        </dd>
                        <dd v-else class="text-xs text-muted-foreground">
                            Not recorded yet
                        </dd>
                    </div>
                </dl>
            </FormSectionCard>

            <FormSectionCard
                :title="`Consigned Vehicles (${owner.consignments.length})`"
                description="Consolidated history of all vehicles previously or currently entrusted to Anka Auto Sales."
                class="lg:col-span-2"
            >
                <div
                    v-if="owner.consignments.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No vehicles consigned by this owner yet.
                </div>
                <div v-else class="divide-y divide-border/60">
                    <div
                        v-for="item in owner.consignments"
                        :key="item.id"
                        class="flex flex-col gap-2 py-3.5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <Link
                                    :href="showConsignment(item.id)"
                                    class="font-mono text-xs font-semibold text-foreground hover:underline"
                                >
                                    {{ item.reference_number }}
                                </Link>
                                <StatusBadge
                                    :label="item.status_label"
                                    :variant="item.status_variant"
                                />
                            </div>
                            <Link
                                :href="showVehicle(item.vehicle_id)"
                                class="block font-medium text-foreground hover:underline"
                            >
                                {{ item.vehicle_title }}
                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    ({{ item.vehicle_reference }})
                                </span>
                            </Link>
                            <p class="text-xs text-muted-foreground">
                                {{ item.commission_label }} · Received
                                {{ formatDate(item.received_at) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3 sm:text-right">
                            <div>
                                <p class="text-xs text-muted-foreground">
                                    Agreed Price
                                </p>
                                <p
                                    class="font-semibold text-foreground tabular-nums"
                                >
                                    {{ formatMoney(item.agreed_selling_price) }}
                                </p>
                            </div>
                            <Button
                                as-child
                                variant="outline"
                                size="sm"
                                class="min-h-9"
                            >
                                <Link :href="showConsignment(item.id)">
                                    <FileCheck2
                                        class="mr-1.5 size-3.5"
                                        aria-hidden="true"
                                    />
                                    Open
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </FormSectionCard>
        </div>
    </div>
</template>
