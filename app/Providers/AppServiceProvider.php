<?php

namespace App\Providers;

use App\Contracts\ExternalAuthenticator;
use App\Services\Fake\FakeExternalAuthService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Swap for the real company-system integration when it lands —
        // nothing else in the app should know which one is bound.
        $this->app->bind(ExternalAuthenticator::class, FakeExternalAuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
