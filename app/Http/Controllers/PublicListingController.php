<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Services\BusinessSettingService;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicListingController extends Controller
{
    public function __construct(
        private BusinessSettingService $businessSettings,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $brandId = $request->integer('brand_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;
        $maxPrice = $request->filled('max_price') ? (float) $request->input('max_price') : null;

        $vehicles = Vehicle::query()
            ->with([
                'brand:id,name',
                'model:id,name',
                'category:id,name',
                'condition:id,name',
                'primaryPhoto',
            ])
            ->where('is_publicly_listed', true)
            ->whereIn('status', [VehicleStatus::Available->value, VehicleStatus::Reserved->value])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('trim', 'like', "%{$search}%")
                        ->orWhere('exterior_colour', 'like', "%{$search}%")
                        ->orWhere('public_description', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('model', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($brandId !== null, fn ($q) => $q->where('vehicle_brand_id', $brandId))
            ->when($categoryId !== null, fn ($q) => $q->where('vehicle_category_id', $categoryId))
            ->when($maxPrice !== null && $maxPrice > 0, fn ($q) => $q->where('advertised_price', '<=', $maxPrice))
            ->latest('id')
            ->paginate(12)
            ->withQueryString()
            ->through(static fn (Vehicle $v): array => [
                'id' => $v->id,
                'slug' => $v->slug,
                'title' => $v->displayName(),
                'year' => $v->year,
                'brand' => $v->brand->name ?? '',
                'category' => $v->category->name ?? '',
                'condition' => $v->condition->name ?? '',
                'transmission' => $v->transmission->label(),
                'fuel_type' => $v->fuel_type->label(),
                'mileage' => $v->mileage,
                'exterior_colour' => $v->exterior_colour,
                'advertised_price' => $v->advertised_price,
                'status' => $v->status->value,
                'status_label' => $v->status === VehicleStatus::Reserved ? 'Reserved' : 'Available',
                'status_variant' => $v->status === VehicleStatus::Reserved ? 'warning' : 'success',
                'photo_url' => $v->primaryPhoto?->url(),
            ]);

        $business = $this->businessSettings->all();

        return Inertia::render('public/Catalog', [
            'vehicles' => $vehicles,
            'brands' => VehicleBrand::query()->active()->orderBy('name')->get(['id', 'name']),
            'categories' => VehicleCategory::query()->active()->orderBy('name')->get(['id', 'name']),
            'business' => [
                'name' => $business['company_name'] ?? 'Anka Auto Sales',
                'tagline' => $business['tagline'] ?? 'Quality Vehicles. Trusted Deals.',
                'phone' => $business['phone'] ?? '',
                'whatsapp' => $business['whatsapp_number'] ?? $business['phone'] ?? '',
                'email' => $business['email'] ?? '',
                'address' => $business['address'] ?? '',
            ],
            'filters' => [
                'search' => $search,
                'brand_id' => $brandId,
                'category_id' => $categoryId,
                'max_price' => $maxPrice,
            ],
        ])->withViewData([
            'ogTitle' => ($business['company_name'] ?? 'Anka Auto Sales').' — '.($business['tagline'] ?? 'Quality Vehicles. Trusted Deals.'),
            'ogDescription' => 'Browse verified quality vehicles available now at '.($business['company_name'] ?? 'Anka Auto Sales').'. Transparent pricing, clean documentation, and trusted deals.',
            'ogImage' => url('/og-image.png'),
            'ogUrl' => route('home'),
        ]);
    }

    public function show(Vehicle $vehicle): Response
    {
        abort_unless($vehicle->is_publicly_listed, 404);

        $vehicle->load([
            'brand:id,name',
            'model:id,name',
            'category:id,name',
            'condition:id,name',
            'photos',
        ]);

        $business = $this->businessSettings->all();

        $availabilityLabel = match ($vehicle->status) {
            VehicleStatus::Available => 'Available Now',
            VehicleStatus::Reserved => 'Reserved',
            VehicleStatus::Sold => 'Sold',
            default => 'Unavailable',
        };

        $availabilityVariant = match ($vehicle->status) {
            VehicleStatus::Available => 'success',
            VehicleStatus::Reserved => 'warning',
            VehicleStatus::Sold => 'danger',
            default => 'neutral',
        };

        $primaryPhoto = $vehicle->photos->firstWhere('is_primary', true) ?? $vehicle->photos->first();
        $ogImage = $primaryPhoto !== null ? url($primaryPhoto->url()) : url('/og-image.png');
        $priceText = $vehicle->advertised_price !== null
            ? Money::fromDecimal($vehicle->advertised_price)->format()
            : 'Call for Price';

        return Inertia::render('public/Show', [
            'vehicle' => [
                'slug' => $vehicle->slug,
                'reference_number' => $vehicle->reference_number,
                'title' => $vehicle->displayName(),
                'year' => $vehicle->year,
                'brand' => $vehicle->brand->name ?? '',
                'model' => $vehicle->model->name ?? '',
                'trim' => $vehicle->trim,
                'category' => $vehicle->category->name ?? '',
                'condition' => $vehicle->condition->name ?? '',
                'transmission' => $vehicle->transmission->label(),
                'fuel_type' => $vehicle->fuel_type->label(),
                'drive_type' => $vehicle->drive_type?->label(),
                'engine_size' => $vehicle->engine_size,
                'mileage' => $vehicle->mileage,
                'exterior_colour' => $vehicle->exterior_colour,
                'interior_colour' => $vehicle->interior_colour,
                'advertised_price' => $vehicle->advertised_price,
                'public_description' => $vehicle->public_description,
                'status' => $vehicle->status->value,
                'status_label' => $availabilityLabel,
                'status_variant' => $availabilityVariant,
                'is_available' => $vehicle->status === VehicleStatus::Available,
                'photos' => $vehicle->photos->map(static fn ($p): array => [
                    'id' => $p->id,
                    'url' => $p->url(),
                    'caption' => $p->caption,
                    'is_primary' => $p->is_primary,
                ])->values()->all(),
            ],
            'listingUrl' => route('public.listings.show', $vehicle->slug),
            'business' => [
                'name' => $business['company_name'] ?? 'Anka Auto Sales',
                'tagline' => $business['tagline'] ?? 'Quality Vehicles. Trusted Deals.',
                'phone' => $business['phone'] ?? '',
                'whatsapp' => $business['whatsapp_number'] ?? $business['phone'] ?? '',
                'email' => $business['email'] ?? '',
                'address' => $business['address'] ?? '',
            ],
        ])->withViewData([
            'ogTitle' => "{$vehicle->displayName()} — {$priceText} | ".($business['company_name'] ?? 'Anka Auto Sales'),
            'ogDescription' => $vehicle->public_description ?: "{$vehicle->displayName()} ({$vehicle->transmission->label()}, {$vehicle->fuel_type->label()}) — {$availabilityLabel}. Contact Anka Auto Sales today.",
            'ogImage' => $ogImage,
            'ogUrl' => route('public.listings.show', $vehicle->slug),
        ]);
    }
}
