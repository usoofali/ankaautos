<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import { computed, watch } from 'vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { create, index, store } from '@/routes/vehicles';

type LookupItem = { id: number; name: string };
type ModelItem = { id: number; vehicle_brand_id: number; name: string };
type OptionItem = { value: string; label: string };

const props = defineProps<{
    brands: LookupItem[];
    models: ModelItem[];
    categories: LookupItem[];
    conditions: LookupItem[];
    ownershipOptions: OptionItem[];
    transmissionOptions: OptionItem[];
    fuelOptions: OptionItem[];
    driveOptions: OptionItem[];
    steeringOptions: OptionItem[];
    initialStatusOptions: OptionItem[];
    canOverrideDuplicateVin: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Inventory', href: index() },
            { title: 'Register Vehicle', href: create() },
        ],
    },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    ownership_type: 'company_owned',
    vehicle_brand_id: props.brands[0]?.id ?? 0,
    vehicle_model_id: 0,
    vehicle_category_id: props.categories[0]?.id ?? 0,
    vehicle_condition_id:
        props.conditions[1]?.id ?? props.conditions[0]?.id ?? 0,
    year: new Date().getFullYear(),
    vin: '',
    chassis_number: '',
    engine_number: '',
    registration_number: '',
    transmission: 'automatic',
    fuel_type: 'petrol',
    drive_type: 'fwd',
    exterior_colour: '',
    interior_colour: '',
    mileage: '',
    steering_position: 'left_hand',
    doors: '4',
    status: 'available',
    location: 'Main Showroom',
    received_at: today,
    acquisition_source: '',
    acquisition_cost: '',
    acquisition_reference: '',
    acquired_from: '',
    advertised_price: '',
    minimum_price: '',
    general_description: '',
    condition_remarks: '',
    mechanical_issues: '',
    bodywork_condition: '',
    accessories: '',
    internal_notes: '',
    is_public_listing: true,
    duplicate_vin_override_reason: '',
});

const filteredModels = computed(() =>
    props.models.filter((m) => m.vehicle_brand_id === form.vehicle_brand_id),
);

watch(
    filteredModels,
    (list) => {
        if (!list.some((m) => m.id === form.vehicle_model_id)) {
            form.vehicle_model_id = list[0]?.id ?? 0;
        }
    },
    { immediate: true },
);

function submit(): void {
    form.post(store.url());
}
</script>

