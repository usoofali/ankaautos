<?php

namespace App\Http\Controllers;

use App\Models\Consignment;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\DocumentCategory;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $search = trim((string) $request->string('search'));
        $categoryId = $request->integer('category_id') ?: null;
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;

        $documents = Document::query()
            ->with(['category:id,name', 'uploader:id,name'])
            ->withCount('accessLogs')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('original_filename', 'like', "%{$search}%");
                });
            })
            ->when($categoryId !== null, fn ($q) => $q->where('document_category_id', $categoryId))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(static fn (Document $doc): array => [
                'id' => $doc->id,
                'reference_number' => $doc->reference_number,
                'title' => $doc->title,
                'category_name' => $doc->category->name ?? 'General',
                'original_filename' => $doc->original_filename,
                'mime_type' => $doc->mime_type,
                'file_size' => $doc->file_size,
                'uploaded_by' => $doc->uploader->name ?? 'System',
                'downloads_count' => $doc->access_logs_count,
                'created_at' => $doc->created_at?->toISOString(),
                'linked_label' => $doc->documentable_type !== null
                    ? class_basename($doc->documentable_type).' #'.$doc->documentable_id
                    : 'General Vault',
            ]);

        return Inertia::render('documents/Index', [
            'documents' => $documents,
            'categories' => DocumentCategory::query()->active()->orderBy('name')->get(['id', 'name']),
            'vehicles' => Vehicle::query()->with(['brand:id,name', 'model:id,name'])->latest('id')->limit(100)->get()->map(static fn (Vehicle $v): array => [
                'id' => $v->id,
                'label' => "{$v->reference_number} — {$v->displayName()}",
            ]),
            'canManage' => $user->can('documents.manage'),
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'document_category_id' => ['nullable', 'integer', Rule::exists('document_categories', 'id')],
            'entity_type' => ['nullable', Rule::in(['vehicle', 'sale', 'consignment', 'customer'])],
            'entity_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $path = $file->store('documents/'.now()->format('Y/m'), 'local');

        $morphMap = [
            'vehicle' => Vehicle::class,
            'sale' => Sale::class,
            'consignment' => Consignment::class,
            'customer' => Customer::class,
        ];

        $entityType = isset($validated['entity_type']) && $validated['entity_type'] !== ''
            ? ($morphMap[(string) $validated['entity_type']] ?? null)
            : null;
        $entityId = $entityType !== null && ! empty($validated['entity_id']) ? (int) $validated['entity_id'] : null;

        /** @var Document $document */
        $document = Document::query()->create([
            'reference_number' => $this->referenceGenerator->generate('DOC'),
            'document_category_id' => $validated['document_category_id'] ?? null,
            'documentable_type' => $entityType,
            'documentable_id' => $entityId,
            'title' => (string) $validated['title'],
            'disk' => 'local',
            'file_path' => (string) $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => (string) ($file->getClientMimeType() ?: 'application/octet-stream'),
            'file_size' => (int) $file->getSize(),
            'notes' => $validated['notes'] ?? null,
            'uploaded_by' => $actor->id,
        ]);

        $this->auditLogger->activity(
            action: 'document.uploaded',
            description: "Uploaded private document {$document->reference_number} ({$document->title})",
            subject: $document,
            actor: $actor,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Document {$document->reference_number} uploaded successfully.",
        ]);

        return back();
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        abort_unless(Storage::disk($document->disk)->exists($document->file_path), 404);

        DocumentAccessLog::query()->create([
            'document_id' => $document->id,
            'user_id' => $actor->id,
            'action' => 'downloaded',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'accessed_at' => now(),
        ]);

        return Storage::disk($document->disk)->download($document->file_path, $document->original_filename);
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $ref = $document->reference_number;
        $document->delete();

        $this->auditLogger->audit(
            event: 'document.deleted',
            auditable: $document,
            oldValues: ['reference_number' => $ref, 'title' => $document->title],
            reason: 'Archived document from vault',
            actor: $actor,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Document {$ref} archived from active vault.",
        ]);

        return back();
    }
}
