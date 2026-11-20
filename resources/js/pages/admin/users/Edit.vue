<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import { computed } from 'vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { edit, index, update } from '@/routes/admin/users';

type RoleOption = {
    name: string;
    display_name: string;
    description: string | null;
};

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    role: string;
    last_login_at: string | null;
};

const props = defineProps<{
    managedUser: ManagedUser;
    roles: RoleOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Users & Staff', href: index() },
            { title: 'Edit Staff Member', href: index() },
        ],
    },
});

const form = useForm({
    name: props.managedUser.name,
    email: props.managedUser.email,
    phone: props.managedUser.phone ?? '',
    role: props.managedUser.role,
    is_active: props.managedUser.is_active,
    password: '',
    password_confirmation: '',
});

const selectedRole = computed(() =>
    props.roles.find((role) => role.name === form.role),
);

function submit(): void {
    form.put(update.url(props.managedUser.id));
}
</script>

<template>
    <Head :title="`Edit ${managedUser.name}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="`Edit ${managedUser.name}`"
            description="Update staff details, role assignment, account status, or reset password."
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Staff
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="max-w-3xl space-y-5" @submit.prevent="submit">
            <FormSectionCard
                title="Account Details"
                description="Identity and contact information."
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="name"
                            >Full Name
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="email"
                            >Email Address
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="space-y-2">
                        <Label for="phone">Phone Number</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.phone" />
                    </div>
                </div>
            </FormSectionCard>

            <FormSectionCard
                title="Role, Status & Optional Password Reset"
                description="Leave password fields blank to keep the user's current password."
            >
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="role"
                            >Assigned Role
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="role"
                            v-model="form.role"
                            required
                            class="flex min-h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                        >
                            <option
                                v-for="role in roles"
                                :key="role.name"
                                :value="role.name"
                            >
                                {{ role.display_name }}
                            </option>
                        </select>
                        <p
                            v-if="selectedRole?.description"
                            class="rounded-lg bg-muted/60 px-3 py-2 text-xs text-muted-foreground"
                        >
                            {{ selectedRole.description }}
                        </p>
                        <InputError :message="form.errors.role" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="password"
                                >New Password (Optional)</Label
                            >
                            <PasswordInput
                                id="password"
                                v-model="form.password"
                                autocomplete="new-password"
                                class="min-h-10"
                                placeholder="Leave blank to keep existing"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation"
                                >Confirm New Password</Label
                            >
                            <PasswordInput
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                autocomplete="new-password"
                                class="min-h-10"
                                placeholder="Repeat new password"
                            />
                        </div>
                    </div>

                    <label
                        for="is_active"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-border/70 p-3"
                    >
                        <Checkbox
                            id="is_active"
                            :model-value="form.is_active"
                            @update:model-value="
                                (val) => (form.is_active = Boolean(val))
                            "
                        />
                        <div class="space-y-0.5">
                            <span class="block text-sm font-medium"
                                >Account Active</span
                            >
                            <span class="block text-xs text-muted-foreground">
                                Deactivated staff cannot sign in or access any
                                dealership endpoints.
                            </span>
                        </div>
                    </label>
                </div>

                <template #footer>
                    <Button as-child variant="outline" class="min-h-10">
                        <Link :href="edit(managedUser.id)">Reset</Link>
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
