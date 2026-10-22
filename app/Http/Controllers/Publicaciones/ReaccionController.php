<?php

namespace App\Http\Controllers\Publicaciones;

use App\Http\Controllers\Controller;
use App\Models\Publicaciones\Publicacion;
use App\Models\Publicaciones\Reaccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Reacción del usuario a una publicación (HU-25). Reaccionar dos veces o
 * quitar una reacción que no existe no es un error: el resultado es el mismo.
 */
class ReaccionController extends Controller
{
    public function store(Request $request, Publicacion $publicacion): JsonResponse
    {
        $this->autorizar($publicacion);

        Reaccion::query()->insertOrIgnore([
            'publicacion_id' => $publicacion->id,
            'usuario_id' => $request->user()->id,
        ]);

        return $this->respuesta($publicacion, reaccione: true);
    }

    public function destroy(Request $request, Publicacion $publicacion): JsonResponse
    {
        $this->autorizar($publicacion);

        $publicacion->reacciones()->where('usuario_id', $request->user()->id)->delete();

        return $this->respuesta($publicacion, reaccione: false);
    }

    private function autorizar(Publicacion $publicacion): void
    {
        Gate::authorize('view', $publicacion);
        Gate::authorize('reaccionar', $publicacion);
    }

    /**
     * El contador actualizado, para que la SPA no tenga que adivinarlo.
     */
    private function respuesta(Publicacion $publicacion, bool $reaccione): JsonResponse
    {
        return response()->json(['data' => [
            'reacciones' => $publicacion->reacciones()->count(),
            'reaccione' => $reaccione,
        ]]);
    }
}
