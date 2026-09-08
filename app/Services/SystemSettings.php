<?php

namespace App\Services;

use App\Models\organization;
use Illuminate\Support\Facades\Cache;

class SystemSettings
{
    public static function get(): ?organization
    {
        $organizationId = Cache::rememberForever(
            'organization_settings_id',
            function () {
                return organization::where('status', 1)->value('id');
            }
        );

        if (!$organizationId) {
            return null;
        }

        return organization::with('currency')->find($organizationId);
    }

    public static function clear(): void
    {
        Cache::forget('organization_settings_id');
    }

    public static function getSetting(string $key, $default = null)
    {
        $settings = self::get();

        return $settings->{$key} ?? $default;
    }

    public static function getCurrencySymbol(): string
    {
        $settings = self::get();

        if ($settings && $settings->currency) {
            return $settings->currency->symbol
                ?? $settings->currency->code
                ?? '₱';
        }

        return config('app.currency_symbol', '₱');
    }

    public static function getCurrencyCode(): string
    {
        $settings = self::get();

        if ($settings && $settings->currency) {
            return $settings->currency->code ?? 'PHP';
        }

        return config('app.currency', 'PHP');
    }

    public static function getDecimalPlaces(): int
    {
        return config('app.decimal_places', 2);
    }

    public static function getTimezone(): string
    {
        $settings = self::get();

        return $settings->timezone ?? config('app.timezone', 'Asia/Manila');
    }

    public static function getLocale(): string
    {
        $settings = self::get();

        return $settings->locale ?? config('app.locale', 'en');
    }

    public static function getOrganizationId(): ?int
    {
        $settings = self::get();

        return $settings?->id;
    }

    public static function hasSettings(): bool
    {
        return self::get() !== null;
    }

    public static function formatCurrency($amount): string
    {
        if ($amount === null || $amount === '' || !is_numeric($amount)) {
            $amount = 0;
        }

        return self::getCurrencySymbol() . ' ' . number_format(
            (float) $amount,
            self::getDecimalPlaces(),
            '.',
            ','
        );
    }
}