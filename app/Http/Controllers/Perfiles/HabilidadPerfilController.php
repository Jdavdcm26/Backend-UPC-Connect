<?php

namespace App\Http\Controllers\Perfiles;

use App\Actions\Perfiles\AgregarHabilidad;
use App\Enums\Perfiles\NivelHabilidad;
use App\Http\Controllers\Controller;
use App\Http\Requests\Perfiles\AgregarHabilidadRequest;
use App\Http\Resources\Perfiles\PerfilResource;
use App\Models\Perfiles\Perfil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Habilidades del perfil propio (HU-11). Ambas acciones devuelven el perfil
 * actualizado.
 */
class HabilidadPerfilController extends Controller
{
    public function store(AgregarHabilidadRequest $request, AgregarHabilidad $agregarHabilidad): JsonResponse|PerfilResource
    {
        $perfil = $request->user()->perfil()->first();

        if ($perfil === null) {
            return $this->perfilNoCreado();
        }

        $nivel = $request->validated('nivel');

        $agregarHabilidad->handle($perfil, $request->validated('habilidad_id'), $nivel === null ? null : NivelHabilidad::from($nivel));

        return $this->perfilActualizado($perfil);
    }

    /**
     * Quitar una habilidad que no está en el perfil no es un error: el
     * resultado es el mismo.
     */
    public function destroy(Request $request, string $habilidad): JsonResponse|PerfilResource
    {
        $perfil = $request->user()->perfil()->first();

        if ($perfil === null) {
            return $this->perfilNoCreado();
        }

        $perfil->habilidades()->detach($habilidad);

        return $this->perfilActualizado($perfil);
    }

    private function perfilActualizado(Perfil $perfil): PerfilResource
    {
        return new PerfilResource($perfil->load(['carrera', 'habilidades']));
    }

    private function perfilNoCreado(): JsonResponse
    {
        return response()->json([
            'codigo' => 'perfil_no_creado',
            'message' => 'Aún no has creado tu perfil. Complétalo para continuar.',
        ], 404);
    }
}
