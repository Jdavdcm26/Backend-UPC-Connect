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
     * Cambia la contraseña con el enlace temporal. El token se borra al
     * usarse, y las demás sesiones abiertas se cierran en su siguiente
     * solicitud porque guardan el hash de la contraseña anterior.
     */
    public function __invoke(RestablecerContrasenaRequest $request): JsonResponse
    {
        $estado = Password::reset(
            $request->safe()->only(['email', 'password', 'password_confirmation', 'token']),
            function (User $usuario, string $password): void {
                $usuario->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($usuario));
            },
        );

        if ($estado !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => trans('passwords.token')]);
        }

        return response()->json(['message' => trans($estado)]);
    }
}
