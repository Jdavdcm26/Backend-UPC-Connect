<?php

namespace App\Http\Controllers\Autenticacion;

use App\Actions\Autenticacion\ActivarCuenta;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class VerificacionCorreoController extends Controller
{
    /**
     * Activa la cuenta desde el enlace firmado del correo, sin exigir sesión.
     * Cada respuesta de error lleva un código para que la SPA explique el
     * motivo y ofrezca reenviar el enlace.
     */
    public function __invoke(Request $request, string $id, string $hash, ActivarCuenta $activarCuenta): JsonResponse
    {
        $usuario = URL::hasCorrectSignature($request, absolute: false)
            ? User::query()->find($id)
            : null;

        if ($usuario === null || ! hash_equals(hash('sha256', $usuario->getEmailForVerification()), $hash)) {
            return $this->error('enlace_invalido', 'El enlace de verificación no es válido. Solicita uno nuevo.', 403);
        }

        if ($usuario->hasVerifiedEmail()) {
            return $this->error('cuenta_ya_activa', 'Este enlace ya se usó: tu correo está verificado. Inicia sesión.', 409);
        }

        if (! URL::signatureHasNotExpired($request)) {
            return $this->error('enlace_vencido', 'El enlace de verificación venció. Solicita uno nuevo.', 410);
        }

        $activarCuenta->handle($usuario);

        return response()->json(['message' => 'Tu cuenta está activa. Ya puedes iniciar sesión.']);
    }

    private function error(string $codigo, string $mensaje, int $estado): JsonResponse
    {
        return response()->json(['codigo' => $codigo, 'message' => $mensaje], $estado);
    }
}
