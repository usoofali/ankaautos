<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class BusinessSettingService
{
    private const CACHE_KEY = 'anka.business_settings';

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        if (! Schema::hasTable('business_settings')) {
            return $this->defaults();
        }

        /** @var array<string, string> $stored */
        $stored = Cache::rememberForever(self::CACHE_KEY, function (): array {
            /** @var array<string, string|null> $values */
            $values = BusinessSetting::query()->pluck('value', 'key')->all();

            return array_map(static fn (?string $value): string => (string) ($value ?? ''), $values);
        });

        return array_merge($this->defaults(), $stored);
    }

    public function get(string $key, string $default = ''): string
    {
        $settings = $this->all();

        return $settings[$key] ?? $default;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function updateMany(array $values): void
    {
        foreach ($values as $key => $value) {
            BusinessSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? ''],
            );
        }

        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Public-safe subset shared across Inertia pages and public listings.
     *
     * @return array{company_name: string, phone: string, whatsapp_number: string, email: string, address: string, currency_code: string, currency_symbol: string, timezone: string}
     */
    public function publicProfile(): array
    {
        $all = $this->all();

        return [
            'company_name' => $all['company_name'],
            'phone' => $all['phone'],
            'whatsapp_number' => $all['whatsapp_number'],
            'email' => $all['email'],
            'address' => $all['address'],
            'currency_code' => $all['currency_code'],
            'currency_symbol' => $all['currency_symbol'],
            'timezone' => $all['timezone'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function defaults(): array
    {
        return [
            'company_name' => 'Anka Auto Sales',
            'tagline' => 'Trusted Dealership & Consignment Management',
            'address' => 'Plot 14, Aminu Kano Crescent, Wuse II, Abuja, Nigeria',
            'phone' => '+234 803 000 0000',
            'whatsapp_number' => '2348030000000',
            'email' => 'info@ankaautos.com',
            'currency_code' => 'NGN',
            'currency_symbol' => '₦',
            'timezone' => 'Africa/Lagos',
            'reference_prefix' => 'ANK',
            'receipt_footer' => 'Thank you for doing business with Anka Auto Sales. All official receipts are system-generated.',
        ];
    }
}
