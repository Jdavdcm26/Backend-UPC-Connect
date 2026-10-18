<?php

namespace App\Http\Middleware;

use App\Enums\Autenticacion\EstadoUsuario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de una cuenta que dejó de estar activa (por ejemplo, la
 * suspendieron mientras tenía la sesión abierta) en su siguiente solicitud.
 */
class AsegurarCuentaActiva
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario === null || $usuario->estado === EstadoUsuario::Activo) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'codigo' => $usuario->estado === EstadoUsuario::Suspendido ? 'cuenta_suspendida' : 'cuenta_no_activa',
            'message' => $usuario->estado === EstadoUsuario::Suspendido
                ? 'Tu cuenta está suspendida. Si crees que es un error, comunícate con la administración de la plataforma.'
                : 'Tu cuenta aún no está activa. Abre el enlace que te enviamos a tu correo institucional para activarla.',
        ], 401);
    }
}
