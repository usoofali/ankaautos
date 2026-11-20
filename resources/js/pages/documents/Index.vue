<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Download, FileText, Trash2, Upload } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/anka/ConfirmDialog.vue';
import DataTableCard, {
    type DataTableColumn,
} from '@/components/anka/DataTableCard.vue';
import PageHeader from '@/components/anka/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormatting } from '@/composables/useFormatting';
import { dashboard } from '@/routes';
import { destroy, download, index, store } from '@/routes/documents';
import type { Paginated } from '@/types';

type DocumentRow = {
    id: number;
    reference_number: string;
    title: string;
    category_name: string;
    original_filename: string;
    mime_type: string;
    file_size: number;
    uploaded_by: string;
    downloads_count: number;
    created_at: string | null;
    linked_label: string;
};

const props = defineProps<{
    documents: Paginated<DocumentRow>;
    categories: Array<{ id: number; name: string }>;
    vehicles: Array<{ id: number; label: string }>;
    canManage: boolean;
    filters: {
        search: string;
        category_id: number | null;
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Document Vault', href: index() },
        ],
    },
});

const { formatDate } = useFormatting();
const uploadOpen = ref(false);
const deleteTarget = ref<DocumentRow | null>(null);

const uploadForm = useForm({
    title: '',
    document_category_id: props.categories[0]?.id ?? '',
    entity_type: 'vehicle',
    entity_id: '' as string | number,
    notes: '',
    file: null as File | null,
});

