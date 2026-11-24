<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccess
{
    /**
     * Route middleware key => sub_modules.route_name that owns the grant.
     *
     * Category & sub-category intentionally inherit the Chart of Accounts grant,
     * so all three route keys resolve to the same sub_modules row ("gl.chart").
     */
    private const ALIASES = [
        // GL — category & sub-category share the Chart of Accounts grant
        'gl.chart.category.index' => 'gl.chart',
        'gl.chart.subcategory.index' => 'gl.chart',
    ];

    public function handle(Request $request, Closure $next, string $subModule, ?string $action = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $action ??= match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'read',
        };

        // Resolve the route key to the sub_modules.route_name that owns the grant.
        $grantKey = self::ALIASES[$subModule] ?? $subModule;
        // dd($grantKey, $action);

        if (!$user->canAccess($grantKey, $action)) {
            abort(403, "You are not authorized to access this page ({$grantKey} / {$action}).");
        }

        return $next($request);
    }
}