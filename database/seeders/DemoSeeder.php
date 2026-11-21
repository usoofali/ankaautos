<?php

namespace Database\Seeders;

use App\Actions\Consignments\CreateConsignmentAction;
use App\Actions\Expenses\RecordVehicleExpenseAction;
use App\Actions\Reservations\CreateReservationAction;
use App\Actions\Sales\CreateSaleAction;
use App\Enums\CommissionType;
use App\Enums\ExpenseBearer;
use App\Enums\OwnerCategory;
use App\Enums\OwnershipType;
use App\Enums\VehicleStatus;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleCondition;
use App\Models\VehicleModel;
use App\Models\VehicleOwner;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            MasterDataSeeder::class,
            AdminUserSeeder::class,
        ]);

        /** @var User $admin */
        $admin = User::query()->where('email', 'admin@ankaautos.com')->firstOrFail();
        $method = PaymentMethod::query()->firstOrFail();
        $account = FinancialAccount::query()->firstOrFail();
        $expenseCategory = ExpenseCategory::query()->firstOrFail();

        $toyota = VehicleBrand::query()->where('name', 'Toyota')->firstOrFail();
        $lexus = VehicleBrand::query()->where('name', 'Lexus')->firstOrFail();
        $mercedes = VehicleBrand::query()->where('name', 'Mercedes-Benz')->firstOrFail();
        $honda = VehicleBrand::query()->where('name', 'Honda')->firstOrFail();

        $camry = VehicleModel::query()->where('vehicle_brand_id', $toyota->id)->where('name', 'Camry')->first()
            ?? VehicleModel::query()->create(['vehicle_brand_id' => $toyota->id, 'name' => 'Camry', 'is_active' => true]);
        $rx350 = VehicleModel::query()->where('vehicle_brand_id', $lexus->id)->where('name', 'RX 350')->first()
            ?? VehicleModel::query()->create(['vehicle_brand_id' => $lexus->id, 'name' => 'RX 350', 'is_active' => true]);
        $gle = VehicleModel::query()->where('vehicle_brand_id', $mercedes->id)->first()
            ?? VehicleModel::query()->create(['vehicle_brand_id' => $mercedes->id, 'name' => 'GLE 350', 'is_active' => true]);
        $accord = VehicleModel::query()->where('vehicle_brand_id', $honda->id)->first()
            ?? VehicleModel::query()->create(['vehicle_brand_id' => $honda->id, 'name' => 'Accord', 'is_active' => true]);

        $sedan = VehicleCategory::query()->where('name', 'Sedan')->first() ?? VehicleCategory::query()->firstOrFail();
        $suv = VehicleCategory::query()->where('name', 'SUV')->first() ?? VehicleCategory::query()->firstOrFail();
        $foreignUsed = VehicleCondition::query()->where('name', 'Foreign Used (Tokunbo)')->first() ?? VehicleCondition::query()->firstOrFail();
        $nigeriaUsed = VehicleCondition::query()->where('name', 'Nigerian Used')->first() ?? VehicleCondition::query()->firstOrFail();

        // Owners
        $owner1 = VehicleOwner::query()->firstOrCreate(
            ['phone' => '+2348034411223'],
            [
                'reference_number' => 'ANK-OWN-DEMO-001',
                'owner_category' => OwnerCategory::Individual,
                'name' => 'Alhaji Musa Ibrahim',
                'email' => 'musa.ibrahim@example.ng',
                'address' => 'Maitama District, Abuja',
                'bank_name' => 'GTBank',
                'bank_account_name' => 'Musa Ibrahim',
                'bank_account_number' => '0123456789',
                'created_by' => $admin->id,
            ],
        );

        $owner2 = VehicleOwner::query()->firstOrCreate(
            ['phone' => '+2348097788990'],
            [
                'reference_number' => 'ANK-OWN-DEMO-002',
                'owner_category' => OwnerCategory::Organization,
                'name' => 'Zuma Fleet Holdings Ltd',
                'email' => 'fleet@zuma.example.ng',
                'address' => 'Central Business District, Abuja',
                'bank_name' => 'Zenith Bank',
                'bank_account_name' => 'Zuma Fleet Holdings Ltd',
                'bank_account_number' => '1019283746',
                'created_by' => $admin->id,
            ],
        );

        // Customers
        $customer1 = Customer::query()->firstOrCreate(
            ['phone' => '+2348051122334'],
            [
                'reference_number' => 'ANK-CUS-DEMO-001',
                'name' => 'Engr. Chinedu Okafor',
                'email' => 'chinedu.okafor@example.ng',
                'address' => 'Gwarinpa Estate, Abuja',
                'created_by' => $admin->id,
            ],
        );

        $customer2 = Customer::query()->firstOrCreate(
            ['phone' => '+2348065544332'],
            [
                'reference_number' => 'ANK-CUS-DEMO-002',
                'name' => 'Dr. Amina Bello',
                'email' => 'amina.bello@example.ng',
                'address' => 'Wuse II, Abuja',
                'created_by' => $admin->id,
            ],
        );

        // 1. Available Company-Owned Vehicle (Publicly Listed)
        $v1 = Vehicle::query()->firstOrCreate(
            ['reference_number' => 'ANK-VEH-DEMO-001'],
            [
                'slug' => '2021-toyota-camry-xse-pearl-white-demo-1',
                'ownership_type' => OwnershipType::CompanyOwned,
                'vehicle_brand_id' => $toyota->id,
                'vehicle_model_id' => $camry->id,
                'vehicle_category_id' => $sedan->id,
                'vehicle_condition_id' => $foreignUsed->id,
                'year' => 2021,
                'vin' => '4T1K61AK5MU500001',
                'exterior_colour' => 'Pearl White',
                'interior_colour' => 'Black Leather',
                'mileage' => 42500,
                'status' => VehicleStatus::Available,
                'location' => 'Main Showroom Bay A1',
                'received_at' => now()->subDays(12)->toDateString(),
                'received_by' => $admin->id,
                'acquisition_cost' => '24500000.00',
                'advertised_price' => '28500000.00',
                'minimum_price' => '27000000.00',
                'general_description' => 'Clean Tokunbo 2021 Toyota Camry with full customs duty paid, panoramic sunroof, lane assist, and chilled AC. Buy and drive.',
                'is_public_listing' => true,
            ],
        );

        // 2. Active Consignment Vehicle (Publicly Listed)
        $v2 = Vehicle::query()->firstOrCreate(
            ['reference_number' => 'ANK-VEH-DEMO-002'],
            [
                'slug' => '2020-lexus-rx-350-fsport-obsidian-demo-2',
                'ownership_type' => OwnershipType::Consignment,
                'vehicle_brand_id' => $lexus->id,
                'vehicle_model_id' => $rx350->id,
                'vehicle_category_id' => $suv->id,
                'vehicle_condition_id' => $foreignUsed->id,
                'year' => 2020,
                'vin' => '2T2HZMDA9LC200002',
                'exterior_colour' => 'Obsidian Black',
                'interior_colour' => 'Roast Brown Leather',
                'mileage' => 38200,
                'status' => VehicleStatus::Available,
                'location' => 'Main Showroom Bay B2',
                'received_at' => now()->subDays(8)->toDateString(),
                'received_by' => $admin->id,
                'advertised_price' => '52000000.00',
                'minimum_price' => '49500000.00',
                'general_description' => 'Executive 2020 Lexus RX 350 with 360-degree camera, Mark Levinson audio, power tailgate, and spotless interior.',
                'is_public_listing' => true,
            ],
        );

        if ($v2->activeConsignment === null) {
            $consignment = app(CreateConsignmentAction::class)->execute([
                'vehicle_id' => $v2->id,
                'vehicle_owner_id' => $owner1->id,
                'received_at' => now()->subDays(8)->toDateString(),
                'agreed_selling_price' => '52000000.00',
                'minimum_acceptable_price' => '49500000.00',
                'commission_type' => CommissionType::Percentage->value,
                'commission_value' => '4.00',
            ], $admin);

            app(RecordVehicleExpenseAction::class)->execute([
                'vehicle_id' => $v2->id,
                'expense_category_id' => $expenseCategory->id,
                'borne_by' => ExpenseBearer::OwnerDeductible->value,
                'description' => 'Full interior detailing, ceramic wax polish & engine bay steam wash',
                'amount' => '180000.00',
                'expense_date' => now()->subDays(6)->toDateString(),
                'vendor_name' => 'Abuja Auto Spa',
            ], $admin);

            unset($consignment);
        }

        // 3. Reserved Company-Owned Vehicle (with mandatory deposit)
        $v3 = Vehicle::query()->firstOrCreate(
            ['reference_number' => 'ANK-VEH-DEMO-003'],
            [
                'slug' => '2019-mercedes-benz-gle-350-silver-demo-3',
                'ownership_type' => OwnershipType::CompanyOwned,
                'vehicle_brand_id' => $mercedes->id,
                'vehicle_model_id' => $gle->id,
                'vehicle_category_id' => $suv->id,
                'vehicle_condition_id' => $foreignUsed->id,
                'year' => 2019,
                'vin' => '4JGDA5JB2KA300003',
                'exterior_colour' => 'Iridium Silver',
                'interior_colour' => 'Black Leather',
                'mileage' => 54000,
                'status' => VehicleStatus::Available,
                'location' => 'VIP Lot C1',
                'received_at' => now()->subDays(15)->toDateString(),
                'received_by' => $admin->id,
                'acquisition_cost' => '36000000.00',
                'advertised_price' => '41500000.00',
                'general_description' => 'Executive SUV in pristine mechanical condition with verified mileage and complete customs clearance.',
                'is_public_listing' => true,
            ],
        );

        if ($v3->status === VehicleStatus::Available) {
            app(CreateReservationAction::class)->execute([
                'vehicle_id' => $v3->id,
                'customer_id' => $customer1->id,
                'agreed_price' => '41000000.00',
                'deposit_amount' => '5000000.00',
                'payment_method_id' => $method->id,
                'financial_account_id' => $account->id,
                'paid_at' => now()->subDays(2)->toDateString(),
                'notes' => 'Buyer completing bank transfer balance next week.',
            ], $admin);
        }

        // 4. Sold Consignment Vehicle (generates Completed Sale + Ready Owner Settlement)
        $v4 = Vehicle::query()->firstOrCreate(
            ['reference_number' => 'ANK-VEH-DEMO-004'],
            [
                'slug' => '2018-honda-accord-touring-blue-demo-4',
                'ownership_type' => OwnershipType::Consignment,
                'vehicle_brand_id' => $honda->id,
                'vehicle_model_id' => $accord->id,
                'vehicle_category_id' => $sedan->id,
                'vehicle_condition_id' => $nigeriaUsed->id,
                'year' => 2018,
                'vin' => '1HGCV2F93JA400004',
                'exterior_colour' => 'Deep Blue',
                'interior_colour' => 'Grey Leather',
                'mileage' => 68000,
                'status' => VehicleStatus::Available,
                'location' => 'Main Showroom Bay A4',
                'received_at' => now()->subDays(20)->toDateString(),
                'received_by' => $admin->id,
                'advertised_price' => '16500000.00',
                'general_description' => 'First-owner Nigerian-used 2018 Honda Accord with complete service history.',
                'is_public_listing' => true,
            ],
        );

        if ($v4->status === VehicleStatus::Available) {
            app(CreateConsignmentAction::class)->execute([
                'vehicle_id' => $v4->id,
                'vehicle_owner_id' => $owner2->id,
                'received_at' => now()->subDays(20)->toDateString(),
                'agreed_selling_price' => '16500000.00',
                'commission_type' => CommissionType::Fixed->value,
                'commission_value' => '800000.00',
            ], $admin);

            app(CreateSaleAction::class)->execute([
                'vehicle_id' => $v4->id,
                'customer_id' => $customer2->id,
                'list_price' => '16500000.00',
                'initial_payment_amount' => '16500000.00',
                'payment_method_id' => $method->id,
                'financial_account_id' => $account->id,
                'paid_at' => now()->subDays(1)->toDateString(),
                'idempotency_key' => 'demo-sale-accord-001',
            ], $admin);
        }

        unset($v1);
    }
}
