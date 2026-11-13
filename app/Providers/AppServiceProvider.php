<?php

namespace App\Providers;

use App\Models\Plan;
use App\Models\TestSeries;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Throw an exception on any lazy-loaded relation in local/testing, so N+1 queries
        // are caught during development. In production it stays off and never breaks a page.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Polymorphic columns (payments.payable_type, Sanctum's tokenable_type) store these
        // short names instead of PHP class names: renaming a class can never orphan old rows,
        // and an unknown type throws instead of silently loading the wrong model.
        Relation::enforceMorphMap([
            'user' => User::class,
            'test_series' => TestSeries::class,
            'plan' => Plan::class,
        ]);

        // 60 API requests a minute per user (or per IP before logging in).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }
}
