<script setup lang="ts">
import { UserPlus } from '@lucide/vue';
import { reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
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
import { quickStore } from '@/routes/customers';

export type QuickCustomer = {
    id: number;
    reference_number: string;
    name: string;
    phone: string;
};

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    created: [customer: QuickCustomer];
}>();

const processing = ref(false);
const errors = reactive<Record<string, string>>({});

const form = reactive({
    customer_type: 'individual',
    name: '',
    phone: '',
    email: '',
    address: '',
});

function getCsrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));
    return match ? decodeURIComponent(match.split('=')[1] ?? '') : '';
}

function resetForm(): void {
    form.customer_type = 'individual';
    form.name = '';
    form.phone = '';
    form.email = '';
    form.address = '';
    for (const key of Object.keys(errors)) {
        delete errors[key];
    }
}

async function submit(): Promise<void> {
    processing.value = true;
    for (const key of Object.keys(errors)) {
        delete errors[key];
    }

    try {
        const response = await fetch(quickStore.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                customer_type: form.customer_type,
                name: form.name,
                phone: form.phone,
                email: form.email || null,
                address: form.address || null,
            }),
        });

        const payload = (await response.json()) as {
            customer?: QuickCustomer;
            errors?: Record<string, string[]>;
            message?: string;
        };

        if (response.status === 422 && payload.errors) {
            for (const [field, messages] of Object.entries(payload.errors)) {
                errors[field] = messages[0] ?? 'Invalid value.';
            }
            return;
        }

        if (!response.ok || !payload.customer) {
            toast.error(
                payload.message ||
                    'Could not create customer. Please check the form and try again.',
            );
            return;
        }

        toast.success(
            `Customer ${payload.customer.name} created and selected.`,
        );
        emit('created', payload.customer);
        emit('update:open', false);
        resetForm();
    } catch {
        toast.error('Network error while creating customer. Please try again.');
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Dialog
        :open="open"
        @update:open="
            (val) => {
                emit('update:open', val);
                if (!val) resetForm();
            }
        "
    >
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <UserPlus class="size-5 text-primary" aria-hidden="true" />
                    Quick Add Customer
                </DialogTitle>
                <DialogDescription>
                    Register a new buyer right here without leaving this page.
                    They will be automatically selected once saved.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="qc_type">Customer Category *</Label>
                        <select
                            id="qc_type"
                            v-model="form.customer_type"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            required
                        >
                            <option value="individual">Individual</option>
                            <option value="organization">
                                Organization / Corporate
                            </option>
                        </select>
                        <InputError :message="errors.customer_type" />
                    </div>

                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="qc_name">Full Name / Company Name *</Label>
                        <Input
                            id="qc_name"
                            v-model="form.name"
                            placeholder="e.g. Alhaji Bello Danjuma"
                            class="min-h-10"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="qc_phone">Phone Number *</Label>
                        <Input
                            id="qc_phone"
                            v-model="form.phone"
                            placeholder="+234 803 000 0000"
                            class="min-h-10"
                            required
                        />
                        <InputError :message="errors.phone" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="qc_email">Email (Optional)</Label>
                        <Input
                            id="qc_email"
                            v-model="form.email"
                            type="email"
                            placeholder="buyer@example.com"
                            class="min-h-10"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="qc_address">Address (Optional)</Label>
                        <Input
                            id="qc_address"
                            v-model="form.address"
                            placeholder="Residential or office address"
                            class="min-h-10"
                        />
                        <InputError :message="errors.address" />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        class="min-h-10"
                        @click="emit('update:open', false)"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        class="min-h-10"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" class="mr-2 size-4" />
                        Save &amp; Select Customer
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
