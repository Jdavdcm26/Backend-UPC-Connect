<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\RestablecerContrasenaRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RestablecimientoContrasenaController extends Controller
{
    /**
     * Cambia la contraseña con el enlace temporal de recuperación (60 minutos)
     * o de invitación (7 días, para cuentas creadas por el administrador). Al
     * usarse se borran los tokens de ambos tipos, y las demás sesiones
     * abiertas se cierran en su siguiente solicitud porque guardan el hash de
     * la contraseña anterior.
     */
    public function __invoke(RestablecerContrasenaRequest $request): JsonResponse
    {
        $credenciales = $request->safe()->only(['email', 'password', 'password_confirmation', 'token']);

        $definirContrasena = function (User $usuario, string $password): void {
            $usuario->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            Password::broker('users')->deleteToken($usuario);
            Password::broker('invitaciones')->deleteToken($usuario);

            event(new PasswordReset($usuario));
        };

        $estado = Password::broker('users')->reset($credenciales, $definirContrasena);

        if ($estado === Password::INVALID_TOKEN) {
            $estado = Password::broker('invitaciones')->reset($credenciales, $definirContrasena);
        }

        if ($estado !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => trans('passwords.token')]);
        }

        return response()->json(['message' => trans($estado)]);
    }
}
