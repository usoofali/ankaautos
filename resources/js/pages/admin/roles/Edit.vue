<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckSquare, Save, Square } from '@lucide/vue';
import FormSectionCard from '@/components/anka/FormSectionCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { index, update } from '@/routes/admin/roles';

type PermissionItem = {
    name: string;
    display_name: string;
    description: string;
};

type PermissionGroup = {
    group: string;
    permissions: PermissionItem[];
};

const props = defineProps<{
    role: {
        id: number;
        name: string;
        display_name: string;
        description: string;
        purpose: string;
        responsibilities_summary: string;
        permissions: string[];
        is_super_admin: boolean;
    };
    permissionGroups: PermissionGroup[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Roles & Permissions', href: index() },
            { title: 'Configure Role', href: index() },
        ],
    },
});

const form = useForm({
    display_name: props.role.display_name,
    description: props.role.description,
    purpose: props.role.purpose,
    responsibilities_summary: props.role.responsibilities_summary,
    permissions: [...props.role.permissions],
});

function hasPermission(permissionName: string): boolean {
    return form.permissions.includes(permissionName);
}

function togglePermission(permissionName: string, checked: boolean): void {
    if (checked && !form.permissions.includes(permissionName)) {
        form.permissions.push(permissionName);
    } else if (!checked) {
        form.permissions = form.permissions.filter((p) => p !== permissionName);
    }
}

function isGroupFullySelected(group: PermissionGroup): boolean {
    return group.permissions.every((p) => form.permissions.includes(p.name));
}

function toggleGroup(group: PermissionGroup): void {
    const allSelected = isGroupFullySelected(group);
    const groupNames = group.permissions.map((p) => p.name);

    if (allSelected) {
        form.permissions = form.permissions.filter(
            (name) => !groupNames.includes(name),
        );
    } else {
        const merged = new Set([...form.permissions, ...groupNames]);
        form.permissions = Array.from(merged);
    }
}

function submit(): void {
    form.put(update.url(props.role.id));
}
</script>

<template>
    <Head :title="`Configure ${role.display_name}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            :title="`Configure ${role.display_name}`"
            description="Review role documentation and select the exact permissions granted to staff assigned to this role."
        >
            <template #actions>
                <Button as-child variant="outline" class="min-h-10">
                    <Link :href="index()">
                        <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
                        Back to Roles
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="space-y-5" @submit.prevent="submit">
            <FormSectionCard
                title="Role Documentation"
                description="Plain-English description of what this role is responsible for across the dealership."
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <Label for="display_name"
                            >Role Name
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="display_name"
                            v-model="form.display_name"
                            type="text"
                            required
                            class="min-h-10"
                        />
                        <InputError :message="form.errors.display_name" />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label for="description"
                            >Short Description
                            <span class="text-destructive">*</span></Label
                        >
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            required
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="space-y-2">
                        <Label for="purpose">Purpose of the Role</Label>
                        <textarea
                            id="purpose"
                            v-model="form.purpose"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                        />
                        <InputError :message="form.errors.purpose" />
                    </div>

                    <div class="space-y-2">
                        <Label for="responsibilities_summary"
                            >Summary of Responsibilities</Label
                        >
                        <textarea
                            id="responsibilities_summary"
                            v-model="form.responsibilities_summary"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                        />
                        <InputError
                            :message="form.errors.responsibilities_summary"
                        />
                    </div>
                </div>
            </FormSectionCard>

            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-foreground">
                            Assigned Permissions ({{ form.permissions.length }})
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Permissions are grouped by dealership module with
                            plain-English descriptions.
                        </p>
                    </div>
                    <p
                        v-if="role.is_super_admin"
                        class="text-xs font-medium text-amber-700 dark:text-amber-300"
                    >
                        Core user, role, and settings permissions remain
                        protected for Super Administrator.
                    </p>
                </div>

                <FormSectionCard
                    v-for="group in permissionGroups"
                    :key="group.group"
                    :title="group.group"
                >
                    <template #headerAction>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="min-h-9"
                            @click="toggleGroup(group)"
                        >
                            <component
                                :is="
                                    isGroupFullySelected(group)
                                        ? Square
                                        : CheckSquare
                                "
                                class="mr-1.5 size-3.5"
                                aria-hidden="true"
                            />
                            {{
                                isGroupFullySelected(group)
                                    ? 'Clear Module'
                                    : 'Select All'
                            }}
                        </Button>
                    </template>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label
                            v-for="permission in group.permissions"
                            :key="permission.name"
                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-border/70 p-3.5 transition-colors hover:bg-muted/40"
                            :class="
                                hasPermission(permission.name)
                                    ? 'border-brand-amber/60 bg-accent/25'
                                    : ''
                            "
                        >
                            <Checkbox
                                class="mt-0.5"
                                :model-value="hasPermission(permission.name)"
                                @update:model-value="
                                    (checked) =>
                                        togglePermission(
                                            permission.name,
                                            Boolean(checked),
                                        )
                                "
                            />
                            <div class="min-w-0 space-y-0.5">
                                <span
                                    class="block text-sm font-medium text-foreground"
                                >
                                    {{ permission.display_name }}
                                </span>
                                <span
                                    class="block text-xs leading-relaxed text-muted-foreground"
                                >
                                    {{ permission.description }}
                                </span>
                            </div>
                        </label>
                    </div>
                </FormSectionCard>
            </div>

            <div
                class="sticky bottom-3 z-10 flex flex-wrap items-center justify-end gap-2 rounded-xl border border-border bg-card/95 p-3 shadow-md backdrop-blur-xs"
            >
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
                    Save Role Permissions
                </Button>
            </div>
        </form>
    </div>
</template>
