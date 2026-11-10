<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\LeaderboardController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\TestController;
use Illuminate\Support\Facades\Route;

/*
| REST API — everything here is under /api (added by Laravel), then /v1.
| Auth: Sanctum personal access tokens ("Authorization: Bearer <token>").
| Errors are always JSON (see bootstrap/app.php): 401 no/invalid token, 403 wrong role
| or deactivated, 404 not found, 422 validation / business rule.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public — with a tighter rate limit against password guessing.
    Route::middleware('throttle:6,1')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        // Any logged-in role may read a leaderboard.
        Route::get('tests/{test}/leaderboard', [LeaderboardController::class, 'show'])->name('tests.leaderboard');

        // Student-only — the same EnsureRole middleware as the web app.
        Route::middleware('role:student')->group(function () {
            Route::get('tests', [TestController::class, 'index'])->name('tests.index');
            Route::get('me/results', [MeController::class, 'results'])->name('me.results');
            Route::get('me/analytics', [MeController::class, 'analytics'])->name('me.analytics');
        });
    });
});
