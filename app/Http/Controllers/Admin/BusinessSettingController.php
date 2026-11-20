<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBusinessSettingsRequest;
use App\Models\BusinessSetting;
use App\Services\AuditLogger;
use App\Services\BusinessSettingService;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BusinessSettingController extends Controller
{
    public function __construct(
        private BusinessSettingService $settings,
        private AuditLogger $auditLogger,
    ) {}

    public function edit(): Response
    {
        return Inertia::render('admin/settings/Business', [
            'settings' => $this->settings->all(),
            'timezones' => [
                'Africa/Lagos',
                'Africa/Accra',
                'Africa/Johannesburg',
                'Africa/Nairobi',
                'Europe/London',
                'UTC',
                ...array_values(array_diff(DateTimeZone::listIdentifiers(), [
                    'Africa/Lagos',
                    'Africa/Accra',
                    'Africa/Johannesburg',
                    'Africa/Nairobi',
                    'Europe/London',
                    'UTC',
                ])),
            ],
        ]);
    }

    public function update(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['currency_code'] = strtoupper($validated['currency_code']);
        $validated['reference_prefix'] = strtoupper($validated['reference_prefix']);

        $before = $this->settings->all();
        $this->settings->updateMany($validated);
        $after = $this->settings->all();

        $anchor = BusinessSetting::query()->first();
        if ($anchor !== null) {
            $this->auditLogger->audit(
                event: 'business_settings.updated',
                auditable: $anchor,
                oldValues: $before,
                newValues: $after,
            );
        }

        $this->auditLogger->activity(
            action: 'business_settings.updated',
            description: 'Updated dealership business settings and localization preferences.',
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Business settings saved successfully.',
        ]);

        return back();
    }
}
