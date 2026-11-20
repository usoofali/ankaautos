<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChargeTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterDataItemRequest;
use App\Models\ChargeCategory;
use App\Models\DocumentCategory;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleCondition;
use App\Models\VehicleModel;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataController extends Controller
{
    /**
     * @var array<string, class-string<Model>>
     */
    private const TYPES = [
        'brands' => VehicleBrand::class,
        'models' => VehicleModel::class,
        'categories' => VehicleCategory::class,
        'conditions' => VehicleCondition::class,
        'expense_categories' => ExpenseCategory::class,
        'charge_categories' => ChargeCategory::class,
        'payment_methods' => PaymentMethod::class,
        'financial_accounts' => FinancialAccount::class,
        'document_categories' => DocumentCategory::class,
    ];

    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $defaultTab = $user->can('master_data.manage') ? 'brands' : 'charge_categories';
        $tab = (string) $request->string('tab', $defaultTab);

        if (! array_key_exists($tab, self::TYPES)) {
            $tab = $defaultTab;
        }

        if (! $user->can('master_data.manage') && $tab !== 'charge_categories') {
            abort(403, 'You do not have permission to manage this catalog section.');
        }

        $search = trim((string) $request->string('search'));
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;

        $modelClass = self::TYPES[$tab];
        $query = $modelClass::query();

        if ($tab === 'models') {
            $query->with('brand');
        }

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($tab === 'conditions') {
            $query->orderBy('severity_order')->orderBy('name');
        } else {
            $query->orderBy('name');
        }

        $items = $query->paginate($perPage)->withQueryString();

        $brands = VehicleBrand::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        return Inertia::render('admin/master-data/Index', [
            'activeTab' => $tab,
            'items' => $items,
            'brands' => $brands,
            'chargeTransactionTypes' => ChargeTransactionType::options(),
            'canManageAllMasterData' => $user->can('master_data.manage'),
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(MasterDataItemRequest $request, string $type): RedirectResponse
    {
        $modelClass = $this->resolveModelClass($type);
        $data = $this->prepareAttributes($type, $request->validated());

        /** @var Model $record */
        $record = $modelClass::query()->create($data);

        $this->auditLogger->audit(
            event: "master_data.{$type}.created",
            auditable: $record,
            newValues: $record->toArray(),
        );

        $this->auditLogger->activity(
            action: "master_data.{$type}.created",
            description: "Added {$this->humanType($type)} entry: {$record->getAttribute('name')}.",
            subject: $record,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => ucfirst($this->humanType($type)).' created successfully.',
        ]);

        return back();
    }

    public function update(MasterDataItemRequest $request, string $type, int $id): RedirectResponse
    {
        $modelClass = $this->resolveModelClass($type);
        /** @var Model $record */
        $record = $modelClass::query()->findOrFail($id);
        $oldValues = $record->toArray();

        $record->update($this->prepareAttributes($type, $request->validated()));

        $this->auditLogger->audit(
            event: "master_data.{$type}.updated",
            auditable: $record,
            oldValues: $oldValues,
            newValues: $record->toArray(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => ucfirst($this->humanType($type)).' updated successfully.',
        ]);

        return back();
    }

    public function toggleActive(Request $request, string $type, int $id): RedirectResponse
    {
        $this->authorizeType($request, $type);
        $modelClass = $this->resolveModelClass($type);
        /** @var Model $record */
        $record = $modelClass::query()->findOrFail($id);

        $previous = (bool) $record->getAttribute('is_active');
        $record->update(['is_active' => ! $previous]);

        $this->auditLogger->audit(
            event: "master_data.{$type}.".(! $previous ? 'activated' : 'deactivated'),
            auditable: $record,
            oldValues: ['is_active' => $previous],
            newValues: ['is_active' => ! $previous],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => ucfirst($this->humanType($type)).' '.(! $previous ? 'activated' : 'deactivated').' successfully.',
        ]);

        return back();
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        $this->authorizeType($request, $type);
        $modelClass = $this->resolveModelClass($type);
        /** @var Model $record */
        $record = $modelClass::query()->findOrFail($id);

        if ($type === 'brands' && $record instanceof VehicleBrand && $record->models()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Cannot delete this brand because it has vehicle models linked to it. Deactivate it instead.',
            ]);

            return back();
        }

        try {
            $snapshot = $record->toArray();
            $record->delete();

            $this->auditLogger->audit(
                event: "master_data.{$type}.deleted",
                auditable: $record,
                oldValues: $snapshot,
            );

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => ucfirst($this->humanType($type)).' deleted successfully.',
            ]);
        } catch (QueryException) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'This record is referenced by historical transactions and cannot be deleted. Please deactivate it instead.',
            ]);
        }

        return back();
    }

    /**
     * @return class-string<Model>
     */
    private function resolveModelClass(string $type): string
    {
        if (! array_key_exists($type, self::TYPES)) {
            abort(404);
        }

        return self::TYPES[$type];
    }

    private function authorizeType(Request $request, string $type): void
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        if ($type === 'charge_categories') {
            abort_unless($user->can('charges.manage_categories') || $user->can('master_data.manage'), 403);

            return;
        }

        abort_unless($user->can('master_data.manage'), 403);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $type, array $validated): array
    {
        if (in_array($type, ['brands', 'categories', 'conditions'], true) && isset($validated['name'])) {
            $validated['slug'] = Str::slug((string) $validated['name']);
        }

        return $validated;
    }

    private function humanType(string $type): string
    {
        return str_replace('_', ' ', Str::singular($type));
    }
}
