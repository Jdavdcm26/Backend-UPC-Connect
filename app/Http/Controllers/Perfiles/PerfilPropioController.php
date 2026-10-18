<?php

namespace App\Http\Controllers\Perfiles;

use App\Actions\Perfiles\GuardarPerfil;
use App\Http\Controllers\Controller;
use App\Http\Requests\Perfiles\GuardarPerfilRequest;
use App\Http\Resources\Perfiles\PerfilResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PerfilPropioController extends Controller
{
    /**
     * Perfil de la persona con sesión. Si aún no lo ha creado responde 404
     * con un código para que la SPA la lleve a crearlo (HU-09).
     */
    public function show(Request $request): JsonResponse|PerfilResource
    {
        $perfil = $request->user()->perfil()->first();

        if ($perfil === null) {
            return response()->json([
                'codigo' => 'perfil_no_creado',
                'message' => 'Aún no has creado tu perfil. Complétalo para continuar.',
            ], 404);
        }

        return new PerfilResource($perfil->load(['carrera', 'habilidades']));
    }

    /**
     * Crea el perfil (201) o lo actualiza (200) con un solo PUT.
     */
    public function update(GuardarPerfilRequest $request, GuardarPerfil $guardarPerfil): JsonResponse
    {
        $usuario = $request->user();
        $perfilActual = $usuario->perfil()->first();

        if ($perfilActual !== null) {
            Gate::authorize('update', $perfilActual);
        }

        $perfil = $guardarPerfil->handle($usuario, $request->validated());

        return (new PerfilResource($perfil->load(['carrera', 'habilidades'])))
            ->response()
            ->setStatusCode($perfil->wasRecentlyCreated ? 201 : 200);
    }
}
