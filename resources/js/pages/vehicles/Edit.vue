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
import { index, show, update } from '@/routes/vehicles';

type LookupItem = { id: number; name: string };
type ModelItem = { id: number; vehicle_brand_id: number; name: string };
type OptionItem = { value: string; label: string };

const props = defineProps<{
    vehicle: {
        id: number;
        reference_number: string;
        ownership_type: string;
        ownership_label: string;
        vehicle_brand_id: number;
        vehicle_model_id: number;
        vehicle_category_id: number;
        vehicle_condition_id: number;
        year: number;
        vin: string;
        chassis_number: string;
        engine_number: string;
        registration_number: string;
        transmission: string;
        fuel_type: string;
        drive_type: string;
        exterior_colour: string;
        interior_colour: string;
        mileage: number | string;
        steering_position: string;
        doors: number | string;
        location: string;
        received_at: string;
        acquisition_source: string;
        acquisition_cost: string;
        acquisition_reference: string;
        acquired_from: string;
        advertised_price: string;
        minimum_price: string;
        general_description: string;
        condition_remarks: string;
        mechanical_issues: string;
        bodywork_condition: string;
        accessories: string;
        internal_notes: string;
        is_public_listing: boolean;
    };
    brands: LookupItem[];
    models: ModelItem[];
    categories: LookupItem[];
    conditions: LookupItem[];
    transmissionOptions: OptionItem[];
    fuelOptions: OptionItem[];
    driveOptions: OptionItem[];
    steeringOptions: OptionItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vehicle Inventory', href: index() },
            { title: 'Edit Vehicle', href: index() },
        ],
    },
});

const form = useForm({
    vehicle_brand_id: props.vehicle.vehicle_brand_id,
    vehicle_model_id: props.vehicle.vehicle_model_id,
    vehicle_category_id: props.vehicle.vehicle_category_id,
    vehicle_condition_id: props.vehicle.vehicle_condition_id,
    year: props.vehicle.year,
    vin: props.vehicle.vin,
    chassis_number: props.vehicle.chassis_number,
    engine_number: props.vehicle.engine_number,
    registration_number: props.vehicle.registration_number,
    transmission: props.vehicle.transmission,
    fuel_type: props.vehicle.fuel_type,
    drive_type: props.vehicle.drive_type,
    exterior_colour: props.vehicle.exterior_colour,
    interior_colour: props.vehicle.interior_colour,
    mileage: String(props.vehicle.mileage ?? ''),
    steering_position: props.vehicle.steering_position,
    doors: String(props.vehicle.doors ?? ''),
    location: props.vehicle.location,
    received_at: props.vehicle.received_at,
    acquisition_source: props.vehicle.acquisition_source,
    acquisition_cost: props.vehicle.acquisition_cost,
    acquisition_reference: props.vehicle.acquisition_reference,
    acquired_from: props.vehicle.acquired_from,
    advertised_price: props.vehicle.advertised_price,
    minimum_price: props.vehicle.minimum_price,
    general_description: props.vehicle.general_description,
    condition_remarks: props.vehicle.condition_remarks,
    mechanical_issues: props.vehicle.mechanical_issues,
    bodywork_condition: props.vehicle.bodywork_condition,
    accessories: props.vehicle.accessories,
    internal_notes: props.vehicle.internal_notes,
    is_public_listing: props.vehicle.is_public_listing,
});

const filteredModels = computed(() =>
    props.models.filter((m) => m.vehicle_brand_id === form.vehicle_brand_id),
);

watch(filteredModels, (list) => {
    if (!list.some((m) => m.id === form.vehicle_model_id)) {
        form.vehicle_model_id = list[0]?.id ?? 0;
    }
});

function submit(): void {
    form.put(update.url(props.vehicle.id));
}
</script>

