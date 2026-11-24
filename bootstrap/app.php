<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LoadSystemSettings;
use App\Http\Middleware\CheckAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            LoadSystemSettings::class,
        ]);

        $middleware->alias([
            'access' => CheckAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Invalid signed URL — legitimately a 403
        $exceptions->render(function (InvalidSignatureException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This link has expired or is invalid. Please request a new link.',
                ], 403);
            }
            return response()->view('errors.signed-expired', [], 403);
        });

        // Not found — MUST be 404, not 403
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Record not found.'], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        // Abort(403) from CheckAccess / AuthorizationService
        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'You are not allowed to access this.',
                ], 403);
            }
            return response()->view('errors.403', [
                'message' => $e->getMessage(),
            ], 403);
        });

        // Gate/Policy denials — make their message visible
        $exceptions->render(function (AuthorizationException $e, $request) {
            $message = $e->getMessage() ?: 'You are not allowed to access this.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }
            return response()->view('errors.403', ['message' => $message], 403);
        });
    })->create();