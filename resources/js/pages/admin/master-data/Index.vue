<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { MoreHorizontal, Pencil, Plus, Power, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/anka/ConfirmDialog.vue';
import DataTableCard, {
    type DataTableColumn,
} from '@/components/anka/DataTableCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatusBadge, {
    type StatusVariant,
} from '@/components/anka/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormatting } from '@/composables/useFormatting';
import { dashboard } from '@/routes';
import {
    destroy,
    index,
    store,
    toggleActive,
    update,
} from '@/routes/admin/master-data';
import type { Paginated } from '@/types';

type CatalogItem = {
    id: number;
    name: string;
    description?: string | null;
    is_active: boolean;
    vehicle_brand_id?: number;
    brand?: { id: number; name: string } | null;
    severity_order?: number;
    badge_variant?: StatusVariant;
    applicable_types?: string[] | null;
    default_amount?: string | null;
    code?: string;
    requires_reference?: boolean;
    bank_name?: string | null;
    account_number?: string | null;
    account_type?: string;
    is_private?: boolean;
};

type BrandOption = {
    id: number;
    name: string;
    is_active: boolean;
};

type OptionItem = {
    value: string;
    label: string;
};

const props = defineProps<{
    activeTab: string;
    items: Paginated<CatalogItem>;
    brands: BrandOption[];
    chargeTransactionTypes: OptionItem[];
    canManageAllMasterData: boolean;
    filters: {
        search: string;
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Master Data & Charges', href: index() },
        ],
    },
});

const { formatMoney } = useFormatting();

const allTabs = [
    { key: 'brands', label: 'Vehicle Brands' },
    { key: 'models', label: 'Vehicle Models' },
    { key: 'categories', label: 'Vehicle Categories' },
    { key: 'conditions', label: 'Vehicle Conditions' },
    { key: 'expense_categories', label: 'Expense Categories' },
    { key: 'charge_categories', label: 'Charge Categories' },
    { key: 'payment_methods', label: 'Payment Methods' },
    { key: 'financial_accounts', label: 'Financial Accounts' },
    { key: 'document_categories', label: 'Document Categories' },
];

const visibleTabs = computed(() =>
    props.canManageAllMasterData
        ? allTabs
        : allTabs.filter((tab) => tab.key === 'charge_categories'),
);

const currentTabLabel = computed(
    () =>
        allTabs.find((tab) => tab.key === props.activeTab)?.label ??
        'Master Data',
);

const columns = computed<DataTableColumn[]>(() => {
    const base: DataTableColumn[] = [{ key: 'name', label: 'Name' }];

    if (props.activeTab === 'models') {
        base.push({ key: 'brand', label: 'Brand' });
    } else if (props.activeTab === 'conditions') {
        base.push({ key: 'severity', label: 'Order / Style' });
    } else if (props.activeTab === 'charge_categories') {
        base.push({
            key: 'applicable_types',
            label: 'Applicable Transactions',
        });
        base.push({
            key: 'default_amount',
            label: 'Suggested Amount',
            align: 'right',
        });
    } else if (props.activeTab === 'payment_methods') {
        base.push({ key: 'code', label: 'Code & Reference' });
    } else if (props.activeTab === 'financial_accounts') {
        base.push({ key: 'account', label: 'Bank / Account' });
    } else if (props.activeTab === 'document_categories') {
        base.push({ key: 'privacy', label: 'Access Level' });
    } else if (props.activeTab !== 'brands') {
        base.push({ key: 'description', label: 'Description' });
    }

    base.push({ key: 'status', label: 'Status' });
    base.push({ key: 'actions', label: 'Actions', align: 'right' });

    return base;
});

const modalOpen = ref(false);
const editingItem = ref<CatalogItem | null>(null);
const deleteCandidate = ref<CatalogItem | null>(null);
const deleting = ref(false);

const form = useForm({
    name: '',
    description: '',
    vehicle_brand_id: props.brands[0]?.id ?? 0,
    severity_order: 1,
    badge_variant: 'neutral' as StatusVariant,
    applicable_types: [] as string[],
    default_amount: '',
    code: '',
    requires_reference: false,
    bank_name: '',
    account_number: '',
    account_type: 'bank',
    is_private: true,
    is_active: true,
});

