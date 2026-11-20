<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { KeyRound, Settings2, ShieldCheck, Users } from '@lucide/vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import StatusBadge from '@/components/anka/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { edit, index } from '@/routes/admin/roles';

type RoleCard = {
    id: number;
    name: string;
    display_name: string;
    description: string;
    purpose: string;
    responsibilities_summary: string;
    users_count: number;
    permissions_count: number;
    is_super_admin: boolean;
};

defineProps<{
    roles: RoleCard[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Roles & Permissions', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Roles & Permissions" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Roles & Permissions"
            description="Review each role's purpose, operational responsibilities, and configure module-level access permissions."
        />

        <div class="grid gap-4 lg:grid-cols-2">
            <article
                v-for="role in roles"
                :key="role.id"
                class="flex flex-col justify-between rounded-xl border border-border/80 bg-card p-5 shadow-xs"
            >
                <div class="space-y-3">
                    <div
                        class="flex flex-wrap items-start justify-between gap-2"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-accent text-accent-foreground"
                            >
                                <ShieldCheck
                                    class="size-5"
                                    aria-hidden="true"
                                />
                            </div>
                            <div>
                                <h2
                                    class="text-base font-semibold text-card-foreground"
                                >
                                    {{ role.display_name }}
                                </h2>
                                <div
                                    class="mt-0.5 flex flex-wrap items-center gap-3 text-xs text-muted-foreground"
                                >
                                    <span
                                        class="inline-flex items-center gap-1"
                                    >
                                        <Users
                                            class="size-3.5"
                                            aria-hidden="true"
                                        />
                                        {{ role.users_count }}
                                        {{
                                            role.users_count === 1
                                                ? 'staff member'
                                                : 'staff members'
                                        }}
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1"
                                    >
                                        <KeyRound
                                            class="size-3.5"
                                            aria-hidden="true"
                                        />
                                        {{ role.permissions_count }} permissions
                                    </span>
                                </div>
                            </div>
                        </div>

                        <StatusBadge
                            v-if="role.is_super_admin"
                            label="System Root"
                            variant="warning"
                        />
                    </div>

                    <p class="text-sm leading-relaxed text-foreground">
                        {{ role.description }}
                    </p>

                    <div
                        v-if="role.purpose"
                        class="rounded-lg bg-muted/55 p-3 text-xs leading-relaxed"
                    >
                        <span class="font-semibold text-foreground"
                            >Purpose:
                        </span>
                        <span class="text-muted-foreground">{{
                            role.purpose
                        }}</span>
                    </div>

                    <div
                        v-if="role.responsibilities_summary"
                        class="text-xs leading-relaxed text-muted-foreground"
                    >
                        <span class="font-semibold text-foreground"
                            >Key Responsibilities:
                        </span>
                        {{ role.responsibilities_summary }}
                    </div>
                </div>

                <div
                    class="mt-4 flex items-center justify-end border-t border-border/60 pt-4"
                >
                    <Button
                        as-child
                        variant="outline"
                        class="min-h-10 w-full sm:w-auto"
                    >
                        <Link :href="edit(role.id)">
                            <Settings2
                                class="mr-1.5 size-4"
                                aria-hidden="true"
                            />
                            Configure Permissions
                        </Link>
                    </Button>
                </div>
            </article>
        </div>
    </div>
</template>