<template>
    <Head :title="`Edit ${vehicle.reference_number}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="`Edit ${vehicle.reference_number}`"
            :description="`${vehicle.ownership_label} vehicle specifications and pricing.`"
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="show(vehicle.id)">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Record
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="max-w-5xl space-y-5" @submit.prevent="submit">
            <FormSectionCard title="Specifications & Classification">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="vehicle_brand_id">Make / Brand</Label>
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
                    </div>

                    <div class="space-y-2">
                        <Label for="vehicle_model_id">Model</Label>
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
                    </div>

                    <div class="space-y-2">
                        <Label for="year">Year</Label>
                        <Input
                            id="year"
                            v-model.number="form.year"
                            type="number"
                            required
                            class="min-h-10 tabular-nums"
                        />
                        <InputError :message="form.errors.year" />
                    </div>

                    <div class="space-y-2">
                        <Label for="vehicle_category_id">Body Category</Label>
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
                    </div>

                    <div class="space-y-2">
                        <Label for="vehicle_condition_id">Condition</Label>
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
                    </div>

                    <div class="space-y-2">
                        <Label for="exterior_colour">Exterior Colour</Label>
                        <Input
                            id="exterior_colour"
                            v-model="form.exterior_colour"
                            required
                            class="min-h-10"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="vin">VIN</Label>
                        <Input
                            id="vin"
                            v-model="form.vin"
                            class="min-h-10 font-mono uppercase"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="chassis_number">Chassis Number</Label>
                        <Input
                            id="chassis_number"
                            v-model="form.chassis_number"
                            class="min-h-10 font-mono uppercase"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="registration_number">Plate Number</Label>
                        <Input
                            id="registration_number"
                            v-model="form.registration_number"
                            class="min-h-10 font-mono uppercase"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="transmission">Transmission</Label>
                        <select
                            id="transmission"
                            v-model="form.transmission"
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
                        <Label for="fuel_type">Fuel Type</Label>
                        <select
                            id="fuel_type"
                            v-model="form.fuel_type"
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
                        <Label for="mileage">Mileage (km)</Label>
                        <Input
                            id="mileage"
                            v-model="form.mileage"
                            type="number"
                            min="0"
                            class="min-h-10 tabular-nums"
                        />
                    </div>
                </div>
            </FormSectionCard>

            <FormSectionCard title="Location, Pricing & Notes">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="received_at">Date Received</Label>
                        <Input
                            id="received_at"
                            v-model="form.received_at"
                            type="date"
                            required
                            class="min-h-10"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="location">Stand Location</Label>
                        <Input
                            id="location"
                            v-model="form.location"
                            class="min-h-10"
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

                    <template v-if="vehicle.ownership_type === 'company_owned'">
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
                        </div>

                        <div class="space-y-2">
                            <Label for="acquired_from">Acquired From</Label>
                            <Input
                                id="acquired_from"
                                v-model="form.acquired_from"
                                class="min-h-10"
                            />
                        </div>
                    </template>

                    <div class="space-y-2 sm:col-span-2 lg:col-span-3">
                        <Label for="general_description"
                            >Public Description</Label
                        >
                        <textarea
                            id="general_description"
                            v-model="form.general_description"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                    </div>

                    <div class="space-y-2 sm:col-span-2 lg:col-span-3">
                        <Label for="internal_notes"
                            >Internal Notes (Staff Only)</Label
                        >
                        <textarea
                            id="internal_notes"
                            v-model="form.internal_notes"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                        />
                    </div>

                    <label
                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-border/70 p-3 sm:col-span-2 lg:col-span-3"
                    >
                        <Checkbox
                            :model-value="form.is_public_listing"
                            @update:model-value="
                                (val) => (form.is_public_listing = Boolean(val))
                            "
                        />
                        <span class="text-sm font-medium"
                            >Show on Public Vehicle Catalog</span
                        >
                    </label>
                </div>

                <template #footer>
                    <Button as-child variant="outline" class="min-h-10">
                        <Link :href="show(vehicle.id)">Cancel</Link>
                    </Button>
                    <Button
                        type="submit"
                        class="min-h-10"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" class="mr-1.5" />
                        <Save v-else class="mr-1.5 size-4" aria-hidden="true" />
                        Save Changes
                    </Button>
                </template>
            </FormSectionCard>
        </form>
    </div>
</template>
