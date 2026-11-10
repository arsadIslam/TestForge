<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
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
    }
}
