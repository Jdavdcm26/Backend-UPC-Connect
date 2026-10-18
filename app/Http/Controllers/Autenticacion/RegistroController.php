<?php

namespace App\Http\Controllers\Autenticacion;

use App\Actions\Autenticacion\RegistrarUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\RegistrarUsuarioRequest;
use Illuminate\Http\JsonResponse;

class RegistroController extends Controller
{
    /**
     * Registra a un estudiante con su correo institucional. La respuesta es
     * la misma exista o no la cuenta, y no inicia sesión: la cuenta queda
     * PENDIENTE hasta que verifique el correo.
     */
    public function __invoke(RegistrarUsuarioRequest $request, RegistrarUsuario $registrarUsuario): JsonResponse
    {
        $registrarUsuario->handle(
            $request->validated('email'),
            $request->validated('password'),
            $request->universidad(),
            $request->validated('version_politica'),
        );

        return response()->json([
            'message' => 'Revisa tu correo institucional: te enviamos un enlace para activar tu cuenta.',
        ], 202);
    }
}
