<?php

namespace App\Enums;

enum SystemRole: string
{
    case SuperAdministrator = 'super_administrator';
    case Manager = 'manager';
    case InventoryOfficer = 'inventory_officer';
    case SalesOfficer = 'sales_officer';
    case Accountant = 'accountant';
    case VehicleAttendant = 'vehicle_attendant';
    case Viewer = 'viewer';

    public function displayName(): string
    {
        return match ($this) {
            self::SuperAdministrator => 'Super Administrator',
            self::Manager => 'Manager',
            self::InventoryOfficer => 'Inventory Officer',
            self::SalesOfficer => 'Sales Officer',
            self::Accountant => 'Accountant / Finance Officer',
            self::VehicleAttendant => 'Vehicle Attendant',
            self::Viewer => 'Viewer',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SuperAdministrator => 'Highest level of system administration with complete control over users, roles, configuration, operations, and financial oversight.',
            self::Manager => 'Oversees daily dealership operations, supervises sales and consignments, approves discounts, cancellations, and operational exceptions.',
            self::InventoryOfficer => 'Responsible for registering vehicles, updating vehicle specifications, managing photographs and documents, and maintaining inventory and consignment records.',
            self::SalesOfficer => 'Manages customer registration, enquiries, vehicle inspections, reservations, sales processing, and marketing advertisements.',
            self::Accountant => 'Responsible for recording customer payments, managing vehicle expenses and dynamic charges, preparing financial reports, and processing owner settlements.',
            self::VehicleAttendant => 'Handles physical car-stand activities including washing, maintenance logging, condition updates, vehicle movements, and handover assistance.',
            self::Viewer => 'Read-only access to operational records and authorized summaries without permission to create, modify, or delete data.',
        };
    }

    public function purpose(): string
    {
        return match ($this) {
            self::SuperAdministrator => 'System governance, security administration, master catalog configuration, and executive oversight.',
            self::Manager => 'Operational supervision, commercial approval controls, and dealership performance monitoring.',
            self::InventoryOfficer => 'Maintaining an accurate, up-to-date central registry of all company-owned and consignment vehicles.',
            self::SalesOfficer => 'Driving customer conversions from initial enquiry through reservation, sale confirmation, and vehicle marketing.',
            self::Accountant => 'Safeguarding financial integrity across payments, expenses, service charges, withdrawals, and owner settlements.',
            self::VehicleAttendant => 'Tracking physical vehicle care, stand movements, and readiness for customer inspection and handover.',
            self::Viewer => 'Enabling stakeholders or auditors to monitor dealership operations without altering records.',
        };
    }

    public function responsibilitiesSummary(): string
    {
        return match ($this) {
            self::SuperAdministrator => 'Manage staff accounts and roles, configure business settings and master data, oversee all operational/financial modules, and inspect immutable audit logs.',
            self::Manager => 'Monitor inventory and sales, approve discounts/price adjustments/early handovers/cancellations, override returning VINs, review expenses and settlements, and inspect audit trails.',
            self::InventoryOfficer => 'Register and update vehicles, upload photos and vehicle documents, log reception/condition updates, register vehicle owners and consignments, and initiate withdrawals.',
            self::SalesOfficer => 'Register customers, track enquiries and inspections, create reservations and sales, prepare vehicle advertisements, share listings on WhatsApp/Facebook/Instagram, and record handovers.',
            self::Accountant => 'Record customer and withdrawal payments, record and pay vehicle expenses, manage dynamic charge categories and transaction charges, disburse owner settlements, and export financial reports.',
            self::VehicleAttendant => 'Log vehicle washing, maintenance, movements, preparation progress, and condition remarks without access to sensitive financial data.',
            self::Viewer => 'View vehicle inventory, consignments, customers, reservations, sales, expenses, settlements, and operational reports in read-only mode.',
        };
    }
}
