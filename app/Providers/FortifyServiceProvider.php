<?php

namespace App\Providers;

use App\Enums\Autenticacion\EstadoUsuario;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurarInicioDeSesion();

        RateLimiter::for('registro', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('recuperacion', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('reenvio-verificacion', fn (Request $request): array => [
            Limit::perMinute(3)->by(is_string($email = $request->input('email')) ? Str::lower(trim($email)) : ''),
            Limit::perMinute(10)->by($request->ip()),
        ]);
    }

    /**
     * Inicio de sesión (HU-03, RF-04):
     *
     * - Tras cinco intentos fallidos seguidos del mismo correo y la misma IP,
     *   Fortify bloquea temporalmente e informa los segundos de espera (429).
     * - La ruta además limita a 20 intentos por minuto por cuenta, venga de
     *   la IP que venga, para frenar ataques repartidos.
     * - Solo entran las cuentas ACTIVO; las pendientes y suspendidas reciben
     *   un aviso claro. Ese aviso solo aparece con la contraseña correcta, así
     *   que no revela qué cuentas existen.
     */
    private function configurarInicioDeSesion(): void
    {
        Fortify::authenticateThrough(fn (): array => [
            EnsureLoginIsNotThrottled::class,
            CanonicalizeUsername::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]);

        Fortify::authenticateUsing(function (Request $request): ?User {
            $usuario = User::query()->where('email', $request->input(Fortify::username()))->first();

            if ($usuario === null || ! Hash::check($request->input('password'), $usuario->password)) {
                return null;
            }

            $aviso = match ($usuario->estado) {
                EstadoUsuario::Pendiente => 'Tu cuenta aún no está activa. Abre el enlace que te enviamos a tu correo institucional para activarla.',
                EstadoUsuario::Suspendido => 'Tu cuenta está suspendida. Si crees que es un error, comunícate con la administración de la plataforma.',
                EstadoUsuario::Activo => null,
            };

            if ($aviso !== null) {
                throw ValidationException::withMessages([Fortify::username() => $aviso]);
            }

            return $usuario;
        });

        RateLimiter::for('login', function (Request $request): Limit {
            $correo = $request->input(Fortify::username());

            return Limit::perMinute(20)
                ->by('cuenta:'.(is_string($correo) ? Str::lower(trim($correo)) : ''))
                ->response(fn (Request $request, array $headers) => response()->json([
                    'message' => trans('auth.throttle', ['seconds' => $headers['Retry-After']]),
                ], 429, $headers));
        });
    }
}
