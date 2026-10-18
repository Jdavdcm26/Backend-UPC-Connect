<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request): Limit {
            $correo = $request->input(Fortify::username());
            $throttleKey = Str::transliterate((is_string($correo) ? Str::lower($correo) : '').'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('registro', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('reenvio-verificacion', fn (Request $request): array => [
            Limit::perMinute(3)->by(is_string($email = $request->input('email')) ? Str::lower(trim($email)) : ''),
            Limit::perMinute(10)->by($request->ip()),
        ]);
    }
}
