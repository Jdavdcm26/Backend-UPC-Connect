<?php

namespace App\Http\Controllers\Autenticacion;

use App\Enums\Autenticacion\Rol;
use App\Http\Controllers\Controller;
use App\Http\Resources\Autenticacion\UsuarioActualResource;
use Illuminate\Http\Request;

class UsuarioActualController extends Controller
{
    /**
     * Devuelve el usuario de la sesión actual.
     */
    public function __invoke(Request $request): UsuarioActualResource
    {
        $usuario = $request->user()->loadMissing('universidad')->loadExists('perfil');

        if ($usuario->rol === Rol::Empresa) {
            $usuario->loadMissing('empresa');
        }

        return new UsuarioActualResource($usuario);
    }
}
