<?php

namespace App\Providers;

use App\Enums\Autenticacion\Rol;
use App\Models\User;
use App\Models\Vacantes\Vacante;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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

        Gate::define('es-admin', fn (User $user): bool => $user->rol === Rol::Admin);
        Gate::define('es-empresa', fn (User $user): bool => $user->rol === Rol::Empresa);
        Gate::define('es-candidato', fn (User $user): bool => $user->rol->esCandidato());

        Gate::define('viewApiDocs', fn (?User $user): bool => ! $this->app->isProduction());

        $this->configureDevCommands();
        $this->configurarBindings();
    }

    /**
     * {vacantePropia} solo resuelve vacantes de la empresa con sesión. Una de
     * otra empresa responde 404, igual que una inexistente, para no revelar
     * que existe.
     */
    private function configurarBindings(): void
    {
        Route::bind('vacantePropia', function (string $id): Vacante {
            abort_unless(Str::isUuid($id), 404);

            return request()->user()?->empresa?->vacantes()->findOrFail($id) ?? abort(404);
        });
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