function switchTab(tabKey: string): void {
    router.get(
        index.url(),
        { tab: tabKey, per_page: props.filters.per_page },
        { preserveState: false },
    );
}

function updateQuery(overrides: Record<string, string | number>): void {
    router.get(
        index.url(),
        {
            tab: props.activeTab,
            search: props.filters.search || undefined,
            per_page: props.filters.per_page,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function openCreateModal(): void {
    editingItem.value = null;
    form.reset();
    form.clearErrors();
    form.vehicle_brand_id = props.brands[0]?.id ?? 0;
    form.severity_order = 1;
    form.badge_variant = 'neutral';
    form.applicable_types = [];
    form.account_type = 'bank';
    form.is_private = true;
    form.is_active = true;
    modalOpen.value = true;
}

function openEditModal(item: CatalogItem): void {
    editingItem.value = item;
    form.clearErrors();
    form.name = item.name;
    form.description = item.description ?? '';
    form.vehicle_brand_id = item.vehicle_brand_id ?? props.brands[0]?.id ?? 0;
    form.severity_order = item.severity_order ?? 1;
    form.badge_variant = item.badge_variant ?? 'neutral';
    form.applicable_types = [...(item.applicable_types ?? [])];
    form.default_amount = item.default_amount ?? '';
    form.code = item.code ?? '';
    form.requires_reference = Boolean(item.requires_reference);
    form.bank_name = item.bank_name ?? '';
    form.account_number = item.account_number ?? '';
    form.account_type = item.account_type ?? 'bank';
    form.is_private = item.is_private ?? true;
    form.is_active = item.is_active;
    modalOpen.value = true;
}

function toggleApplicableType(value: string, checked: boolean): void {
    if (checked && !form.applicable_types.includes(value)) {
        form.applicable_types.push(value);
    } else if (!checked) {
        form.applicable_types = form.applicable_types.filter(
            (v) => v !== value,
        );
    }
}

function submitForm(): void {
    if (editingItem.value) {
        form.put(
            update.url({ type: props.activeTab, id: editingItem.value.id }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    modalOpen.value = false;
                },
            },
        );
    } else {
        form.post(store.url(props.activeTab), {
            preserveScroll: true,
            onSuccess: () => {
                modalOpen.value = false;
            },
        });
    }
}

function handleToggleActive(item: CatalogItem): void {
    router.patch(
        toggleActive.url({ type: props.activeTab, id: item.id }),
        {},
        { preserveScroll: true },
    );
}

function confirmDelete(): void {
    if (!deleteCandidate.value) {
        return;
    }

    deleting.value = true;
    router.delete(
        destroy.url({ type: props.activeTab, id: deleteCandidate.value.id }),
        {
            preserveScroll: true,
            onFinish: () => {
                deleting.value = false;
                deleteCandidate.value = null;
            },
        },
    );
}

function formatChargeType(value: string): string {
    return (
        props.chargeTransactionTypes.find((t) => t.value === value)?.label ??
        value
    );
}
</script>

<template>
    <Head title="Master Data & Charges" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Master Data & Dynamic Charges"
            description="Configure vehicle catalogs, expense categories, reusable dynamic charge categories, payment methods, and accounts without code changes."
        >
            <template #actions>
                <Button
                    type="button"
                    class="min-h-10 w-full sm:w-auto"
                    @click="openCreateModal"
                >
                    <Plus class="mr-1.5 size-4" aria-hidden="true" />
                    Add {{ currentTabLabel.replace(/s$/, '') }}
                </Button>
            </template>
        </PageHeader>

        <!-- Scrollable Mobile-Friendly Category Tabs -->
        <div
            role="tablist"
            aria-label="Master data categories"
            class="flex gap-1.5 overflow-x-auto rounded-xl border border-border/80 bg-card p-1.5 shadow-xs"
        >
            <button
                v-for="tab in visibleTabs"
                :key="tab.key"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.key"
                :class="[
                    'min-h-10 shrink-0 rounded-lg px-3.5 py-2 text-xs font-medium whitespace-nowrap transition-colors sm:text-sm',
                    activeTab === tab.key
                        ? 'bg-primary text-primary-foreground shadow-xs'
                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                ]"
                @click="switchTab(tab.key)"
            >
                {{ tab.label }}
            </button>
        </div>

        <DataTableCard
            :caption="`${currentTabLabel} directory`"
            :columns="columns"
            :rows="items"
            :search="filters.search"
            :search-placeholder="`Search ${currentTabLabel.toLowerCase()}...`"
            :per-page="filters.per_page"
            :empty-title="`No ${currentTabLabel.toLowerCase()} found`"
            empty-description="Add an entry so staff can select it during dealership operations."
            @update:search="(value) => updateQuery({ search: value, page: 1 })"
            @update:per-page="
                (perPage) => updateQuery({ per_page: perPage, page: 1 })
            "
        >
            <template #emptyAction>
                <Button type="button" class="min-h-10" @click="openCreateModal">
                    <Plus class="mr-1.5 size-4" aria-hidden="true" />
                    Add Entry
                </Button>
            </template>

            <template #mobile-card="{ row }">
                <div class="space-y-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-semibold text-foreground">
                                {{ row.name }}
                            </p>
                            <p
                                v-if="activeTab === 'models' && row.brand"
                                class="text-xs text-muted-foreground"
                            >
                                Brand: {{ row.brand.name }}
                            </p>
                            <p
                                v-else-if="row.description"
                                class="text-xs text-muted-foreground"
                            >
                                {{ row.description }}
                            </p>
                        </div>
                        <StatusBadge
                            :label="row.is_active ? 'Active' : 'Inactive'"
                            :variant="row.is_active ? 'success' : 'neutral'"
                        />
                    </div>

                    <div
                        v-if="activeTab === 'charge_categories'"
                        class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                    >
                        <span class="font-medium text-foreground tabular-nums">
                            Suggested:
                            {{
                                row.default_amount
                                    ? formatMoney(row.default_amount)
                                    : 'Variable'
                            }}
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-1">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="min-h-9"
                            @click="openEditModal(row)"
                        >
                            <Pencil
                                class="mr-1.5 size-3.5"
                                aria-hidden="true"
                            />
                            Edit
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="min-h-9"
                            @click="handleToggleActive(row)"
                        >
                            <Power class="mr-1.5 size-3.5" aria-hidden="true" />
                            {{ row.is_active ? 'Deactivate' : 'Activate' }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="min-h-9 text-destructive hover:text-destructive"
                            @click="deleteCandidate = row"
                        >
                            <Trash2
                                class="mr-1.5 size-3.5"
                                aria-hidden="true"
                            />
                            Delete
                        </Button>
                    </div>
                </div>
            </template>

            <template #row="{ row }">
                <td class="px-4 py-3.5 font-medium text-foreground">
                    {{ row.name }}
                </td>

                <td
                    v-if="activeTab === 'models'"
                    class="px-4 py-3.5 text-muted-foreground"
                >
                    {{ row.brand?.name ?? '—' }}
                </td>

                <td v-else-if="activeTab === 'conditions'" class="px-4 py-3.5">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-muted-foreground tabular-nums"
                            >#{{ row.severity_order }}</span
                        >
                        <StatusBadge
                            :label="row.name"
                            :variant="row.badge_variant ?? 'neutral'"
                        />
                    </div>
                </td>

                <template v-else-if="activeTab === 'charge_categories'">
                    <td class="px-4 py-3.5">
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="t in row.applicable_types ?? []"
                                :key="t"
                                class="rounded-md bg-secondary px-2 py-0.5 text-xs text-secondary-foreground"
                            >
                                {{ formatChargeType(t) }}
                            </span>
                            <span
                                v-if="!row.applicable_types?.length"
                                class="text-xs text-muted-foreground"
                                >Any transaction</span
                            >
                        </div>
                    </td>
                    <td
                        class="px-4 py-3.5 text-right font-medium text-foreground tabular-nums"
                    >
                        {{
                            row.default_amount
                                ? formatMoney(row.default_amount)
                                : 'Variable'
                        }}
                    </td>
                </template>

                <td
                    v-else-if="activeTab === 'payment_methods'"
                    class="px-4 py-3.5 text-xs text-muted-foreground"
                >
                    <span class="font-mono text-foreground">{{
                        row.code
                    }}</span>
                    <span class="ml-2">{{
                        row.requires_reference
                            ? '· Reference required'
                            : '· Optional reference'
                    }}</span>
                </td>

                <td
                    v-else-if="activeTab === 'financial_accounts'"
                    class="px-4 py-3.5 text-xs text-muted-foreground"
                >
                    <span class="font-medium text-foreground uppercase">{{
                        row.account_type
                    }}</span>
                    <span v-if="row.bank_name"> · {{ row.bank_name }}</span>
                    <span v-if="row.account_number" class="tabular-nums">
                        ({{ row.account_number }})</span
                    >
                </td>

                <td
                    v-else-if="activeTab === 'document_categories'"
                    class="px-4 py-3.5"
                >
                    <StatusBadge
                        :label="row.is_private ? 'Private' : 'Public'"
                        :variant="row.is_private ? 'warning' : 'info'"
                    />
                </td>

                <td
                    v-else-if="activeTab !== 'brands'"
                    class="max-w-md truncate px-4 py-3.5 text-xs text-muted-foreground"
                >
                    {{ row.description || '—' }}
                </td>

                <td class="px-4 py-3.5">
                    <StatusBadge
                        :label="row.is_active ? 'Active' : 'Inactive'"
                        :variant="row.is_active ? 'success' : 'neutral'"
                    />
                </td>

                <td class="px-4 py-3.5 text-right">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="size-9"
                                :aria-label="`Actions for ${row.name}`"
                            >
                                <MoreHorizontal
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @select="openEditModal(row)">
                                <Pencil
                                    class="mr-2 size-4"
                                    aria-hidden="true"
                                />
                                Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem @select="handleToggleActive(row)">
                                <Power class="mr-2 size-4" aria-hidden="true" />
                                {{ row.is_active ? 'Deactivate' : 'Activate' }}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                class="text-destructive focus:text-destructive"
                                @select="deleteCandidate = row"
                            >
                                <Trash2
                                    class="mr-2 size-4"
                                    aria-hidden="true"
                                />
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </td>
            </template>
        </DataTableCard>

        <!-- Create / Edit Modal -->
        <Dialog :open="modalOpen" @update:open="modalOpen = $event">
            <DialogContent class="max-h-[90svh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingItem ? 'Edit' : 'Add' }}
                        {{ currentTabLabel.replace(/s$/, '') }}
                    </DialogTitle>
                    <DialogDescription>
                        Changes apply immediately across dealership forms while
                        preserving historical transaction records.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitForm">
                    <div v-if="activeTab === 'models'" class="space-y-2">
                        <Label for="md_brand"
                            >Vehicle Brand
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="md_brand"
                            v-model.number="form.vehicle_brand_id"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="brand in brands"
                                :key="brand.id"
                                :value="brand.id"
                            >
                                {{ brand.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.vehicle_brand_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="md_name"
                            >Name <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="md_name"
                            v-model="form.name"
                            type="text"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div
                        v-if="
                            [
                                'categories',
                                'conditions',
                                'expense_categories',
                                'charge_categories',
                                'document_categories',
                            ].includes(activeTab)
                        "
                        class="space-y-2"
                    >
                        <Label for="md_description">Description</Label>
                        <textarea
                            id="md_description"
                            v-model="form.description"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div
                        v-if="activeTab === 'conditions'"
                        class="grid gap-4 sm:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <Label for="md_severity"
                                >Sort Order
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="md_severity"
                                v-model.number="form.severity_order"
                                type="number"
                                min="1"
                                max="100"
                                required
                                class="min-h-10"
                            />
                            <InputError :message="form.errors.severity_order" />
                        </div>

                        <div class="space-y-2">
                            <Label for="md_variant"
                                >Badge Style
                                <span class="text-destructive">*</span></Label
                            >
                            <select
                                id="md_variant"
                                v-model="form.badge_variant"
                                class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            >
                                <option value="success">Success (Green)</option>
                                <option value="info">Info (Blue)</option>
                                <option value="warning">Warning (Amber)</option>
                                <option value="danger">Danger (Red)</option>
                                <option value="neutral">Neutral (Slate)</option>
                            </select>
                            <InputError :message="form.errors.badge_variant" />
                        </div>
                    </div>

                    <template v-if="activeTab === 'charge_categories'">
                        <div class="space-y-2">
                            <Label for="md_default_amount"
                                >Suggested Default Amount (₦, optional)</Label
                            >
                            <Input
                                id="md_default_amount"
                                v-model="form.default_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="min-h-10 tabular-nums"
                                placeholder="Leave blank for variable pricing"
                            />
                            <p class="text-xs text-muted-foreground">
                                Staff can always enter a transaction-specific
                                amount when applying this charge.
                            </p>
                            <InputError :message="form.errors.default_amount" />
                        </div>

                        <div class="space-y-2">
                            <Label>Applicable Transaction Types</Label>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <label
                                    v-for="opt in chargeTransactionTypes"
                                    :key="opt.value"
                                    class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-border/70 p-2.5 text-xs"
                                >
                                    <Checkbox
                                        :model-value="
                                            form.applicable_types.includes(
                                                opt.value,
                                            )
                                        "
                                        @update:model-value="
                                            (checked) =>
                                                toggleApplicableType(
                                                    opt.value,
                                                    Boolean(checked),
                                                )
                                        "
                                    />
                                    <span>{{ opt.label }}</span>
                                </label>
                            </div>
                        </div>
                    </template>

                    <template v-if="activeTab === 'payment_methods'">
                        <div class="space-y-2">
                            <Label for="md_code"
                                >Code
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="md_code"
                                v-model="form.code"
                                type="text"
                                required
                                class="min-h-10 font-mono"
                                placeholder="e.g. bank_transfer"
                            />
                            <InputError :message="form.errors.code" />
                        </div>

                        <label
                            class="flex cursor-pointer items-center gap-2.5 text-sm"
                        >
                            <Checkbox
                                :model-value="form.requires_reference"
                                @update:model-value="
                                    (val) =>
                                        (form.requires_reference = Boolean(val))
                                "
                            />
                            <span
                                >Require external transfer / POS reference</span
                            >
                        </label>
                    </template>

                    <template v-if="activeTab === 'financial_accounts'">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="md_account_type"
                                    >Account Type
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <select
                                    id="md_account_type"
                                    v-model="form.account_type"
                                    class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                                >
                                    <option value="bank">Bank Account</option>
                                    <option value="cash">Cash Till</option>
                                    <option value="mobile_money">
                                        Mobile Money / Wallet
                                    </option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <Label for="md_bank_name">Bank Name</Label>
                                <Input
                                    id="md_bank_name"
                                    v-model="form.bank_name"
                                    type="text"
                                    class="min-h-10"
                                    placeholder="e.g. GTBank"
                                />
                            </div>

                            <div class="space-y-2 sm:col-span-2">
                                <Label for="md_account_number"
                                    >Account Number</Label
                                >
                                <Input
                                    id="md_account_number"
                                    v-model="form.account_number"
                                    type="text"
                                    class="min-h-10 tabular-nums"
                                />
                            </div>
                        </div>
                    </template>

                    <label
                        v-if="activeTab === 'document_categories'"
                        class="flex cursor-pointer items-center gap-2.5 text-sm"
                    >
                        <Checkbox
                            :model-value="form.is_private"
                            @update:model-value="
                                (val) => (form.is_private = Boolean(val))
                            "
                        />
                        <span
                            >Store privately (requires permission to view or
                            download)</span
                        >
                    </label>

                    <label
                        class="flex cursor-pointer items-center gap-2.5 border-t border-border/60 pt-3 text-sm"
                    >
                        <Checkbox
                            :model-value="form.is_active"
                            @update:model-value="
                                (val) => (form.is_active = Boolean(val))
                            "
                        />
                        <span>Active and selectable in operational forms</span>
                    </label>

                    <DialogFooter
                        class="flex-col-reverse gap-2 pt-2 sm:flex-row"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            class="min-h-10"
                            @click="modalOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            class="min-h-10"
                            :disabled="form.processing"
                        >
                            <Spinner v-if="form.processing" class="mr-1.5" />
                            {{ editingItem ? 'Save Changes' : 'Create Entry' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            :open="deleteCandidate !== null"
            title="Delete Catalog Entry"
            :description="`Are you sure you want to delete '${deleteCandidate?.name}'? If this entry is linked to historical records, deletion will be blocked and you should deactivate it instead.`"
            confirm-label="Delete"
            destructive
            :processing="deleting"
            @update:open="(open) => !open && (deleteCandidate = null)"
            @confirm="confirmDelete"
        />
    </div>
</template>
