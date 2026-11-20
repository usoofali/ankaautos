<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Bell,
    Building2,
    Database,
    LayoutGrid,
    ScrollText,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { index as auditLogsIndex } from '@/routes/admin/audit-logs';
import { edit as businessSettingsEdit } from '@/routes/admin/business-settings';
import { index as masterDataIndex } from '@/routes/admin/master-data';
import { index as rolesIndex } from '@/routes/admin/roles';
import { index as usersIndex } from '@/routes/admin/users';
import { index as notificationsIndex } from '@/routes/notifications';
import type { NavGroup } from '@/types';

const { can, canAny } = usePermissions();
const { isCurrentUrl } = useCurrentUrl();

const navGroups = computed<NavGroup[]>(() => {
    const groups: NavGroup[] = [
        {
            label: 'Operations',
            items: [
                {
                    title: 'Dashboard',
                    href: dashboard(),
                    icon: LayoutGrid,
                    iconClass: 'text-amber-400',
                },
                {
                    title: 'Notifications',
                    href: notificationsIndex(),
                    icon: Bell,
                    iconClass: 'text-sky-400',
                },
            ],
        },
        {
            label: 'Administration',
            items: [
                {
                    title: 'Users & Staff',
                    href: usersIndex(),
                    icon: Users,
                    iconClass: 'text-emerald-400',
                    permission: 'users.manage',
                },
                {
                    title: 'Roles & Permissions',
                    href: rolesIndex(),
                    icon: ShieldCheck,
                    iconClass: 'text-violet-400',
                    permission: 'roles.manage',
                },
                {
                    title: 'Master Data & Charges',
                    href: masterDataIndex(),
                    icon: Database,
                    iconClass: 'text-amber-300',
                    anyPermissions: [
                        'master_data.manage',
                        'charges.manage_categories',
                    ],
                },
                {
                    title: 'Business Settings',
                    href: businessSettingsEdit(),
                    icon: Building2,
                    iconClass: 'text-cyan-400',
                    permission: 'settings.manage',
                },
                {
                    title: 'Activity & Audit Logs',
                    href: auditLogsIndex(),
                    icon: ScrollText,
                    iconClass: 'text-rose-400',
                    permission: 'audit.view',
                },
            ],
        },
    ];

    return groups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => {
                if (item.permission) {
                    return can(item.permission);
                }
                if (item.anyPermissions) {
                    return canAny(item.anyPermissions);
                }
                return true;
            }),
        }))
        .filter((group) => group.items.length > 0);
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup
                v-for="group in navGroups"
                :key="group.label"
                class="px-2 py-1"
            >
                <SidebarGroupLabel
                    class="text-[11px] font-semibold tracking-wider text-sidebar-foreground/55 uppercase"
                >
                    {{ group.label }}
                </SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem
                        v-for="item in group.items"
                        :key="item.title"
                    >
                        <SidebarMenuButton
                            as-child
                            :is-active="isCurrentUrl(item.href)"
                            :tooltip="item.title"
                            class="min-h-10"
                        >
                            <Link :href="item.href">
                                <component
                                    :is="item.icon"
                                    :class="['size-4 shrink-0', item.iconClass]"
                                    aria-hidden="true"
                                />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
