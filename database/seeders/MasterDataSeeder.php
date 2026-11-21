<?php

namespace Database\Seeders;

use App\Enums\ChargeTransactionType;
use App\Models\BusinessSetting;
use App\Models\ChargeCategory;
use App\Models\DocumentCategory;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\PaymentMethod;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleCondition;
use App\Models\VehicleModel;
use App\Services\BusinessSettingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBusinessSettings();
        $this->seedBrandsAndModels();
        $this->seedVehicleCategories();
        $this->seedVehicleConditions();
        $this->seedExpenseCategories();
        $this->seedChargeCategories();
        $this->seedPaymentMethods();
        $this->seedDocumentCategories();
    }

    private function seedBusinessSettings(): void
    {
        $service = app(BusinessSettingService::class);
        $defaults = $service->defaults();

        $meta = [
            'company_name' => ['group' => 'identity', 'label' => 'Dealership Name', 'type' => 'string'],
            'tagline' => ['group' => 'identity', 'label' => 'Tagline', 'type' => 'string'],
            'address' => ['group' => 'contact', 'label' => 'Showroom Address', 'type' => 'text'],
            'phone' => ['group' => 'contact', 'label' => 'Primary Phone', 'type' => 'string'],
            'whatsapp_number' => ['group' => 'contact', 'label' => 'WhatsApp Business Number', 'type' => 'string'],
            'email' => ['group' => 'contact', 'label' => 'Official Email', 'type' => 'string'],
            'currency_code' => ['group' => 'localization', 'label' => 'Currency Code', 'type' => 'string'],
            'currency_symbol' => ['group' => 'localization', 'label' => 'Currency Symbol', 'type' => 'string'],
            'timezone' => ['group' => 'localization', 'label' => 'Timezone', 'type' => 'string'],
            'reference_prefix' => ['group' => 'operations', 'label' => 'Reference Prefix', 'type' => 'string'],
            'receipt_footer' => ['group' => 'documents', 'label' => 'Receipt & Document Footer', 'type' => 'text'],
        ];

        foreach ($defaults as $key => $value) {
            BusinessSetting::query()->firstOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => $meta[$key]['group'] ?? 'general',
                    'label' => $meta[$key]['label'] ?? Str::headline($key),
                    'type' => $meta[$key]['type'] ?? 'string',
                ],
            );
        }

        $service->clearCache();
    }

    private function seedBrandsAndModels(): void
    {
        $catalog = [
            'Toyota' => ['Camry', 'Corolla', 'Highlander', 'RAV4', 'Land Cruiser', 'Land Cruiser Prado', 'Hilux', 'Sienna', 'Venza', 'Avalon'],
            'Lexus' => ['RX 350', 'ES 350', 'GX 460', 'LX 570', 'IS 250', 'NX 300'],
            'Mercedes-Benz' => ['C300', 'E350', 'GLC 300', 'GLE 350', 'GLE 450', 'ML 350', 'CLA 250', 'S550'],
            'Honda' => ['Accord', 'Civic', 'CR-V', 'Pilot', 'Odyssey'],
            'Hyundai' => ['Elantra', 'Sonata', 'Tucson', 'Santa Fe', 'Palisade'],
            'Kia' => ['Rio', 'Cerato', 'Sportage', 'Sorento', 'Optima'],
            'Ford' => ['Edge', 'Explorer', 'Escape', 'F-150', 'Ranger'],
            'Nissan' => ['Altima', 'Sentra', 'Pathfinder', 'Murano', 'X-Trail'],
            'Land Rover' => ['Range Rover Vogue', 'Range Rover Sport', 'Range Rover Evoque', 'Discovery'],
            'BMW' => ['328i', '530i', 'X3', 'X5', 'X6'],
            'Acura' => ['MDX', 'RDX', 'TLX', 'ZDX'],
            'Peugeot' => ['301', '508', '3008', '5008'],
        ];

        foreach ($catalog as $brandName => $models) {
            /** @var VehicleBrand $brand */
            $brand = VehicleBrand::query()->firstOrCreate(
                ['name' => $brandName],
                ['slug' => Str::slug($brandName), 'is_active' => true],
            );

            foreach ($models as $modelName) {
                VehicleModel::query()->firstOrCreate(
                    ['vehicle_brand_id' => $brand->id, 'name' => $modelName],
                    ['is_active' => true],
                );
            }
        }
    }

    private function seedVehicleCategories(): void
    {
        $categories = [
            ['name' => 'Sedan', 'description' => 'Four-door passenger saloon vehicles.'],
            ['name' => 'SUV', 'description' => 'Sport utility and crossover vehicles.'],
            ['name' => 'Pickup Truck', 'description' => 'Open-bed utility and double-cabin trucks.'],
            ['name' => 'Coupe', 'description' => 'Two-door sporty passenger vehicles.'],
            ['name' => 'Hatchback', 'description' => 'Compact rear-hatch passenger vehicles.'],
            ['name' => 'Minivan / Bus', 'description' => 'Multi-passenger family vans and commercial buses.'],
            ['name' => 'Luxury / Executive', 'description' => 'Flagship executive and high-end vehicles.'],
        ];

        foreach ($categories as $category) {
            VehicleCategory::query()->firstOrCreate(
                ['name' => $category['name']],
                [
                    'slug' => Str::slug($category['name']),
                    'description' => $category['description'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedVehicleConditions(): void
    {
        $conditions = [
            ['name' => 'Excellent', 'description' => 'Showroom-ready condition with no known mechanical or body defects.', 'severity_order' => 1, 'badge_variant' => 'success'],
            ['name' => 'Very Good', 'description' => 'Clean condition with minimal normal wear.', 'severity_order' => 2, 'badge_variant' => 'success'],
            ['name' => 'Good', 'description' => 'Mechanically sound with minor cosmetic blemishes.', 'severity_order' => 3, 'badge_variant' => 'info'],
            ['name' => 'Fair', 'description' => 'Drivable condition requiring light servicing or touch-ups.', 'severity_order' => 4, 'badge_variant' => 'warning'],
            ['name' => 'Requires Repairs', 'description' => 'Has documented mechanical or bodywork issues needing attention.', 'severity_order' => 5, 'badge_variant' => 'danger'],
            ['name' => 'Under Maintenance', 'description' => 'Currently undergoing active workshop repair or servicing.', 'severity_order' => 6, 'badge_variant' => 'warning'],
        ];

        foreach ($conditions as $condition) {
            VehicleCondition::query()->firstOrCreate(
                ['name' => $condition['name']],
                [
                    'slug' => Str::slug($condition['name']),
                    'description' => $condition['description'],
                    'severity_order' => $condition['severity_order'],
                    'badge_variant' => $condition['badge_variant'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedExpenseCategories(): void
    {
        $categories = [
            ['name' => 'Vehicle Washing & Detailing', 'description' => 'Interior/exterior washing, polishing, and detailing costs.'],
            ['name' => 'Routine Servicing', 'description' => 'Oil change, filter replacement, and general tuning.'],
            ['name' => 'Mechanical Repairs', 'description' => 'Engine, gearbox, suspension, brake, or electrical repairs.'],
            ['name' => 'Bodywork & Painting', 'description' => 'Panel beating, dent removal, bumper work, and respraying.'],
            ['name' => 'Towing & Transportation', 'description' => 'Logistics, flatbed towing, and haulage charges.'],
            ['name' => 'Fuel & Diagnostics', 'description' => 'Fueling for test drives/movements and OBD diagnostics.'],
            ['name' => 'Documentation & Clearance', 'description' => 'Customs verification, roadworthiness, and paper processing.'],
            ['name' => 'Tyres & Battery', 'description' => 'Replacement tyres, wheel alignment, and batteries.'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::query()->firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description'], 'is_active' => true],
            );
        }
    }

    private function seedChargeCategories(): void
    {
        $charges = [
            [
                'name' => 'Consignment Sale Service Charge',
                'description' => 'Administrative or marketing fee applied upon closing a consignment sale.',
                'applicable_types' => [ChargeTransactionType::ConsignmentSale->value],
                'default_amount' => '50000.00',
            ],
            [
                'name' => 'Consignment Withdrawal Fee',
                'description' => 'Administrative fee applied when an owner withdraws a vehicle before sale.',
                'applicable_types' => [ChargeTransactionType::ConsignmentWithdrawal->value],
                'default_amount' => '35000.00',
            ],
            [
                'name' => 'Stand Parking Charge',
                'description' => 'Display and security parking fee billed for consignment vehicles.',
                'applicable_types' => [
                    ChargeTransactionType::ConsignmentWithdrawal->value,
                    ChargeTransactionType::ConsignmentSale->value,
                ],
                'default_amount' => '25000.00',
            ],
            [
                'name' => 'Washing & Detailing Charge',
                'description' => 'Service charge billed for stand washing and presentation.',
                'applicable_types' => [
                    ChargeTransactionType::ConsignmentWithdrawal->value,
                    ChargeTransactionType::VehicleService->value,
                ],
                'default_amount' => '15000.00',
            ],
            [
                'name' => 'Vehicle Preparation Charge',
                'description' => 'Charge for pre-display inspection, polishing, and photography.',
                'applicable_types' => [
                    ChargeTransactionType::ConsignmentWithdrawal->value,
                    ChargeTransactionType::ConsignmentSale->value,
                    ChargeTransactionType::VehicleService->value,
                ],
                'default_amount' => '20000.00',
            ],
            [
                'name' => 'Documentation & Handover Fee',
                'description' => 'Documentation handling charge applied to vehicle sales or releases.',
                'applicable_types' => [
                    ChargeTransactionType::Sale->value,
                    ChargeTransactionType::ConsignmentSale->value,
                ],
                'default_amount' => '25000.00',
            ],
        ];

        foreach ($charges as $charge) {
            ChargeCategory::query()->firstOrCreate(
                ['name' => $charge['name']],
                [
                    'description' => $charge['description'],
                    'applicable_types' => $charge['applicable_types'],
                    'default_amount' => $charge['default_amount'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedPaymentMethods(): void
    {
        $methods = [
            ['name' => 'Bank Transfer', 'code' => 'bank_transfer', 'requires_reference' => true],
            ['name' => 'POS Terminal', 'code' => 'pos', 'requires_reference' => true],
            ['name' => 'Cash', 'code' => 'cash', 'requires_reference' => false],
            ['name' => 'Cheque / Bank Draft', 'code' => 'cheque', 'requires_reference' => true],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->firstOrCreate(
                ['code' => $method['code']],
                [
                    'name' => $method['name'],
                    'requires_reference' => $method['requires_reference'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedDocumentCategories(): void
    {
        $categories = [
            ['name' => 'Vehicle Ownership / Customs Papers', 'description' => 'Customs duty papers, proof of ownership, and vehicle licenses.', 'is_private' => true],
            ['name' => 'Purchase Agreement / Invoice', 'description' => 'Acquisition invoices and receipts for company-owned vehicles.', 'is_private' => true],
            ['name' => 'Signed Consignment Agreement', 'description' => 'Executed consignment agreement signed by vehicle owner.', 'is_private' => true],
            ['name' => 'Vehicle Inspection Report', 'description' => 'Mechanical and body inspection sheets.', 'is_private' => true],
            ['name' => 'Maintenance & Expense Receipt', 'description' => 'Workshop invoices, spare parts receipts, and expense evidence.', 'is_private' => true],
            ['name' => 'Sales Agreement & Receipt', 'description' => 'Executed customer sales agreements and payment proofs.', 'is_private' => true],
            ['name' => 'Vehicle Handover Document', 'description' => 'Signed delivery or withdrawal handover checklist.', 'is_private' => true],
            ['name' => 'Owner / Customer ID Document', 'description' => 'NIN, Driver License, International Passport, or CAC registration.', 'is_private' => true],
        ];

        foreach ($categories as $category) {
            DocumentCategory::query()->firstOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'is_private' => $category['is_private'],
                    'is_active' => true,
                ],
            );
        }
    }
}
