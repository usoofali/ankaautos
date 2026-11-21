<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Archive,
    ArrowLeft,
    Camera,
    FileCheck2,
    History,
    Pencil,
    Star,
    Trash2,
    Upload,
    Wrench,
} from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/anka/ConfirmDialog.vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatusBadge, {
    type StatusVariant,
} from '@/components/anka/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormatting } from '@/composables/useFormatting';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import {
    create as createConsignment,
    show as showConsignment,
} from '@/routes/consignments';
import { show as showOwner } from '@/routes/owners';
import { archive, edit, index } from '@/routes/vehicles';
import { store as storeAttendantLog } from '@/routes/vehicles/attendant-logs';
import { update as updateCondition } from '@/routes/vehicles/condition';
import {
    destroy as deletePhoto,
    primary as setPrimaryPhoto,
    store as uploadPhotos,
} from '@/routes/vehicles/photos';
import { update as updateStatus } from '@/routes/vehicles/status';

type PhotoItem = {
    id: number;
    url: string;
    original_name: string;
    caption: string | null;
    category_label: string;
    is_primary: boolean;
    uploaded_by: string;
    created_at: string | null;
};

type OptionItem = { value: string; label: string };

const props = defineProps<{
    vehicle: {
        id: number;
        reference_number: string;
        slug: string;
        title: string;
        ownership_type: string;
        ownership_label: string;
        brand_name: string;
        model_name: string;
        category_name: string;
        condition_id: number;
        condition_name: string;
        condition_variant: StatusVariant;
        year: number;
        vin: string | null;
        chassis_number: string | null;
        engine_number: string | null;
        registration_number: string | null;
        transmission_label: string;
        fuel_type_label: string;
        drive_type_label: string | null;
        exterior_colour: string;
        interior_colour: string | null;
        mileage: number | null;
        steering_position_label: string;
        doors: number | null;
        status: string;
        status_label: string;
        status_variant: StatusVariant;
        location: string | null;
        received_at: string;
        registered_at: string | null;
        received_by_name: string;
        acquisition_source: string | null;
        acquisition_cost: string | null;
        acquisition_reference: string | null;
        acquired_from: string | null;
        advertised_price: string | null;
        minimum_price: string | null;
        general_description: string | null;
        condition_remarks: string | null;
        mechanical_issues: string | null;
        bodywork_condition: string | null;
        accessories: string | null;
        internal_notes: string | null;
        is_public_listing: boolean;
        duplicate_vin_override_reason: string | null;
        duplicate_vin_overridden_by_name: string | null;
        photos: PhotoItem[];
        status_histories: Array<{
            id: number;
            from_label: string | null;
            to_label: string;
            to_variant: StatusVariant;
            reason: string | null;
            changed_by: string;
            created_at: string | null;
        }>;
        condition_histories: Array<{
            id: number;
            from_name: string | null;
            to_name: string;
            defects_noted: string | null;
            changed_by: string;
            created_at: string | null;
        }>;
        attendant_logs: Array<{
            id: number;
            activity_label: string;
            description: string;
            location_from: string | null;
            location_to: string | null;
            logged_by: string;
            logged_at: string;
        }>;
        active_consignment: {
            id: number;
            reference_number: string;
            status_label: string;
            status_variant: StatusVariant;
            owner_id: number;
            owner_name: string;
            owner_phone: string | null;
            agreed_selling_price: string;
            commission_label: string;
        } | null;
    };
    allowedManualStatuses: OptionItem[];
    canArchive: boolean;
    conditions: Array<{ id: number; name: string }>;
    attendantActivityTypes: OptionItem[];
    photoCategories: OptionItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Inventory', href: index() },
            { title: 'Vehicle Record', href: index() },
        ],
    },
});

const { can } = usePermissions();
const { formatMoney, formatDate, formatDateTime } = useFormatting();

const activeTab = ref<'overview' | 'photos' | 'care' | 'history'>('overview');
const archiveDialogOpen = ref(false);
const archiving = ref(false);

const statusForm = useForm({
    status: props.allowedManualStatuses[0]?.value ?? '',
    reason: '',
});

