<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BusinessSettingService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reports,
        private BusinessSettingService $businessSettings,
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $canOperational = $user->can('reports.view_operational');
        $canFinancial = $user->can('reports.view_financial');

        $catalog = array_values(array_filter(
            $this->reports->catalog(),
            static fn (array $item): bool => ($item['category'] === 'operational' && $canOperational)
                || ($item['category'] === 'financial' && $canFinancial),
        ));

        return Inertia::render('reports/Index', [
            'catalog' => $catalog,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        /** @var User $user */
        $user = $request->user();
        $from = $request->filled('from') ? (string) $request->string('from') : null;
        $to = $request->filled('to') ? (string) $request->string('to') : null;

        $report = $this->reports->generate($slug, $from, $to);
        $this->authorizeReport($user, $report['category']);

        return Inertia::render('reports/Show', [
            'report' => $report,
            'filters' => [
                'from' => $from ?? '',
                'to' => $to ?? '',
            ],
        ]);
    }

    public function exportCsv(Request $request, string $slug): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $from = $request->filled('from') ? (string) $request->string('from') : null;
        $to = $request->filled('to') ? (string) $request->string('to') : null;

        $report = $this->reports->generate($slug, $from, $to);
        $this->authorizeReport($user, $report['category']);

        $filename = "Anka-{$slug}-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(static function () use ($report): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $report['headers']);
            foreach ($report['rows'] as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request, string $slug): HttpResponse
    {
        /** @var User $user */
        $user = $request->user();
        $from = $request->filled('from') ? (string) $request->string('from') : null;
        $to = $request->filled('to') ? (string) $request->string('to') : null;

        $report = $this->reports->generate($slug, $from, $to);
        $this->authorizeReport($user, $report['category']);

        $pdf = Pdf::loadView('pdfs.report', [
            'report' => $report,
            'from' => $from,
            'to' => $to,
            'business' => $this->businessSettings->all(),
            'generatedBy' => $user->name,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("Anka-{$slug}-".now()->format('Ymd').'.pdf');
    }

    private function authorizeReport(User $user, string $category): void
    {
        if ($category === 'financial') {
            abort_unless($user->can('reports.view_financial'), 403);

            return;
        }

        abort_unless($user->can('reports.view_operational'), 403);
    }
}
