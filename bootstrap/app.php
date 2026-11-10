<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        // Rate-limit every /api route with the 'api' limiter (defined in AppServiceProvider).
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API clients always get JSON errors (401, 403, 404, 422, ...), even if they forget
        // the "Accept: application/json" header — e.g. a missing token is a 401 JSON
        // response, not a redirect to the login page.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        // Route-model-binding misses would otherwise say "No query results for model
        // [App\Models\Test] 999" — even in production. Don't leak class names to API clients.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Not found.'], 404);
            }
        });
    })->create();
