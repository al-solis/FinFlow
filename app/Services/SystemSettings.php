<?php

namespace App\Services;

use App\Models\organization;
use Illuminate\Support\Facades\Cache;

class SystemSettings
{
    public static function get(): ?organization
    {
        return Cache::rememberForever('organization_settings', function () {
            return organization::where('status', 1)->first();
        });
    }

    public static function clear(): void
    {
        Cache::forget('organization_settings');
    }
}