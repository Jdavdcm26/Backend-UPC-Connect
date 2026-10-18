<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->when(MiddlewareAuthSecurityStrategy::class)
            ->needs(SecurityScheme::class)
            ->give(fn (): SecurityScheme => SecurityScheme::apiKey('cookie', config('session.cookie')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewApiDocs', fn (?User $user): bool => ! $this->app->isProduction());
    }
}
