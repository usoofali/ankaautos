<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Models\Advertisement;
use App\Models\AdvertisementShare;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AuditLogger;
use App\Services\BusinessSettingService;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class MarketingController extends Controller
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private BusinessSettingService $businessSettings,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;

        $vehicles = Vehicle::query()
            ->with(['brand:id,name', 'model:id,name', 'primaryPhoto'])
            ->withCount('photos')
            ->whereNotIn('status', [VehicleStatus::Archived->value])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('model', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(static function (Vehicle $v): array {
                $adCount = Advertisement::query()->where('vehicle_id', $v->id)->count();
                $shareCount = AdvertisementShare::query()->where('vehicle_id', $v->id)->count();

                return [
                    'id' => $v->id,
                    'reference_number' => $v->reference_number,
                    'slug' => $v->slug,
                    'title' => $v->displayName(),
                    'advertised_price' => $v->advertised_price,
                    'status_label' => $v->status->label(),
                    'status_variant' => $v->status->badgeVariant(),
                    'is_publicly_listed' => $v->is_publicly_listed,
                    'photos_count' => $v->photos_count,
                    'photo_url' => $v->primaryPhoto?->url(),
                    'has_ad' => $adCount > 0,
                    'shares_count' => $shareCount,
                ];
            });

        return Inertia::render('marketing/Index', [
            'vehicles' => $vehicles,
            'summary' => [
                'public_count' => Vehicle::query()->where('is_publicly_listed', true)->where('status', VehicleStatus::Available->value)->count(),
                'ads_count' => Advertisement::query()->count(),
                'shares_count' => AdvertisementShare::query()->count(),
            ],
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function builder(Request $request, Vehicle $vehicle): Response
    {
        /** @var User $user */
        $user = $request->user();
        $vehicle->load(['brand:id,name', 'model:id,name', 'condition:id,name', 'photos']);

        $advertisement = Advertisement::query()
            ->where('vehicle_id', $vehicle->id)
            ->latest('id')
            ->first();

        $business = $this->businessSettings->all();
        $businessName = $business['company_name'] ?? 'Anka Auto Sales';
        $phone = $business['whatsapp_number'] ?? $business['phone'] ?? '';
        $priceFormatted = $vehicle->advertised_price !== null
            ? Money::fromDecimal($vehicle->advertised_price)->format()
            : 'Call for Price';
        $listingUrl = route('public.listings.show', $vehicle->slug);

        $defaultHeadline = "{$vehicle->displayName()} — {$priceFormatted}";
        $defaultCaption = implode("\n", array_filter([
            "🚗 FOR SALE: {$vehicle->displayName()}",
            "💰 Price: {$priceFormatted}",
            "⚙️ {$vehicle->transmission->label()} | {$vehicle->fuel_type->label()} | {$vehicle->exterior_colour}",
            $vehicle->condition !== null ? "✨ Condition: {$vehicle->condition->name}" : null,
            $vehicle->mileage !== null ? '📍 Mileage: '.number_format($vehicle->mileage).' km' : null,
            '',
            "View full photos & live availability: {$listingUrl}",
            $phone !== '' ? "📞 Call / WhatsApp {$businessName}: {$phone}" : "🏢 {$businessName}",
            '#AnkaAutoSales #QualityVehicles #TrustedDeals #CarsInNigeria',
        ]));

        $shares = AdvertisementShare::query()
            ->where('vehicle_id', $vehicle->id)
            ->with('user:id,name')
            ->latest('shared_at')
            ->limit(20)
            ->get()
            ->map(static fn (AdvertisementShare $s): array => [
                'id' => $s->id,
                'platform' => $s->platform,
                'shared_by' => $s->user->name ?? 'System',
                'shared_at' => $s->shared_at->toISOString(),
            ])
            ->values()
            ->all();

        return Inertia::render('marketing/Builder', [
            'vehicle' => [
                'id' => $vehicle->id,
                'reference_number' => $vehicle->reference_number,
                'slug' => $vehicle->slug,
                'title' => $vehicle->displayName(),
                'advertised_price' => $vehicle->advertised_price,
                'is_publicly_listed' => $vehicle->is_publicly_listed,
                'status_label' => $vehicle->status->label(),
                'status_variant' => $vehicle->status->badgeVariant(),
                'photos' => $vehicle->photos->map(static fn ($p): array => [
                    'id' => $p->id,
                    'url' => $p->url(),
                    'caption' => $p->caption,
                    'is_primary' => $p->is_primary,
                ])->values()->all(),
            ],
            'advertisement' => [
                'id' => $advertisement?->id,
                'headline' => $advertisement->headline ?? $defaultHeadline,
                'caption' => $advertisement->caption ?? $defaultCaption,
                'selected_photo_ids' => $advertisement?->selected_photo_ids
                    ?? $vehicle->photos->pluck('id')->values()->all(),
            ],
            'listingUrl' => $listingUrl,
            'shares' => $shares,
            'canManage' => $user->can('marketing.manage'),
        ]);
    }

    public function saveAdvertisement(Request $request, Vehicle $vehicle): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'headline' => ['required', 'string', 'max:200'],
            'caption' => ['required', 'string', 'max:4000'],
            'selected_photo_ids' => ['nullable', 'array'],
            'selected_photo_ids.*' => ['integer'],
            'is_publicly_listed' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('is_publicly_listed', $validated)) {
            $vehicle->update(['is_publicly_listed' => (bool) $validated['is_publicly_listed']]);
        }

        $advertisement = Advertisement::query()->where('vehicle_id', $vehicle->id)->latest('id')->first();
        if ($advertisement === null) {
            $advertisement = Advertisement::query()->create([
                'reference_number' => $this->referenceGenerator->generate('AD'),
                'vehicle_id' => $vehicle->id,
                'headline' => (string) $validated['headline'],
                'caption' => (string) $validated['caption'],
                'selected_photo_ids' => $validated['selected_photo_ids'] ?? [],
                'is_active' => true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        } else {
            $advertisement->update([
                'headline' => (string) $validated['headline'],
                'caption' => (string) $validated['caption'],
                'selected_photo_ids' => $validated['selected_photo_ids'] ?? [],
                'updated_by' => $actor->id,
            ]);
        }

        $this->auditLogger->activity(
            action: 'marketing.advertisement_saved',
            description: "Updated advertisement copy for {$vehicle->reference_number}",
            subject: $advertisement,
            actor: $actor,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Advertisement copy and listing visibility saved.',
        ]);

        return back();
    }

    public function logShare(Request $request, Vehicle $vehicle): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'platform' => ['required', Rule::in(['whatsapp', 'facebook', 'instagram', 'link_copy'])],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $advertisement = Advertisement::query()->where('vehicle_id', $vehicle->id)->latest('id')->first();

        AdvertisementShare::query()->create([
            'advertisement_id' => $advertisement?->id,
            'vehicle_id' => $vehicle->id,
            'platform' => (string) $validated['platform'],
            'shared_by' => $actor->id,
            'shared_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Social share logged to marketing history.',
        ]);

        return back();
    }

    public function downloadPhotoPack(Request $request, Vehicle $vehicle): BinaryFileResponse|RedirectResponse
    {
        $vehicle->load('photos');
        $idsParam = $request->input('photo_ids', []);
        $requestedIds = is_array($idsParam)
            ? array_map(static fn ($id): int => (int) $id, $idsParam)
            : [];

        $photos = $requestedIds !== []
            ? $vehicle->photos->whereIn('id', $requestedIds)
            : $vehicle->photos;

        if ($photos->isEmpty()) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => 'No vehicle photos are available to bundle.',
            ]);

            return back();
        }

        $zipPath = storage_path('app/private/photo-pack-'.$vehicle->id.'-'.uniqid('', true).'.zip');
        $zipDir = dirname($zipPath);
        if (! is_dir($zipDir)) {
            mkdir($zipDir, 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Could not assemble photo ZIP archive.',
            ]);

            return back();
        }

        $index = 1;
        foreach ($photos as $photo) {
            $fullPath = Storage::disk($photo->disk)->path($photo->file_path);
            if (is_file($fullPath)) {
                $ext = pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'jpg';
                $zip->addFile($fullPath, sprintf('%s-photo-%02d.%s', $vehicle->reference_number, $index, $ext));
                $index++;
            }
        }
        $zip->close();

        return response()->download($zipPath, "{$vehicle->reference_number}-Photos.zip")->deleteFileAfterSend(true);
    }
}
