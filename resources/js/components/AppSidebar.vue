<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    BadgeDollarSign,
    BarChart3,
    Building2,
    CalendarCheck2,
    Car,
    ChevronDown,
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
import { computed, onUnmounted, reactive, watch } from 'vue';
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
    useSidebar,
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
import { index as ownersIndex } from '@/routes/owners';
import { index as reportsIndex } from '@/routes/reports';
import { index as reservationsIndex } from '@/routes/reservations';
import { index as salesIndex } from '@/routes/sales';
import { index as settlementsIndex } from '@/routes/settlements';
import { index as vehiclesIndex } from '@/routes/vehicles';
import { index as withdrawalsIndex } from '@/routes/withdrawals';
import type { NavGroup } from '@/types';

type SidebarNavGroup = NavGroup & {
    key: string;
    collapsible?: boolean;
};

const { can, canAny } = usePermissions();
const { isCurrentUrl, currentUrl } = useCurrentUrl();
const { isMobile, setOpenMobile, state: sidebarState } = useSidebar();

function closeMobileSidebar(): void {
    if (isMobile.value) {
        setOpenMobile(false);
    }
}

// Guarantee mobile sidebar closes automatically after any Inertia navigation
const removeNavigateListener = router.on('navigate', () => {
    closeMobileSidebar();
});

onUnmounted(() => {
    removeNavigateListener();
});

const navGroups = computed<SidebarNavGroup[]>(() => {
    const groups: SidebarNavGroup[] = [
        {
            key: 'operations',
            label: 'Operations',
            collapsible: false,
            items: [
                {
                    title: 'Dashboard',
                    href: dashboard(),
                    icon: LayoutGrid,
                    iconClass: 'text-amber-400',
                },
            ],
        },
        {
            key: 'inventory',
            label: 'Inventory & Consignment',
            collapsible: true,
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
            key: 'sales',
            label: 'Sales & Customers',
            collapsible: true,
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
            key: 'finance',
            label: 'Finance & Settlements',
            collapsible: true,
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
            key: 'marketing_reports',
            label: 'Marketing, Vault & Reports',
            collapsible: true,
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
            key: 'administration',
            label: 'Administration',
            collapsible: true,
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

const openGroups = reactive<Record<string, boolean>>({
    inventory: false,
    sales: false,
    finance: false,
    marketing_reports: false,
    administration: false,
});

function hasActiveItem(group: SidebarNavGroup): boolean {
    return group.items.some((item) => isCurrentUrl(item.href));
}

function isGroupOpen(group: SidebarNavGroup): boolean {
    if (!group.collapsible) {
        return true;
    }
    if (sidebarState.value === 'collapsed' && !isMobile.value) {
        return true;
    }
    return Boolean(openGroups[group.key]);
}

function toggleGroup(key: string): void {
    openGroups[key] = !openGroups[key];
}

// Automatically expand whichever collapsible group holds the active route
watch(
    () => currentUrl.value,
    () => {
        for (const group of navGroups.value) {
            if (group.collapsible && hasActiveItem(group)) {
                openGroups[group.key] = true;
            }
        }
    },
    { immediate: true },
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()" @click="closeMobileSidebar">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup
                v-for="group in navGroups"
                :key="group.key"
                class="px-2 py-1"
            >
                <button
                    v-if="group.collapsible"
                    type="button"
                    class="group/btn flex w-full items-center justify-between rounded-md px-2 py-2 text-left text-[11px] font-semibold tracking-wider text-sidebar-foreground/75 uppercase transition group-data-[collapsible=icon]:hidden hover:bg-sidebar-accent/70 hover:text-sidebar-foreground"
                    :aria-expanded="isGroupOpen(group)"
                    @click="toggleGroup(group.key)"
                >
                    <span class="flex items-center gap-2 truncate">
                        <span
                            v-if="hasActiveItem(group)"
                            class="size-1.5 shrink-0 rounded-full bg-sidebar-primary"
                            aria-hidden="true"
                        />
                        <span class="truncate">{{ group.label }}</span>
                    </span>
                    <ChevronDown
                        class="size-3.5 shrink-0 text-sidebar-foreground/60 transition-transform duration-200"
                        :class="isGroupOpen(group) ? 'rotate-180' : ''"
                        aria-hidden="true"
                    />
                </button>
                <SidebarGroupLabel
                    v-else
                    class="text-[11px] font-semibold tracking-wider text-sidebar-foreground/55 uppercase"
                >
                    {{ group.label }}
                </SidebarGroupLabel>

                <SidebarMenu
                    v-show="isGroupOpen(group)"
                    :class="
                        group.collapsible
                            ? 'mt-0.5 space-y-0.5 border-l border-sidebar-border/60 pl-2 group-data-[collapsible=icon]:border-l-0 group-data-[collapsible=icon]:pl-0'
                            : 'space-y-0.5'
                    "
                >
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
                            <Link :href="item.href" @click="closeMobileSidebar">
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
