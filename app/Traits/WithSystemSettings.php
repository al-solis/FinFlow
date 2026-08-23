<?php

namespace App\Traits;

use App\Services\SystemSettings;

trait WithSystemSettings
{
    protected function getSystemSettings(): array
    {
        $settings = SystemSettings::get();

        // Get currency symbol from the currency model if available
        $currencySymbol = '$';
        if ($settings && $settings->currency) {
            $currencySymbol = $settings->currency->symbol ?? $settings->currency->code ?? '$';
        }

        return [
            'currency' => config('app.currency', $settings->currency->code ?? 'USD'),
            'currency_id' => $settings->currency_id ?? null,
            'currency_symbol' => $currencySymbol,
            'decimal_places' => config('app.decimal_places', 2),
            'thousands_separator' => config('app.thousands_separator', ','),
            'decimal_separator' => config('app.decimal_separator', '.'),
            'timezone' => config('app.timezone', $settings->timezone ?? 'Asia/Manila'),
            'locale' => config('app.locale', $settings->locale ?? 'en'),
            // Additional settings from the organization
            'organization_id' => $settings?->id,
            'organization_name' => $settings?->name,
            'organization_email' => $settings?->email,
            'organization_phone' => $settings?->phone,
            'organization_address' => $settings?->address,
            'date_format' => config('app.date_format', 'Y-m-d'),
            'time_format' => config('app.time_format', 'H:i'),
        ];
    }

    protected function withSystemSettings(array $data = []): array
    {
        return array_merge($data, [
            'systemSettings' => $this->getSystemSettings(),
        ]);
    }
}