<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\SolicitarRestablecimientoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class SolicitudRestablecimientoController extends Controller
{
    /**
     * Envía el enlace para restablecer la contraseña. La respuesta es la misma
     * si la cuenta no existe o si ya se pidió un enlace hace poco, para no
     * revelar qué correos están registrados.
     */
    public function __invoke(SolicitarRestablecimientoRequest $request): JsonResponse
    {
        Password::sendResetLink(['email' => $request->validated('email')]);

        return response()->json(['message' => trans('passwords.sent')]);
    }
}