const conditionForm = useForm({
    vehicle_condition_id: props.vehicle.condition_id,
    defects_noted: '',
});

const attendantForm = useForm({
    activity_type: props.attendantActivityTypes[0]?.value ?? 'washing',
    description: '',
    location_from: props.vehicle.location ?? '',
    location_to: '',
    update_vehicle_location: false,
});

const photoForm = useForm({
    photos: [] as File[],
    category: 'front',
    caption: '',
});

function handlePhotoFiles(event: Event): void {
    const input = event.target as HTMLInputElement;
    photoForm.photos = input.files ? Array.from(input.files) : [];
}

function submitPhotos(): void {
    photoForm.post(uploadPhotos.url(props.vehicle.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            photoForm.reset('photos', 'caption');
        },
    });
}

function submitStatus(): void {
    statusForm.patch(updateStatus.url(props.vehicle.id), {
        preserveScroll: true,
        onSuccess: () => statusForm.reset('reason'),
    });
}

function submitCondition(): void {
    conditionForm.patch(updateCondition.url(props.vehicle.id), {
        preserveScroll: true,
        onSuccess: () => conditionForm.reset('defects_noted'),
    });
}

function submitAttendantLog(): void {
    attendantForm.post(storeAttendantLog.url(props.vehicle.id), {
        preserveScroll: true,
        onSuccess: () => attendantForm.reset('description', 'location_to'),
    });
}