const columns: DataTableColumn[] = [
    { key: 'reference_number', label: 'Reference' },
    { key: 'title', label: 'Document' },
    { key: 'category_name', label: 'Category' },
    { key: 'file_size', label: 'Size', align: 'right' },
    { key: 'downloads_count', label: 'Downloads', align: 'right' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

function updateQuery(
    overrides: Record<string, string | number | undefined>,
): void {
    router.get(
        index.url(),
        {
            search: props.filters.search || undefined,
            category_id: props.filters.category_id || undefined,
            per_page: props.filters.per_page,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    uploadForm.file = target.files?.[0] ?? null;
}

function submitUpload(): void {
    uploadForm.post(store.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadOpen.value = false;
            uploadForm.reset('title', 'notes', 'file', 'entity_id');
        },
    });
}

function confirmArchive(): void {
    if (!deleteTarget.value) {
        return;
    }
    router.delete(destroy.url(deleteTarget.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <Head title="Private Document Vault" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
        <PageHeader
            title="Private Document Vault"
            description="Securely store customs papers, IDs, signed agreements, and receipts with audited download logs."
        >
            <template v-if="canManage" #actions>
                <Button
                    class="min-h-10 w-full sm:w-auto"
                    @click="uploadOpen = true"
                >
                    <Upload class="mr-1.5 size-4" aria-hidden="true" />
                    Upload Document
                </Button>
            </template>
        </PageHeader>

        <DataTableCard
            caption="Private dealership documents"
            :columns="columns"
            :rows="documents"
            :search="filters.search"
            search-placeholder="Search reference, title, filename..."
            :per-page="filters.per_page"
            empty-title="No documents stored yet"
            empty-description="Upload vehicle customs papers, identification cards, or signed agreements into the private vault."
            @update:search="
                (v) => updateQuery({ search: v || undefined, page: 1 })
            "
            @update:per-page="(p) => updateQuery({ per_page: p, page: 1 })"
        >
            <template #filters>
                <select
                    :value="filters.category_id ?? ''"
                    aria-label="Filter by document category"
                    class="min-h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    @change="
                        updateQuery({
                            category_id:
                                ($event.target as HTMLSelectElement).value ||
                                undefined,
                            page: 1,
                        })
                    "
                >
                    <option value="">All Categories</option>
                    <option
                        v-for="cat in categories"
                        :key="cat.id"
                        :value="cat.id"
                    >
                        {{ cat.name }}
                    </option>
                </select>
            </template>

            <template #mobile-card="{ row }">
                <div class="space-y-2.5">
                    <div class="flex items-start gap-2.5">
                        <FileText class="mt-0.5 size-5 shrink-0 text-primary" />
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ row.title }}</p>
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ row.reference_number }} ·
                                {{ row.category_name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatBytes(row.file_size) }} ·
                                {{ row.downloads_count }} downloads
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            as-child
                            variant="outline"
                            size="sm"
                            class="min-h-9 flex-1"
                        >
                            <a :href="download.url(row.id)">
                                <Download class="mr-1.5 size-3.5" />
                                Download
                            </a>
                        </Button>
                        <Button
                            v-if="canManage"
                            variant="ghost"
                            size="sm"
                            class="min-h-9 text-destructive"
                            @click="deleteTarget = row"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>
            </template>

            <template #row="{ row }">
                <td class="px-4 py-3.5">
                    <div class="font-mono text-xs font-semibold">
                        {{ row.reference_number }}
                    </div>
                    <div class="text-[11px] text-muted-foreground">
                        {{ formatDate(row.created_at) }}
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <div class="font-medium">{{ row.title }}</div>
                    <div class="text-xs text-muted-foreground">
                        {{ row.original_filename }} · {{ row.linked_label }}
                    </div>
                </td>
                <td class="px-4 py-3.5 text-sm">{{ row.category_name }}</td>
                <td class="px-4 py-3.5 text-right text-xs tabular-nums">
                    {{ formatBytes(row.file_size) }}
                </td>
                <td class="px-4 py-3.5 text-right text-xs tabular-nums">
                    {{ row.downloads_count }}
                </td>
                <td class="px-4 py-3.5 text-right">
                    <div class="flex justify-end gap-1.5">
                        <Button
                            as-child
                            variant="outline"
                            size="sm"
                            class="min-h-9"
                        >
                            <a :href="download.url(row.id)">
                                <Download class="mr-1.5 size-3.5" />
                                Download
                            </a>
                        </Button>
                        <Button
                            v-if="canManage"
                            variant="ghost"
                            size="icon"
                            aria-label="Archive document"
                            @click="deleteTarget = row"
                        >
                            <Trash2 class="size-4 text-destructive" />
                        </Button>
                    </div>
                </td>
            </template>
        </DataTableCard>

        <!-- Upload Modal -->
        <Dialog v-model:open="uploadOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Upload Private Document</DialogTitle>
                    <DialogDescription>
                        Stored privately off the web root (max 10 MB; PDF, JPG,
                        PNG, WEBP, DOC/DOCX).
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitUpload">
                    <div class="grid gap-1.5">
                        <Label for="doc_title">Document Title</Label>
                        <Input
                            id="doc_title"
                            v-model="uploadForm.title"
                            placeholder="e.g., 2021 Camry Customs Duty Certificate"
                            required
                        />
                        <InputError :message="uploadForm.errors.title" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="doc_cat">Category</Label>
                            <select
                                id="doc_cat"
                                v-model="uploadForm.document_category_id"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    :value="cat.id"
                                >
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="doc_vehicle">
                                Linked Vehicle (Optional)
                            </Label>
                            <select
                                id="doc_vehicle"
                                v-model="uploadForm.entity_id"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">None (General Vault)</option>
                                <option
                                    v-for="v in vehicles"
                                    :key="v.id"
                                    :value="v.id"
                                >
                                    {{ v.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="doc_file">File</Label>
                        <input
                            id="doc_file"
                            type="file"
                            required
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            @change="onFileChange"
                        />
                        <InputError :message="uploadForm.errors.file" />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="uploadOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="uploadForm.processing">
                            <Spinner
                                v-if="uploadForm.processing"
                                class="mr-2 size-4"
                            />
                            Upload to Vault
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            :open="deleteTarget !== null"
            title="Archive Document?"
            :description="`Remove ${deleteTarget?.title ?? ''} (${deleteTarget?.reference_number ?? ''}) from the active document vault? Audit history is preserved.`"
            confirm-label="Archive Document"
            variant="destructive"
            @update:open="
                (val) => {
                    if (!val) deleteTarget = null;
                }
            "
            @confirm="confirmArchive"
        />
    </div>
</template>
