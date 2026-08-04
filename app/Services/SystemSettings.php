<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Cache;

class SystemSettings
{
    public static function get(): ?Organization
    {
        return Cache::rememberForever('organization_settings', function () {
            return Organization::first();
        });
    }

    public static function clear(): void
    {
        Cache::forget('organization_settings');
    }
}