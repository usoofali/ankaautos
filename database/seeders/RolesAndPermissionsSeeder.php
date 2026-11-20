<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Complete permission catalog with plain-English descriptions grouped by module (Appendix F.14).
     *
     * @return list<array{name: string, display_name: string, description: string, module_group: string}>
     */
    public static function permissionCatalog(): array
    {
        return [
            // Dashboard & Overview
            [
                'name' => 'dashboard.view',
                'display_name' => 'View Dashboard',
                'description' => 'Allows the user to access the main dealership overview dashboard.',
                'module_group' => 'Dashboard',
            ],
            [
                'name' => 'dashboard.view_financials',
                'display_name' => 'View Dashboard Financial Summaries',
                'description' => 'Allows the user to view revenue, expenses, receivables, and owner payable totals on the dashboard.',
                'module_group' => 'Dashboard',
            ],

            // Vehicle Inventory
            [
                'name' => 'vehicles.view',
                'display_name' => 'View Vehicles',
                'description' => 'Allows the user to view registered vehicles, specifications, photographs, and history.',
                'module_group' => 'Vehicle Inventory',
            ],
            [
                'name' => 'vehicles.create',
                'display_name' => 'Register Vehicles',
                'description' => 'Allows the user to register new company-owned or consignment vehicles into inventory.',
                'module_group' => 'Vehicle Inventory',
            ],
            [
                'name' => 'vehicles.edit',
                'display_name' => 'Edit Vehicles',
                'description' => 'Allows the user to update vehicle specifications, location, pricing, and photographs.',
                'module_group' => 'Vehicle Inventory',
            ],
            [
                'name' => 'vehicles.change_status',
                'display_name' => 'Update Vehicle Status',
                'description' => 'Allows the user to update operational vehicle statuses (such as Preparation, Maintenance, or Available).',
                'module_group' => 'Vehicle Inventory',
            ],
            [
                'name' => 'vehicles.log_attendant_activity',
                'display_name' => 'Log Physical Vehicle Activities',
                'description' => 'Allows the user to log washing, maintenance work, condition updates, movements, and preparation progress.',
                'module_group' => 'Vehicle Inventory',
            ],
            [
                'name' => 'vehicles.override_duplicate_vin',
                'display_name' => 'Override Duplicate VIN / Chassis',
                'description' => 'Allows managers to approve re-registering a previously sold or withdrawn vehicle returning to the stand.',
                'module_group' => 'Vehicle Inventory',
            ],
            [
                'name' => 'vehicles.archive',
                'display_name' => 'Archive Vehicles',
                'description' => 'Allows managers to archive inactive sold or withdrawn vehicle records while preserving history.',
                'module_group' => 'Vehicle Inventory',
            ],

            // Vehicle Owners & Consignments
            [
                'name' => 'owners.view',
                'display_name' => 'View Vehicle Owners',
                'description' => 'Allows the user to view third-party consignment vehicle owners and their vehicle history.',
                'module_group' => 'Ownership & Consignment',
            ],
            [
                'name' => 'owners.manage',
                'display_name' => 'Register & Edit Vehicle Owners',
                'description' => 'Allows the user to register new third-party vehicle owners and update their contact and payout details.',
                'module_group' => 'Ownership & Consignment',
            ],
            [
                'name' => 'consignments.view',
                'display_name' => 'View Consignments',
                'description' => 'Allows the user to view consignment records, agreements, commission structures, and status history.',
                'module_group' => 'Ownership & Consignment',
            ],
            [
                'name' => 'consignments.create',
                'display_name' => 'Create Consignments',
                'description' => 'Allows the user to register third-party consignment agreements and capture commission arrangements.',
                'module_group' => 'Ownership & Consignment',
            ],
            [
                'name' => 'consignments.edit',
                'display_name' => 'Edit Consignments',
                'description' => 'Allows the user to update non-financial consignment details and upload signed agreements.',
                'module_group' => 'Ownership & Consignment',
            ],
            [
                'name' => 'consignments.amend_terms',
                'display_name' => 'Amend Consignment Financial Terms',
                'description' => 'Allows managers to amend agreed selling prices or commission arrangements with versioned audit history.',
                'module_group' => 'Ownership & Consignment',
            ],
            [
                'name' => 'withdrawals.view',
                'display_name' => 'View Consignment Withdrawals',
                'description' => 'Allows the user to view consignment withdrawal requests, charges, and completion records.',
                'module_group' => 'Ownership & Consignment',
            ],
            [
                'name' => 'withdrawals.initiate',
                'display_name' => 'Initiate Consignment Withdrawals',
                'description' => 'Allows authorized staff to initiate a vehicle withdrawal request and apply withdrawal charges.',
                'module_group' => 'Ownership & Consignment',
            ],
            [
                'name' => 'withdrawals.complete',
                'display_name' => 'Complete Consignment Withdrawals',
                'description' => 'Allows authorized staff to finalize a paid withdrawal and release the vehicle to its owner.',
                'module_group' => 'Ownership & Consignment',
            ],

            // Expenses & Dynamic Charges
            [
                'name' => 'expenses.view',
                'display_name' => 'View Vehicle Expenses',
                'description' => 'Allows the user to view recorded vehicle expenses and payment history.',
                'module_group' => 'Expenses & Charges',
            ],
            [
                'name' => 'expenses.create',
                'display_name' => 'Record Vehicle Expenses',
                'description' => 'Allows the user to record expenses against individual vehicles and attach receipts.',
                'module_group' => 'Expenses & Charges',
            ],
            [
                'name' => 'expenses.pay',
                'display_name' => 'Record Expense Payments',
                'description' => 'Allows finance personnel to record payments made toward recorded vehicle expenses.',
                'module_group' => 'Expenses & Charges',
            ],
            [
                'name' => 'charges.manage_categories',
                'display_name' => 'Manage Dynamic Charge Categories',
                'description' => 'Allows authorized users to create, edit, activate, and deactivate reusable charge categories.',
                'module_group' => 'Expenses & Charges',
            ],
            [
                'name' => 'charges.apply',
                'display_name' => 'Apply Transaction Charges',
                'description' => 'Allows authorized users to apply dynamic or exceptional one-time charges to transactions.',
                'module_group' => 'Expenses & Charges',
            ],

            // Customers, Reservations, Sales & Handovers
            [
                'name' => 'customers.view',
                'display_name' => 'View Customers & Enquiries',
                'description' => 'Allows the user to view customer records, enquiries, inspections, and purchase history.',
                'module_group' => 'Sales & Customers',
            ],
            [
                'name' => 'customers.manage',
                'display_name' => 'Manage Customers & Enquiries',
                'description' => 'Allows the user to register customers, log vehicle enquiries, inspections, and sales negotiations.',
                'module_group' => 'Sales & Customers',
            ],
            [
                'name' => 'reservations.view',
                'display_name' => 'View Reservations',
                'description' => 'Allows the user to view active, converted, and cancelled vehicle reservations.',
                'module_group' => 'Sales & Customers',
            ],
            [
                'name' => 'reservations.manage',
                'display_name' => 'Create & Convert Reservations',
                'description' => 'Allows the user to reserve vehicles with mandatory deposits and convert reservations into sales.',
                'module_group' => 'Sales & Customers',
            ],
            [
                'name' => 'reservations.override',
                'display_name' => 'Override Conflicting Reservations',
                'description' => 'Allows managers to override conflicting reservations or cancel active reservations.',
                'module_group' => 'Sales & Customers',
            ],
            [
                'name' => 'sales.view',
                'display_name' => 'View Sales Transactions',
                'description' => 'Allows the user to view vehicle sales, payment balances, and receipts.',
                'module_group' => 'Sales & Customers',
            ],
            [
                'name' => 'sales.create',
                'display_name' => 'Process Vehicle Sales',
                'description' => 'Allows the user to create and confirm full-payment or installment vehicle sales.',
                'module_group' => 'Sales & Customers',
            ],
            [
                'name' => 'sales.approve_discount',
                'display_name' => 'Approve Discounts & Price Adjustments',
                'description' => 'Allows managers to approve price adjustments and discounts on vehicle sales.',
                'module_group' => 'Sales & Customers',
            ],
            [
                'name' => 'sales.cancel',
                'display_name' => 'Cancel Sales & Issue Refunds',
                'description' => 'Allows managers to cancel confirmed sales, record refunds, and return vehicles to inventory.',
                'module_group' => 'Sales & Customers',
            ],
            [
                'name' => 'handovers.view',
                'display_name' => 'View Vehicle Handovers',
                'description' => 'Allows the user to view vehicle handover checklists and download handover certificates.',
                'module_group' => 'Sales & Customers',
            ],
            [
                'name' => 'handovers.create',
                'display_name' => 'Record Vehicle Handovers',
                'description' => 'Allows authorized staff to complete handover checklists and release cleared vehicles.',
                'module_group' => 'Sales & Customers',
            ],
            [
                'name' => 'handovers.approve_early_release',
                'display_name' => 'Approve Early Vehicle Handover',
                'description' => 'Allows managers to authorize physical vehicle release before 100% installment payment is collected.',
                'module_group' => 'Sales & Customers',
            ],

            // Financial Management & Settlements
            [
                'name' => 'finance.view',
                'display_name' => 'View Financial Ledger',
                'description' => 'Allows the user to view financial transactions, receivables, payables, and company vs. owner balances.',
                'module_group' => 'Financial Management',
            ],
            [
                'name' => 'payments.record',
                'display_name' => 'Record Customer & Withdrawal Payments',
                'description' => 'Allows finance personnel to record incoming customer payments, installments, and withdrawal payments.',
                'module_group' => 'Financial Management',
            ],
            [
                'name' => 'finance.adjust',
                'display_name' => 'Record Refunds & Financial Adjustments',
                'description' => 'Allows managers to record authorized financial adjustments and customer refunds.',
                'module_group' => 'Financial Management',
            ],
            [
                'name' => 'settlements.view',
                'display_name' => 'View Owner Settlements',
                'description' => 'Allows the user to view consignment owner settlement breakdowns and payout history.',
                'module_group' => 'Financial Management',
            ],
            [
                'name' => 'settlements.process',
                'display_name' => 'Process Owner Settlements & Payouts',
                'description' => 'Allows finance personnel to calculate deductions and disburse payouts to consignment vehicle owners.',
                'module_group' => 'Financial Management',
            ],

            // Marketing & Documents
            [
                'name' => 'marketing.view',
                'display_name' => 'View Marketing & Share History',
                'description' => 'Allows the user to view vehicle advertisements and social media sharing activity.',
                'module_group' => 'Marketing & Documents',
            ],
            [
                'name' => 'marketing.manage',
                'display_name' => 'Create & Share Vehicle Advertisements',
                'description' => 'Allows the user to edit ad copy, select listing photos, toggle public listings, and share to WhatsApp, Facebook, and Instagram.',
                'module_group' => 'Marketing & Documents',
            ],
            [
                'name' => 'documents.view',
                'display_name' => 'View & Download Documents',
                'description' => 'Allows the user to view and download authorized vehicle, sales, and consignment documents.',
                'module_group' => 'Marketing & Documents',
            ],
            [
                'name' => 'documents.manage',
                'display_name' => 'Upload & Manage Documents',
                'description' => 'Allows the user to upload supporting documents linked to vehicles, owners, customers, and transactions.',
                'module_group' => 'Marketing & Documents',
            ],

            // Reports
            [
                'name' => 'reports.view_operational',
                'display_name' => 'View Operational Reports',
                'description' => 'Allows the user to view and export inventory, consignment, and sales activity reports.',
                'module_group' => 'Reports & Analytics',
            ],
            [
                'name' => 'reports.view_financial',
                'display_name' => 'View Financial & Profitability Reports',
                'description' => 'Allows authorized management and finance staff to view and export expense, settlement, and profitability reports.',
                'module_group' => 'Reports & Analytics',
            ],

            // Administration
            [
                'name' => 'users.manage',
                'display_name' => 'Manage Users',
                'description' => 'Allows administrators to create, edit, activate, deactivate, and assign roles to staff accounts.',
                'module_group' => 'Administration',
            ],
            [
                'name' => 'roles.manage',
                'display_name' => 'Manage Roles & Permissions',
                'description' => 'Allows administrators to inspect and modify permissions assigned to system roles.',
                'module_group' => 'Administration',
            ],
            [
                'name' => 'master_data.manage',
                'display_name' => 'Manage Master Catalog Data',
                'description' => 'Allows administrators to manage vehicle brands, models, categories, conditions, expense/charge categories, payment methods, and accounts.',
                'module_group' => 'Administration',
            ],
            [
                'name' => 'settings.manage',
                'display_name' => 'Manage Business Settings',
                'description' => 'Allows administrators to configure dealership identity, contact numbers, currency, timezone, and receipt footers.',
                'module_group' => 'Administration',
            ],
            [
                'name' => 'audit.view',
                'display_name' => 'View Activity & Audit Logs',
                'description' => 'Allows authorized management users to inspect immutable system audit trails and user activity histories.',
                'module_group' => 'Administration',
            ],
        ];
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissionNames = [];
        foreach (self::permissionCatalog() as $index => $item) {
            Permission::query()->updateOrCreate(
                ['name' => $item['name'], 'guard_name' => 'web'],
                [
                    'display_name' => $item['display_name'],
                    'description' => $item['description'],
                    'module_group' => $item['module_group'],
                    'sort_order' => ($index + 1) * 10,
                ],
            );
            $allPermissionNames[] = $item['name'];
        }

        $rolePermissions = [
            SystemRole::SuperAdministrator->value => $allPermissionNames,

            SystemRole::Manager->value => [
                'dashboard.view',
                'dashboard.view_financials',
                'vehicles.view',
                'vehicles.create',
                'vehicles.edit',
                'vehicles.change_status',
                'vehicles.log_attendant_activity',
                'vehicles.override_duplicate_vin',
                'vehicles.archive',
                'owners.view',
                'owners.manage',
                'consignments.view',
                'consignments.create',
                'consignments.edit',
                'consignments.amend_terms',
                'withdrawals.view',
                'withdrawals.initiate',
                'withdrawals.complete',
                'expenses.view',
                'expenses.create',
                'expenses.pay',
                'charges.manage_categories',
                'charges.apply',
                'customers.view',
                'customers.manage',
                'reservations.view',
                'reservations.manage',
                'reservations.override',
                'sales.view',
                'sales.create',
                'sales.approve_discount',
                'sales.cancel',
                'handovers.view',
                'handovers.create',
                'handovers.approve_early_release',
                'finance.view',
                'payments.record',
                'finance.adjust',
                'settlements.view',
                'settlements.process',
                'marketing.view',
                'marketing.manage',
                'documents.view',
                'documents.manage',
                'reports.view_operational',
                'reports.view_financial',
                'audit.view',
            ],

            SystemRole::InventoryOfficer->value => [
                'dashboard.view',
                'vehicles.view',
                'vehicles.create',
                'vehicles.edit',
                'vehicles.change_status',
                'vehicles.log_attendant_activity',
                'owners.view',
                'owners.manage',
                'consignments.view',
                'consignments.create',
                'consignments.edit',
                'withdrawals.view',
                'withdrawals.initiate',
                'expenses.view',
                'expenses.create',
                'customers.view',
                'handovers.view',
                'handovers.create',
                'marketing.view',
                'documents.view',
                'documents.manage',
                'reports.view_operational',
            ],

            SystemRole::SalesOfficer->value => [
                'dashboard.view',
                'vehicles.view',
                'owners.view',
                'consignments.view',
                'customers.view',
                'customers.manage',
                'reservations.view',
                'reservations.manage',
                'sales.view',
                'sales.create',
                'handovers.view',
                'handovers.create',
                'marketing.view',
                'marketing.manage',
                'documents.view',
                'documents.manage',
                'reports.view_operational',
            ],

            SystemRole::Accountant->value => [
                'dashboard.view',
                'dashboard.view_financials',
                'vehicles.view',
                'owners.view',
                'consignments.view',
                'withdrawals.view',
                'withdrawals.complete',
                'expenses.view',
                'expenses.create',
                'expenses.pay',
                'charges.manage_categories',
                'charges.apply',
                'customers.view',
                'reservations.view',
                'sales.view',
                'handovers.view',
                'finance.view',
                'payments.record',
                'settlements.view',
                'settlements.process',
                'documents.view',
                'documents.manage',
                'reports.view_operational',
                'reports.view_financial',
            ],

            SystemRole::VehicleAttendant->value => [
                'dashboard.view',
                'vehicles.view',
                'vehicles.log_attendant_activity',
                'expenses.create',
                'handovers.view',
            ],

            SystemRole::Viewer->value => [
                'dashboard.view',
                'vehicles.view',
                'owners.view',
                'consignments.view',
                'withdrawals.view',
                'expenses.view',
                'customers.view',
                'reservations.view',
                'sales.view',
                'handovers.view',
                'settlements.view',
                'marketing.view',
                'reports.view_operational',
            ],
        ];

        foreach (SystemRole::cases() as $roleEnum) {
            /** @var Role $role */
            $role = Role::query()->updateOrCreate(
                ['name' => $roleEnum->value, 'guard_name' => 'web'],
                [
                    'display_name' => $roleEnum->displayName(),
                    'description' => $roleEnum->description(),
                    'purpose' => $roleEnum->purpose(),
                    'responsibilities_summary' => $roleEnum->responsibilitiesSummary(),
                ],
            );

            $role->syncPermissions($rolePermissions[$roleEnum->value]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