<template>
    <Head title="Register Vehicle" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Register Vehicle"
            description="Add a new company-owned or third-party consignment vehicle to inventory. A unique reference number will be generated automatically."
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Inventory
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="max-w-5xl space-y-5" @submit.prevent="submit">
            <FormSectionCard
                title="1. Ownership & Basic Classification"
                description="Choose ownership classification carefully—company-owned vehicles track acquisition cost and profitability, while consignment vehicles link to owner agreements."
            >
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="ownership_type"
                            >Ownership Classification
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="ownership_type"
                            v-model="form.ownership_type"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="opt in ownershipOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.ownership_type" />
                    </div>

                    <div class="space-y-2">
                        <Label for="vehicle_brand_id"
                            >Make / Brand
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="vehicle_brand_id"
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
                        <Label for="vehicle_model_id"
                            >Model
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="vehicle_model_id"
                            v-model.number="form.vehicle_model_id"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="model in filteredModels"
                                :key="model.id"
                                :value="model.id"
                            >
                                {{ model.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.vehicle_model_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="year"
                            >Year <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="year"
                            v-model.number="form.year"
                            type="number"
                            min="1970"
                            :max="new Date().getFullYear() + 2"
                            required
                            class="min-h-10 tabular-nums"
                        />
                        <InputError :message="form.errors.year" />
                    </div>

                    <div class="space-y-2">
                        <Label for="vehicle_category_id"
                            >Body Category
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="vehicle_category_id"
                            v-model.number="form.vehicle_category_id"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="cat in categories"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                        <InputError
                            :message="form.errors.vehicle_category_id"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="vehicle_condition_id"
                            >Initial Condition
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="vehicle_condition_id"
                            v-model.number="form.vehicle_condition_id"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="cond in conditions"
                                :key="cond.id"
                                :value="cond.id"
                            >
                                {{ cond.name }}
                            </option>
                        </select>
                        <InputError
                            :message="form.errors.vehicle_condition_id"
                        />
                    </div>
                </div>
            </FormSectionCard>

            <FormSectionCard
                title="2. Identifiers & Specifications"
                description="VIN and chassis numbers are automatically checked against active inventory to prevent duplicates."
            >
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="vin">VIN</Label>
                        <Input
                            id="vin"
                            v-model="form.vin"
                            type="text"
                            class="min-h-10 font-mono uppercase"
                            placeholder="17-character VIN"
                        />
                        <InputError :message="form.errors.vin" />
                    </div>

                    <div class="space-y-2">
                        <Label for="chassis_number">Chassis Number</Label>
                        <Input
                            id="chassis_number"
                            v-model="form.chassis_number"
                            type="text"
                            class="min-h-10 font-mono uppercase"
                        />
                        <InputError :message="form.errors.chassis_number" />
                    </div>

                    <div class="space-y-2">
                        <Label for="engine_number">Engine Number</Label>
                        <Input
                            id="engine_number"
                            v-model="form.engine_number"
                            type="text"
                            class="min-h-10 font-mono uppercase"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="registration_number"
                            >Plate / Reg Number</Label
                        >
                        <Input
                            id="registration_number"
                            v-model="form.registration_number"
                            type="text"
                            class="min-h-10 font-mono uppercase"
                            placeholder="e.g. ABC-123-XY"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="exterior_colour"
                            >Exterior Colour
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="exterior_colour"
                            v-model="form.exterior_colour"
                            type="text"
                            required
                            class="min-h-10"
                            placeholder="e.g. Pearl White"
                        />
                        <InputError :message="form.errors.exterior_colour" />
                    </div>

                    <div class="space-y-2">
                        <Label for="interior_colour">Interior Colour</Label>
                        <Input
                            id="interior_colour"
                            v-model="form.interior_colour"
                            type="text"
                            class="min-h-10"
                            placeholder="e.g. Black Leather"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="transmission"
                            >Transmission
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="transmission"
                            v-model="form.transmission"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="opt in transmissionOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="fuel_type"
                            >Fuel Type
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="fuel_type"
                            v-model="form.fuel_type"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="opt in fuelOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="drive_type">Drive Type</Label>
                        <select
                            id="drive_type"
                            v-model="form.drive_type"
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option value="">Not specified</option>
                            <option
                                v-for="opt in driveOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="mileage">Mileage (km)</Label>
                        <Input
                            id="mileage"
                            v-model="form.mileage"
                            type="number"
                            min="0"
                            class="min-h-10 tabular-nums"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="steering_position">Steering Position</Label>
                        <select
                            id="steering_position"
                            v-model="form.steering_position"
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="opt in steeringOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="doors">Doors</Label>
                        <Input
                            id="doors"
                            v-model="form.doors"
                            type="number"
                            min="2"
                            max="6"
                            class="min-h-10 tabular-nums"
                        />
                    </div>

                    <div
                        v-if="canOverrideDuplicateVin"
                        class="space-y-2 sm:col-span-2 lg:col-span-3"
                    >
                        <Label for="duplicate_vin_override_reason"
                            >Returning Vehicle Override Reason (Managers Only —
                            Optional)</Label
                        >
                        <Input
                            id="duplicate_vin_override_reason"
                            v-model="form.duplicate_vin_override_reason"
                            type="text"
                            class="min-h-10"
                            placeholder="Only required if re-registering a previously Sold or Withdrawn VIN"
                        />
                        <InputError
                            :message="form.errors.duplicate_vin_override_reason"
                        />
                    </div>
                </div>
            </FormSectionCard>

            <FormSectionCard
                title="3. Reception, Status & Pricing"
                description="Record stand arrival date, location, asking price, and company acquisition cost (company-owned vehicles only)."
            >
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="received_at"
                            >Date Received
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

                    <div class="space-y-2">
                        <Label for="status"
                            >Initial Operational Status
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="status"
                            v-model="form.status"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        >
                            <option
                                v-for="opt in initialStatusOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="location">Stand / Parking Location</Label>
                        <Input
                            id="location"
                            v-model="form.location"
                            type="text"
                            class="min-h-10"
                            placeholder="e.g. Showroom Bay 3"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="advertised_price"
                            >Advertised Asking Price (₦)</Label
                        >
                        <Input
                            id="advertised_price"
                            v-model="form.advertised_price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="min-h-10 tabular-nums"
                        />
                        <InputError :message="form.errors.advertised_price" />
                    </div>

                    <div class="space-y-2">
                        <Label for="minimum_price">Minimum Price (₦)</Label>
                        <Input
                            id="minimum_price"
                            v-model="form.minimum_price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="min-h-10 tabular-nums"
                        />
                    </div>

                    <template v-if="form.ownership_type === 'company_owned'">
                        <div class="space-y-2">
                            <Label for="acquisition_cost"
                                >Acquisition Cost (₦)</Label
                            >
                            <Input
                                id="acquisition_cost"
                                v-model="form.acquisition_cost"
                                type="number"
                                step="0.01"
                                min="0"
                                class="min-h-10 tabular-nums"
                            />
                            <InputError
                                :message="form.errors.acquisition_cost"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="acquired_from">Acquired From</Label>
                            <Input
                                id="acquired_from"
                                v-model="form.acquired_from"
                                type="text"
                                class="min-h-10"
                                placeholder="Seller name / auction / importer"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="acquisition_source"
                                >Acquisition Source</Label
                            >
                            <Input
                                id="acquisition_source"
                                v-model="form.acquisition_source"
                                type="text"
                                class="min-h-10"
                                placeholder="e.g. Direct Purchase, Import, Trade-in"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="acquisition_reference"
                                >Purchase Reference</Label
                            >
                            <Input
                                id="acquisition_reference"
                                v-model="form.acquisition_reference"
                                type="text"
                                class="min-h-10"
                            />
                        </div>
                    </template>
                </div>
            </FormSectionCard>

            <FormSectionCard
                title="4. Public Description vs. Internal Notes"
                description="Internal administrative notes are strictly hidden from public listings and advertisements."
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="general_description"
                            >Public General Description</Label
                        >
                        <textarea
                            id="general_description"
                            v-model="form.general_description"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            placeholder="Highlights suitable for customer listings and social advertisements..."
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="accessories">Accessories & Features</Label>
                        <textarea
                            id="accessories"
                            v-model="form.accessories"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            placeholder="Sunroof, reverse camera, leather interior, thumbstart..."
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="condition_remarks">Condition Remarks</Label>
                        <textarea
                            id="condition_remarks"
                            v-model="form.condition_remarks"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="mechanical_issues"
                            >Known Mechanical Issues</Label
                        >
                        <textarea
                            id="mechanical_issues"
                            v-model="form.mechanical_issues"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="bodywork_condition"
                            >Bodywork Condition</Label
                        >
                        <textarea
                            id="bodywork_condition"
                            v-model="form.bodywork_condition"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="internal_notes"
                            >Internal Administrative Notes (Staff Only)</Label
                        >
                        <textarea
                            id="internal_notes"
                            v-model="form.internal_notes"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            placeholder="Private dealership notes never shown on public pages..."
                        />
                    </div>

                    <label
                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-border/70 p-3 sm:col-span-2"
                    >
                        <Checkbox
                            :model-value="form.is_public_listing"
                            @update:model-value="
                                (val) => (form.is_public_listing = Boolean(val))
                            "
                        />
                        <div>
                            <span class="block text-sm font-medium"
                                >Publish on Public Vehicle Catalog</span
                            >
                            <span class="block text-xs text-muted-foreground">
                                Show safe vehicle specs, photos, asking price,
                                and live availability on the storefront.
                            </span>
                        </div>
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
                        Register Vehicle
                    </Button>
                </template>
            </FormSectionCard>
        </form>
    </div>
</template>
