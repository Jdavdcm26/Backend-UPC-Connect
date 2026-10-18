<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Password::defaults(fn (): Password => Password::min(10)->uncompromised());

        Gate::define('viewApiDocs', fn (?User $user): bool => ! $this->app->isProduction());

        $this->configureDevCommands();
    }

    /**
     * Procesos de `composer dev`: la cola atiende primero los correos y el
     * programador corre las tareas como en producción. La API no sirve
     * assets propios, así que Vite no se levanta.
     */
    private function configureDevCommands(): void
    {
        DevCommands::artisan('queue:listen --queue=mail,default --tries=1 --timeout=0', 'queue');
        DevCommands::artisan('schedule:work', 'scheduler');
        DevCommands::except('vite');
    }
}
