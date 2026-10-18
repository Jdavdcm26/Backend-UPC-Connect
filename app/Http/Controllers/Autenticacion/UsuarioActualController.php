<?php

namespace App\Http\Controllers\Autenticacion;

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
        return new UsuarioActualResource($request->user()->loadMissing('universidad'));
    }
}
