<?php

use App\Enums\ChargeTransactionType;
use App\Enums\SystemRole;
use App\Models\AuditLog;
use App\Models\ChargeCategory;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Services\BusinessSettingService;
use App\Services\ReferenceNumberGenerator;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed([
        RolesAndPermissionsSeeder::class,
        MasterDataSeeder::class,
    ]);
});

test('super admin can manage master data and cannot delete a brand with linked models', function () {
    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::SuperAdministrator->value);

    $this->actingAs($admin)
        ->post(route('admin.master-data.store', 'brands'), [
            'name' => 'Changan',
            'is_active' => true,
        ])
        ->assertRedirect();

    $brand = VehicleBrand::query()->where('name', 'Changan')->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('admin.master-data.toggle-active', ['type' => 'brands', 'id' => $brand->id]))
        ->assertRedirect();

    expect($brand->fresh()->is_active)->toBeFalse();

    $toyota = VehicleBrand::query()->where('name', 'Toyota')->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('admin.master-data.destroy', ['type' => 'brands', 'id' => $toyota->id]))
        ->assertRedirect();

    expect(VehicleBrand::query()->whereKey($toyota->id)->exists())->toBeTrue();
});

test('accountant can manage dynamic charge categories but cannot manage vehicle brands', function () {
    $accountant = User::factory()->create();
    $accountant->assignRole(SystemRole::Accountant->value);

    $this->actingAs($accountant)
        ->post(route('admin.master-data.store', 'charge_categories'), [
            'name' => 'Express Towing Fee',
            'description' => 'Emergency stand recovery charge.',
            'applicable_types' => [ChargeTransactionType::ConsignmentWithdrawal->value],
            'default_amount' => '45000.00',
            'is_active' => true,
        ])
        ->assertRedirect();

    expect(ChargeCategory::query()->where('name', 'Express Towing Fee')->exists())->toBeTrue();

    $this->actingAs($accountant)
        ->post(route('admin.master-data.store', 'brands'), [
            'name' => 'UnauthorizedBrand',
            'is_active' => true,
        ])
        ->assertForbidden();
});

test('business settings update clears cache and generates sequential references with new prefix', function () {
    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::SuperAdministrator->value);

    $this->actingAs($admin)
        ->put(route('admin.business-settings.update'), [
            'company_name' => 'Anka Auto Sales Ltd',
            'tagline' => 'Abuja Premier Car Stand',
            'address' => 'Plot 14, Wuse II, Abuja',
            'phone' => '+234 803 123 4567',
            'whatsapp_number' => '2348031234567',
            'email' => 'sales@ankaautos.com',
            'currency_code' => 'NGN',
            'currency_symbol' => '₦',
            'timezone' => 'Africa/Lagos',
            'reference_prefix' => 'AAS',
            'receipt_footer' => 'Official Anka Auto Sales Receipt.',
        ])
        ->assertRedirect();

    $settings = app(BusinessSettingService::class);
    $generator = app(ReferenceNumberGenerator::class);

    expect($settings->get('company_name'))->toBe('Anka Auto Sales Ltd')
        ->and($generator->generate('VEH', 2026))->toBe('AAS-VEH-2026-0001')
        ->and($generator->generate('VEH', 2026))->toBe('AAS-VEH-2026-0002');
});

test('audit logs are strictly immutable and reject updates or deletions', function () {
    $admin = User::factory()->create();

    $log = AuditLog::query()->create([
        'user_id' => $admin->id,
        'event' => 'test.event',
        'auditable_type' => User::class,
        'auditable_id' => $admin->id,
        'new_values' => ['status' => 'ok'],
        'created_at' => now(),
    ]);

    expect(fn () => $log->update(['event' => 'tampered']))
        ->toThrow(LogicException::class)
        ->and(fn () => $log->delete())
        ->toThrow(LogicException::class);
});
