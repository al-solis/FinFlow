<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SystemSettings;

class LoadSystemSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = SystemSettings::get();

        if ($settings) {

            if (!empty($settings->timezone)) {
                config([
                    'app.timezone' => $settings->timezone,
                ]);

                date_default_timezone_set($settings->timezone);
            }

            if (!empty($settings->language)) {
                config([
                    'app.locale' => $settings->language,
                ]);
            }

            if (!empty($settings->currency)) {
                config([
                    'app.currency' => $settings->currency,
                ]);
            }
        }

        return $next($request);
    }
}