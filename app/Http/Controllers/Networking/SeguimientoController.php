<?php

namespace App\Http\Controllers\Networking;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Seguir y dejar de seguir a otra persona (HU-28). Repetir cualquiera de las
 * dos acciones no es un error: el resultado es el mismo.
 */
class SeguimientoController extends Controller
{
    public function store(Request $request, User $usuario): JsonResponse
    {
        Gate::authorize('seguir', $usuario);

        $request->user()->seguidos()->syncWithoutDetaching([$usuario->id]);

        return $this->respuesta($usuario, siguiendo: true);
    }

    public function destroy(Request $request, User $usuario): JsonResponse
    {
        $request->user()->seguidos()->detach($usuario->id);

        return $this->respuesta($usuario, siguiendo: false);
    }

    /**
     * El contador de seguidores actualizado de la persona seguida.
     */
    private function respuesta(User $usuario, bool $siguiendo): JsonResponse
    {
        return response()->json(['data' => [
            'siguiendo' => $siguiendo,
            'seguidores' => $usuario->seguidores()->count(),
        ]]);
    }
}
