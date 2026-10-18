<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\ReenviarVerificacionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ReenvioVerificacionController extends Controller
{
    /**
     * Reenvía el enlace de verificación a una cuenta pendiente. La respuesta
     * es la misma exista o no la cuenta, para no revelar qué correos están
     * registrados.
     */
    public function __invoke(ReenviarVerificacionRequest $request): JsonResponse
    {
        $usuario = User::query()->where('email', $request->validated('email'))->first();

        if ($usuario !== null && ! $usuario->hasVerifiedEmail()) {
            $usuario->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'Si tu cuenta está pendiente de activación, te enviamos un nuevo enlace a tu correo.',
        ], 202);
    }
}
