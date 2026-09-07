<?php

use App\Services\AuthorizationService;
use Illuminate\Support\Facades\Auth;

if (!function_exists('can')) {
    function can(string $moduleCode, string $permission, ?string $subModuleCode = null): bool
    {
        return app(AuthorizationService::class)
            ->hasPermission($moduleCode, $permission, $subModuleCode);
    }
}

if (!function_exists('canCreate')) {
    function canCreate(string $moduleCode, ?string $subModuleCode = null): bool
    {
        return app(AuthorizationService::class)->canCreate($moduleCode, $subModuleCode);
    }
}

if (!function_exists('canRead')) {
    function canRead(string $moduleCode, ?string $subModuleCode = null): bool
    {
        return app(AuthorizationService::class)->canRead($moduleCode, $subModuleCode);
    }
}

if (!function_exists('canUpdate')) {
    function canUpdate(string $moduleCode, ?string $subModuleCode = null): bool
    {
        return app(AuthorizationService::class)->canUpdate($moduleCode, $subModuleCode);
    }
}

if (!function_exists('canDelete')) {
    function canDelete(string $moduleCode, ?string $subModuleCode = null): bool
    {
        return app(AuthorizationService::class)->canDelete($moduleCode, $subModuleCode);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin(): bool
    {
        return app(AuthorizationService::class)->isAdmin();
    }
}