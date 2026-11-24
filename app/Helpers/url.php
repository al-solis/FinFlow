<?php

use Illuminate\Support\Facades\URL;

if (!function_exists('signed_url')) {
    /**
     * Generate a short-lived signed URL.
     *
     * @param string $routeName  e.g. 'ap.rfd.edit'
     * @param array  $params     e.g. ['rfd' => $rfd->id]
     * @param int    $minutes    validity in minutes (default 15)
     */
    function signed_url(string $routeName, array $params = [], int $minutes = 15): string
    {
        return URL::temporarySignedRoute(
            $routeName,
            now()->addMinutes($minutes),
            $params
        );
    }
}