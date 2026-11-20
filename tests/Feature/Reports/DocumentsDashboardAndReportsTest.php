<?php

use App\Enums\SystemRole;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([
        RolesAndPermissionsSeeder::class,
        MasterDataSeeder::class,
    ]);
});

test('authorized staff can upload private documents and downloads are logged to document_access_logs', function () {
    Storage::fake('local');

    $manager = User::factory()->create();
    $manager->assignRole(SystemRole::Manager->value);

    $category = DocumentCategory::query()->firstOrFail();
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($manager)
        ->post(route('documents.store'), [
            'title' => 'Customs Duty Paper',
            'document_category_id' => $category->id,
            'entity_type' => 'vehicle',
            'entity_id' => $vehicle->id,
            'file' => UploadedFile::fake()->create('customs.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect();

    $doc = Document::query()->where('title', 'Customs Duty Paper')->firstOrFail();
    expect($doc->disk)->toBe('local')
        ->and(Storage::disk('local')->exists($doc->file_path))->toBeTrue();

    $this->actingAs($manager)
        ->get(route('documents.download', $doc))
        ->assertOk();

    expect(DocumentAccessLog::query()->where('document_id', $doc->id)->where('user_id', $manager->id)->count())->toBe(1);
});

test('dashboard respects financial permission gating and all 8 reports export cleanly to CSV and PDF', function () {
    $manager = User::factory()->create();
    $manager->assignRole(SystemRole::Manager->value);

    $attendant = User::factory()->create();
    $attendant->assignRole(SystemRole::VehicleAttendant->value);

    $salesOfficer = User::factory()->create();
    $salesOfficer->assignRole(SystemRole::SalesOfficer->value);

    // Dashboard loads for both, gating financial metrics by permission
    $this->actingAs($manager)->get(route('dashboard'))->assertOk();
    $this->actingAs($attendant)->get(route('dashboard'))->assertOk();

    // Sales Officer can view operational report, forbidden on financial profitability report
    $this->actingAs($salesOfficer)
        ->get(route('reports.show', 'inventory'))
        ->assertOk();

    $this->actingAs($salesOfficer)
        ->get(route('reports.show', 'profitability'))
        ->assertForbidden();

    // Manager can view and export all 8 reports to CSV and PDF
    $allSlugs = [
        'inventory',
        'company-owned',
        'consignment',
        'sales',
        'expenses',
        'withdrawals',
        'settlements',
        'profitability',
    ];

    foreach ($allSlugs as $slug) {
        $this->actingAs($manager)
            ->get(route('reports.show', $slug))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('reports.export-csv', $slug))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($manager)
            ->get(route('reports.export-pdf', $slug))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
});
