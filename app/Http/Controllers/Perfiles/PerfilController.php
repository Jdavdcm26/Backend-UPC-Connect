<?php

namespace App\Http\Controllers\Perfiles;

use App\Http\Controllers\Controller;
use App\Http\Resources\Perfiles\PerfilVisibleResource;
use App\Models\Perfiles\Perfil;
use App\Models\Scopes\UniversidadScope;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    /**
     * Perfil de otra persona con las secciones que el visitante puede ver
     * (HU-27). Un perfil que no puede ver responde 404, igual que uno que no
     * existe, para no revelar que existe.
     */
    public function show(Request $request, string $slug): PerfilVisibleResource
    {
        $perfil = Perfil::query()
            ->where('slug', $slug)
            ->with([
                'carrera' => fn ($carreras) => $carreras->withoutGlobalScope(UniversidadScope::class),
                'habilidades',
            ])
            ->firstOrFail();

        $secciones = $perfil->seccionesVisiblesPara($request->user());

        abort_if($secciones === [], 404);

        return new PerfilVisibleResource($perfil, $secciones);
    }
}
