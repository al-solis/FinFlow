<?php

use App\Services\PiiMaskingService;
use Illuminate\Support\Facades\Auth;

if (!function_exists('maskPii')) {
    /**
     * Mask PII data based on user access rights for the current module context
     */
    function maskPii($value, string $piiType): string
    {
        return app('pii.masker')->mask($value, $piiType);
    }
}

if (!function_exists('hasFullPiiAccess')) {
    /**
     * Check if current user has full PII access for the current module
     */
    function hasFullPiiAccess(): bool
    {
        return app('pii.masker')->hasFullAccess();
    }
}

if (!function_exists('hasPartialPiiAccess')) {
    /**
     * Check if current user has partial PII access for the current module
     */
    function hasPartialPiiAccess(): bool
    {
        return app('pii.masker')->hasPartialAccess();
    }
}

if (!function_exists('getPiiMaskLevel')) {
    /**
     * Get current PII mask level for the current module
     */
    function getPiiMaskLevel(): string
    {
        return app('pii.masker')->getMaskLevel();
    }
}

if (!function_exists('setPiiContext')) {
    /**
     * Set the current module context for PII masking
     */
    function setPiiContext(string $module, ?string $subModule = null): void
    {
        app('pii.masker')->setContext($module, $subModule);
    }
}

if (!function_exists('getPiiFields')) {
    /**
     * Get PII fields for a specific module/sub-module
     */
    function getPiiFields(string $module, ?string $subModule = null): array
    {
        return app('pii.masker')->getPiiFields($module, $subModule);
    }
}