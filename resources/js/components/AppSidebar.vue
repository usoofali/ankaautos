<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BadgeDollarSign,
    BarChart3,
    Bell,
    Building2,
    CalendarCheck2,
    Car,
    Database,
    FileCheck2,
    FolderLock,
    Landmark,
    LayoutGrid,
    Megaphone,
    Receipt,
    RotateCcw,
    ScrollText,
    ShieldCheck,
    UserRound,
    UserSquare2,
    Users,
    Wallet,
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
import { index as consignmentsIndex } from '@/routes/consignments';
import { index as customersIndex } from '@/routes/customers';
import { index as documentsIndex } from '@/routes/documents';
import { index as expensesIndex } from '@/routes/expenses';
import { index as financeIndex } from '@/routes/finance';
import { index as marketingIndex } from '@/routes/marketing';
import { index as notificationsIndex } from '@/routes/notifications';
import { index as ownersIndex } from '@/routes/owners';
import { index as reportsIndex } from '@/routes/reports';
import { index as reservationsIndex } from '@/routes/reservations';
import { index as salesIndex } from '@/routes/sales';
import { index as settlementsIndex } from '@/routes/settlements';
import { index as vehiclesIndex } from '@/routes/vehicles';
import { index as withdrawalsIndex } from '@/routes/withdrawals';
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
            label: 'Inventory & Consignment',
            items: [
                {
                    title: 'Vehicle Inventory',
                    href: vehiclesIndex(),
                    icon: Car,
                    iconClass: 'text-amber-300',
                    permission: 'vehicles.view',
                },
                {
                    title: 'Vehicle Owners',
                    href: ownersIndex(),
                    icon: UserSquare2,
                    iconClass: 'text-teal-400',
                    permission: 'owners.view',
                },
                {
                    title: 'Consignments',
                    href: consignmentsIndex(),
                    icon: FileCheck2,
                    iconClass: 'text-indigo-400',
                    permission: 'consignments.view',
                },
                {
                    title: 'Withdrawals',
                    href: withdrawalsIndex(),
                    icon: RotateCcw,
                    iconClass: 'text-rose-300',
                    permission: 'withdrawals.view',
                },
            ],
        },
        {
            label: 'Sales & Customers',
            items: [
                {
                    title: 'Customers',
                    href: customersIndex(),
                    icon: UserRound,
                    iconClass: 'text-cyan-300',
                    permission: 'customers.view',
                },
                {
                    title: 'Reservations',
                    href: reservationsIndex(),
                    icon: CalendarCheck2,
                    iconClass: 'text-violet-300',
                    permission: 'reservations.view',
                },
                {
                    title: 'Vehicle Sales',
                    href: salesIndex(),
                    icon: BadgeDollarSign,
                    iconClass: 'text-emerald-400',
                    permission: 'sales.view',
                },
            ],
        },
        {
            label: 'Finance & Settlements',
            items: [
                {
                    title: 'Vehicle Expenses',
                    href: expensesIndex(),
                    icon: Receipt,
                    iconClass: 'text-orange-400',
                    permission: 'expenses.view',
                },
                {
                    title: 'Owner Settlements',
                    href: settlementsIndex(),
                    icon: Wallet,
                    iconClass: 'text-lime-400',
                    permission: 'settlements.view',
                },
                {
                    title: 'Financial Ledger',
                    href: financeIndex(),
                    icon: Landmark,
                    iconClass: 'text-emerald-300',
                    permission: 'finance.view',
                },
            ],
        },
        {
            label: 'Marketing, Vault & Reports',
            items: [
                {
                    title: 'Marketing & Ads',
                    href: marketingIndex(),
                    icon: Megaphone,
                    iconClass: 'text-pink-400',
                    permission: 'marketing.view',
                },
                {
                    title: 'Document Vault',
                    href: documentsIndex(),
                    icon: FolderLock,
                    iconClass: 'text-amber-300',
                    permission: 'documents.view',
                },
                {
                    title: 'Reports & Exports',
                    href: reportsIndex(),
                    icon: BarChart3,
                    iconClass: 'text-sky-300',
                    anyPermissions: [
                        'reports.view_operational',
                        'reports.view_financial',
                    ],
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