function confirmArchive(): void {
    archiving.value = true;
    router.post(
        archive.url(props.vehicle.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                archiving.value = false;
                archiveDialogOpen.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="`${vehicle.title} (${vehicle.reference_number})`" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="vehicle.title"
            :description="`${vehicle.reference_number} · ${vehicle.ownership_label} · Received ${formatDate(vehicle.received_at)}`"
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Inventory
                    </Link>
                </Button>
                <Button
                    v-if="
                        vehicle.ownership_type === 'consignment' &&
                        !vehicle.active_consignment &&
                        can('consignments.create')
                    "
                    as-child
                    variant="outline"
                    class="min-h-10"
                >
                    <Link
                        :href="
                            createConsignment({
                                query: { vehicle_id: vehicle.id },
                            })
                        "
                    >
                        <FileCheck2 class="mr-1.5 size-4" aria-hidden="true" />
                        Link Consignment
                    </Link>
                </Button>
                <Button v-if="can('vehicles.edit')" as-child class="min-h-10">
                    <Link :href="edit(vehicle.id)">
                        <Pencil class="mr-1.5 size-4" aria-hidden="true" />
                        Edit Vehicle
                    </Link>
                </Button>
                <Button
                    v-if="canArchive"
                    type="button"
                    variant="outline"
                    class="min-h-10 text-destructive"
                    @click="archiveDialogOpen = true"
                >
                    <Archive class="mr-1.5 size-4" aria-hidden="true" />
                    Archive
                </Button>
            </template>
        </PageHeader>

        <!-- Summary Strip -->
        <div
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border/80 bg-card p-4 shadow-xs"
        >
            <div class="flex flex-wrap items-center gap-2">
                <StatusBadge
                    :label="vehicle.status_label"
                    :variant="vehicle.status_variant"
                />
                <StatusBadge
                    :label="vehicle.condition_name"
                    :variant="vehicle.condition_variant"
                />
                <StatusBadge
                    :label="vehicle.ownership_label"
                    :variant="
                        vehicle.ownership_type === 'company_owned'
                            ? 'info'
                            : 'pending'
                    "
                />
            </div>

            <div class="flex flex-wrap items-center gap-4 text-sm">
                <div>
                    <span class="text-xs text-muted-foreground"
                        >Asking Price:
                    </span>
                    <span class="font-bold text-foreground tabular-nums">
                        {{
                            vehicle.advertised_price
                                ? formatMoney(vehicle.advertised_price)
                                : 'Not set'
                        }}
                    </span>
                </div>
                <div v-if="vehicle.acquisition_cost">
                    <span class="text-xs text-muted-foreground"
                        >Acquisition Cost:
                    </span>
                    <span class="font-semibold text-foreground tabular-nums">
                        {{ formatMoney(vehicle.acquisition_cost) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div
            role="tablist"
            class="flex gap-1.5 overflow-x-auto rounded-xl border border-border/80 bg-card p-1.5 shadow-xs"
        >
            <button
                v-for="tab in [
                    { key: 'overview', label: 'Overview & Specs' },
                    {
                        key: 'photos',
                        label: `Photographs (${vehicle.photos.length})`,
                    },
                    {
                        key: 'care',
                        label: `Condition & Care (${vehicle.attendant_logs.length})`,
                    },
                    {
                        key: 'history',
                        label: `Status History (${vehicle.status_histories.length})`,
                    },
                ]"
                :key="tab.key"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.key"
                :class="[
                    'min-h-10 shrink-0 rounded-lg px-4 py-2 text-xs font-medium whitespace-nowrap transition-colors sm:text-sm',
                    activeTab === tab.key
                        ? 'bg-primary text-primary-foreground shadow-xs'
                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                ]"
                @click="
                    activeTab = tab.key as
                        'overview' | 'photos' | 'care' | 'history'
                "
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Tab 1: Overview & Specs -->
        <div
            v-if="activeTab === 'overview'"
            class="grid min-w-0 gap-5 lg:grid-cols-3"
        >
            <div class="min-w-0 space-y-5 lg:col-span-2">
                <FormSectionCard
                    title="Vehicle Identification & Specifications"
                >
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Make / Model / Year
                            </dt>
                            <dd class="font-semibold text-foreground">
                                {{ vehicle.brand_name }}
                                {{ vehicle.model_name }} ({{ vehicle.year }})
                            </dd>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Body Category
                            </dt>
                            <dd class="font-medium text-foreground">
                                {{ vehicle.category_name }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                VIN / Chassis Number
                            </dt>
                            <dd class="font-mono text-xs text-foreground">
                                {{ vehicle.vin || '—' }} /
                                {{ vehicle.chassis_number || '—' }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Engine / Plate Number
                            </dt>
                            <dd class="font-mono text-xs text-foreground">
                                {{ vehicle.engine_number || '—' }} /
                                {{ vehicle.registration_number || '—' }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Transmission & Fuel
                            </dt>
                            <dd class="font-medium text-foreground">
                                {{ vehicle.transmission_label }} ·
                                {{ vehicle.fuel_type_label }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Colours
                            </dt>
                            <dd class="font-medium text-foreground">
                                {{ vehicle.exterior_colour }}
                                <span v-if="vehicle.interior_colour">
                                    / {{ vehicle.interior_colour }}
                                </span>
                            </dd>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Mileage & Steering
                            </dt>
                            <dd
                                class="font-medium text-foreground tabular-nums"
                            >
                                {{
                                    vehicle.mileage
                                        ? `${vehicle.mileage.toLocaleString()} km`
                                        : '—'
                                }}
                                · {{ vehicle.steering_position_label }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Current Location
                            </dt>
                            <dd class="font-medium text-foreground">
                                {{ vehicle.location || 'Unassigned' }}
                            </dd>
                        </div>
                    </dl>
                </FormSectionCard>

                <FormSectionCard title="Descriptions, Defects & Internal Notes">
                    <div class="space-y-4 text-sm">
                        <div>
                            <h3
                                class="text-xs font-semibold text-muted-foreground uppercase"
                            >
                                Public Description
                            </h3>
                            <p class="mt-1 text-foreground">
                                {{
                                    vehicle.general_description ||
                                    'No public description entered yet.'
                                }}
                            </p>
                        </div>
                        <div v-if="vehicle.accessories">
                            <h3
                                class="text-xs font-semibold text-muted-foreground uppercase"
                            >
                                Accessories & Features
                            </h3>
                            <p class="mt-1 text-foreground">
                                {{ vehicle.accessories }}
                            </p>
                        </div>
                        <div
                            v-if="
                                vehicle.mechanical_issues ||
                                vehicle.bodywork_condition
                            "
                            class="grid gap-3 sm:grid-cols-2"
                        >
                            <div class="rounded-lg border border-border p-3">
                                <h3
                                    class="text-xs font-semibold text-muted-foreground uppercase"
                                >
                                    Known Mechanical Issues
                                </h3>
                                <p class="mt-1 text-xs text-foreground">
                                    {{
                                        vehicle.mechanical_issues ||
                                        'None noted'
                                    }}
                                </p>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <h3
                                    class="text-xs font-semibold text-muted-foreground uppercase"
                                >
                                    Bodywork Condition
                                </h3>
                                <p class="mt-1 text-xs text-foreground">
                                    {{
                                        vehicle.bodywork_condition ||
                                        'None noted'
                                    }}
                                </p>
                            </div>
                        </div>
                        <div
                            v-if="vehicle.internal_notes"
                            class="rounded-lg border border-amber-200 bg-amber-50/70 p-3 dark:border-amber-800/50 dark:bg-amber-950/30"
                        >
                            <h3
                                class="text-xs font-semibold text-amber-800 uppercase dark:text-amber-300"
                            >
                                Internal Notes (Never Shown Publicly)
                            </h3>
                            <p
                                class="mt-1 text-xs text-amber-950 dark:text-amber-200"
                            >
                                {{ vehicle.internal_notes }}
                            </p>
                        </div>
                    </div>
                </FormSectionCard>
            </div>

            <div class="space-y-5">
                <!-- Consignment Link Card -->
                <FormSectionCard
                    v-if="vehicle.ownership_type === 'consignment'"
                    title="Consignment Agreement"
                >
                    <div
                        v-if="vehicle.active_consignment"
                        class="space-y-3 text-sm"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-semibold">{{
                                vehicle.active_consignment.reference_number
                            }}</span>
                            <StatusBadge
                                :label="vehicle.active_consignment.status_label"
                                :variant="
                                    vehicle.active_consignment.status_variant
                                "
                            />
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Owner</p>
                            <Link
                                :href="
                                    showOwner(
                                        vehicle.active_consignment.owner_id,
                                    )
                                "
                                class="font-semibold text-foreground hover:underline"
                            >
                                {{ vehicle.active_consignment.owner_name }}
                            </Link>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Agreed Price · Commission
                            </p>
                            <p class="font-medium tabular-nums">
                                {{
                                    formatMoney(
                                        vehicle.active_consignment
                                            .agreed_selling_price,
                                    )
                                }}
                                ·
                                {{
                                    vehicle.active_consignment.commission_label
                                }}
                            </p>
                        </div>
                        <Button
                            as-child
                            variant="outline"
                            class="min-h-10 w-full"
                        >
                            <Link
                                :href="
                                    showConsignment(
                                        vehicle.active_consignment.id,
                                    )
                                "
                            >
                                Open Consignment Record
                            </Link>
                        </Button>
                    </div>
                    <div v-else class="space-y-3 text-sm text-muted-foreground">
                        <p>
                            No consignment agreement has been linked to this
                            vehicle yet.
                        </p>
                        <Button
                            v-if="can('consignments.create')"
                            as-child
                            class="min-h-10 w-full"
                        >
                            <Link
                                :href="
                                    createConsignment({
                                        query: { vehicle_id: vehicle.id },
                                    })
                                "
                            >
                                Create Consignment Agreement
                            </Link>
                        </Button>
                    </div>
                </FormSectionCard>

                <!-- Quick Operational Status Transition -->
                <FormSectionCard
                    v-if="
                        can('vehicles.change_status') &&
                        allowedManualStatuses.length > 0
                    "
                    title="Update Operational Status"
                >
                    <form class="space-y-3" @submit.prevent="submitStatus">
                        <div class="space-y-1.5">
                            <Label for="new_status">New Status</Label>
                            <select
                                id="new_status"
                                v-model="statusForm.status"
                                required
                                class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            >
                                <option
                                    v-for="opt in allowedManualStatuses"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                            <InputError :message="statusForm.errors.status" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="status_reason">Reason / Note</Label>
                            <Input
                                id="status_reason"
                                v-model="statusForm.reason"
                                class="min-h-10"
                                placeholder="e.g. Detailing completed"
                            />
                        </div>
                        <Button
                            type="submit"
                            class="min-h-10 w-full"
                            :disabled="statusForm.processing"
                        >
                            <Spinner
                                v-if="statusForm.processing"
                                class="mr-1.5"
                            />
                            Update Status
                        </Button>
                    </form>
                </FormSectionCard>

                <FormSectionCard title="Acquisition & Audit Metadata">
                    <dl class="space-y-2 text-xs">
                        <div class="flex justify-between gap-2">
                            <dt class="text-muted-foreground">Received By</dt>
                            <dd class="font-medium text-foreground">
                                {{ vehicle.received_by_name }}
                            </dd>
                        </div>
                        <div
                            v-if="vehicle.acquired_from"
                            class="flex justify-between gap-2"
                        >
                            <dt class="text-muted-foreground">Acquired From</dt>
                            <dd class="font-medium text-foreground">
                                {{ vehicle.acquired_from }}
                            </dd>
                        </div>
                        <div
                            v-if="vehicle.duplicate_vin_override_reason"
                            class="rounded-md bg-amber-50 p-2 text-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
                        >
                            <strong>Returning VIN Override:</strong>
                            {{ vehicle.duplicate_vin_override_reason }}
                            ({{ vehicle.duplicate_vin_overridden_by_name }})
                        </div>
                    </dl>
                </FormSectionCard>
            </div>
        </div>

        <!-- Tab 2: Photo Gallery -->
        <div v-else-if="activeTab === 'photos'" class="space-y-5">
            <FormSectionCard
                v-if="can('vehicles.edit')"
                title="Upload Vehicle Photographs"
                description="Upload up to 10 JPG, PNG, or WebP photos (max 5MB each). The first photo becomes the primary image automatically."
            >
                <form
                    class="grid gap-4 sm:grid-cols-3"
                    @submit.prevent="submitPhotos"
                >
                    <div class="space-y-1.5">
                        <Label for="photo_files">Select Images</Label>
                        <input
                            id="photo_files"
                            type="file"
                            accept="image/*"
                            multiple
                            required
                            class="block w-full rounded-md border border-input bg-background px-3 py-2 text-xs text-foreground file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1 file:text-xs file:font-medium file:text-primary-foreground"
                            @change="handlePhotoFiles"
                        />
                        <InputError :message="photoForm.errors.photos" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="photo_category">View Category</Label>
                        <select
                            id="photo_category"
                            v-model="photoForm.category"
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="cat in photoCategories"
                                :key="cat.value"
                                :value="cat.value"
                            >
                                {{ cat.label }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="photo_caption">Caption (Optional)</Label>
                        <div class="flex gap-2">
                            <Input
                                id="photo_caption"
                                v-model="photoForm.caption"
                                class="min-h-10"
                                placeholder="e.g. Front 3/4 view"
                            />
                            <Button
                                type="submit"
                                class="min-h-10 shrink-0"
                                :disabled="
                                    photoForm.processing ||
                                    photoForm.photos.length === 0
                                "
                            >
                                <Spinner
                                    v-if="photoForm.processing"
                                    class="mr-1.5"
                                />
                                <Upload
                                    v-else
                                    class="mr-1.5 size-4"
                                    aria-hidden="true"
                                />
                                Upload
                            </Button>
                        </div>
                    </div>
                </form>
            </FormSectionCard>

            <div
                v-if="vehicle.photos.length === 0"
                class="rounded-xl border border-dashed border-border bg-card p-12 text-center"
            >
                <Camera
                    class="mx-auto mb-3 size-8 text-muted-foreground"
                    aria-hidden="true"
                />
                <p class="text-sm font-medium text-foreground">
                    No photographs uploaded yet
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Upload exterior, interior, dashboard, and engine photos for
                    inventory and social media marketing.
                </p>
            </div>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="photo in vehicle.photos"
                    :key="photo.id"
                    class="overflow-hidden rounded-xl border border-border/80 bg-card shadow-xs"
                >
                    <div class="relative aspect-video bg-muted">
                        <img
                            :src="photo.url"
                            :alt="photo.caption || photo.category_label"
                            class="h-full w-full object-cover"
                        />
                        <span
                            v-if="photo.is_primary"
                            class="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full bg-brand-amber px-2.5 py-0.5 text-xs font-semibold text-brand-amber-foreground shadow-xs"
                        >
                            <Star class="size-3" aria-hidden="true" />
                            Primary Photo
                        </span>
                    </div>
                    <div class="space-y-2 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-foreground">
                                {{ photo.category_label }}
                            </span>
                            <span class="text-[11px] text-muted-foreground">
                                by {{ photo.uploaded_by }}
                            </span>
                        </div>
                        <p
                            v-if="photo.caption"
                            class="text-xs text-muted-foreground"
                        >
                            {{ photo.caption }}
                        </p>
                        <div
                            v-if="can('vehicles.edit')"
                            class="flex gap-2 pt-1"
                        >
                            <Button
                                v-if="!photo.is_primary"
                                type="button"
                                variant="outline"
                                size="sm"
                                class="min-h-8 flex-1"
                                @click="
                                    router.patch(
                                        setPrimaryPhoto.url({
                                            vehicle: vehicle.id,
                                            photo: photo.id,
                                        }),
                                        {},
                                        { preserveScroll: true },
                                    )
                                "
                            >
                                <Star
                                    class="mr-1 size-3.5"
                                    aria-hidden="true"
                                />
                                Set Primary
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="min-h-8 text-destructive"
                                @click="
                                    router.delete(
                                        deletePhoto.url({
                                            vehicle: vehicle.id,
                                            photo: photo.id,
                                        }),
                                        { preserveScroll: true },
                                    )
                                "
                            >
                                <Trash2 class="size-3.5" aria-hidden="true" />
                            </Button>
                        </div>
                    </div>
                </article>
            </div>
        </div>

        <!-- Tab 3: Condition & Attendant Activity -->
        <div v-else-if="activeTab === 'care'" class="grid gap-5 lg:grid-cols-2">
            <FormSectionCard
                v-if="can('vehicles.log_attendant_activity')"
                title="Log Physical Vehicle Activity"
                description="Record washing, maintenance, preparation progress, or stand bay movement."
            >
                <form class="space-y-3" @submit.prevent="submitAttendantLog">
                    <div class="space-y-1.5">
                        <Label for="att_type">Activity Type</Label>
                        <select
                            id="att_type"
                            v-model="attendantForm.activity_type"
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="opt in attendantActivityTypes"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="att_desc"
                            >Details
                            <span class="text-destructive">*</span></Label
                        >
                        <textarea
                            id="att_desc"
                            v-model="attendantForm.description"
                            rows="2"
                            required
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            placeholder="Describe work completed or observations..."
                        />
                        <InputError
                            :message="attendantForm.errors.description"
                        />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="loc_from">From Location</Label>
                            <Input
                                id="loc_from"
                                v-model="attendantForm.location_from"
                                class="min-h-10"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="loc_to">To Location</Label>
                            <Input
                                id="loc_to"
                                v-model="attendantForm.location_to"
                                class="min-h-10"
                            />
                        </div>
                    </div>
                    <label
                        class="flex cursor-pointer items-center gap-2 text-xs"
                    >
                        <Checkbox
                            :model-value="attendantForm.update_vehicle_location"
                            @update:model-value="
                                (v) =>
                                    (attendantForm.update_vehicle_location =
                                        Boolean(v))
                            "
                        />
                        <span>Update vehicle's current stand location</span>
                    </label>
                    <Button
                        type="submit"
                        class="min-h-10 w-full"
                        :disabled="attendantForm.processing"
                    >
                        <Wrench class="mr-1.5 size-4" aria-hidden="true" />
                        Save Activity Log
                    </Button>
                </form>
            </FormSectionCard>

            <FormSectionCard
                v-if="
                    can('vehicles.edit') ||
                    can('vehicles.log_attendant_activity')
                "
                title="Update Vehicle Condition"
                description="Condition changes preserve full historical audit records."
            >
                <form class="space-y-3" @submit.prevent="submitCondition">
                    <div class="space-y-1.5">
                        <Label for="cond_select">Condition Rating</Label>
                        <select
                            id="cond_select"
                            v-model.number="conditionForm.vehicle_condition_id"
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="c in conditions"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="defects_noted"
                            >Defects / Repair Notes</Label
                        >
                        <textarea
                            id="defects_noted"
                            v-model="conditionForm.defects_noted"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            placeholder="Note any scratches, warning lights, or repairs required..."
                        />
                    </div>
                    <Button
                        type="submit"
                        class="min-h-10 w-full"
                        :disabled="conditionForm.processing"
                    >
                        Record Condition Update
                    </Button>
                </form>
            </FormSectionCard>

            <FormSectionCard
                title="Physical Activity Timeline"
                class="lg:col-span-2"
            >
                <div
                    v-if="vehicle.attendant_logs.length === 0"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    No washing, maintenance, or movement activities logged yet.
                </div>
                <ol v-else class="divide-y divide-border/60">
                    <li
                        v-for="log in vehicle.attendant_logs"
                        :key="log.id"
                        class="py-3 text-sm"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <StatusBadge
                                :label="log.activity_label"
                                variant="info"
                            />
                            <span
                                class="text-xs text-muted-foreground tabular-nums"
                            >
                                {{ formatDateTime(log.logged_at) }} ·
                                {{ log.logged_by }}
                            </span>
                        </div>
                        <p class="mt-1.5 text-foreground">
                            {{ log.description }}
                        </p>
                        <p
                            v-if="log.location_to"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            Moved: {{ log.location_from || 'Unknown' }} →
                            {{ log.location_to }}
                        </p>
                    </li>
                </ol>
            </FormSectionCard>
        </div>

        <!-- Tab 4: Status & Condition History -->
        <div v-else class="grid gap-5 lg:grid-cols-2">
            <FormSectionCard title="Operational Status History">
                <ol class="divide-y divide-border/60">
                    <li
                        v-for="item in vehicle.status_histories"
                        :key="item.id"
                        class="space-y-1 py-3 text-sm"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <div class="flex items-center gap-2">
                                <History
                                    class="size-4 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <span
                                    v-if="item.from_label"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ item.from_label }} →
                                </span>
                                <StatusBadge
                                    :label="item.to_label"
                                    :variant="item.to_variant"
                                />
                            </div>
                            <span
                                class="text-xs text-muted-foreground tabular-nums"
                            >
                                {{ formatDateTime(item.created_at) }}
                            </span>
                        </div>
                        <p
                            v-if="item.reason"
                            class="text-xs text-muted-foreground"
                        >
                            {{ item.reason }}
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            Updated by {{ item.changed_by }}
                        </p>
                    </li>
                </ol>
            </FormSectionCard>

            <FormSectionCard title="Condition Change History">
                <ol class="divide-y divide-border/60">
                    <li
                        v-for="item in vehicle.condition_histories"
                        :key="item.id"
                        class="space-y-1 py-3 text-sm"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <span class="font-medium text-foreground">
                                {{
                                    item.from_name
                                        ? `${item.from_name} → ${item.to_name}`
                                        : item.to_name
                                }}
                            </span>
                            <span
                                class="text-xs text-muted-foreground tabular-nums"
                            >
                                {{ formatDateTime(item.created_at) }}
                            </span>
                        </div>
                        <p
                            v-if="item.defects_noted"
                            class="text-xs text-muted-foreground"
                        >
                            {{ item.defects_noted }}
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            Recorded by {{ item.changed_by }}
                        </p>
                    </li>
                </ol>
            </FormSectionCard>
        </div>

        <ConfirmDialog
            :open="archiveDialogOpen"
            title="Archive Vehicle Record"
            :description="`Archive ${vehicle.reference_number}? Archived records are removed from active inventory while preserving all sales, expenses, and audit history.`"
            confirm-label="Archive Vehicle"
            destructive
            :processing="archiving"
            @update:open="archiveDialogOpen = $event"
            @confirm="confirmArchive"
        />
    </div>
</template>
