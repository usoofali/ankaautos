<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardMetricsService $metrics,
    ) {}

    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $canViewFinancials = $user->can('dashboard.view_financials');

        return Inertia::render('Dashboard', [
            'canViewFinancials' => $canViewFinancials,
            'operational' => Inertia::defer(fn (): array => $this->metrics->operational()),
            'financial' => $canViewFinancials
                ? Inertia::defer(fn (): array => $this->metrics->financial())
                : null,
        ]);
    }
}
