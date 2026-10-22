<?php

namespace App\Http\Controllers\Perfiles;

use App\Http\Controllers\Controller;
use App\Http\Resources\Perfiles\PerfilVisibleResource;
use App\Models\Networking\Bloqueo;
use App\Models\Networking\Seguidor;
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

        return (new PerfilVisibleResource($perfil, $secciones))->additional([
            'red' => $this->red($perfil->usuario_id, $request->user()->id),
        ]);
    }

    /**
     * Datos de seguimiento y bloqueo para los botones del perfil (HU-28,
     * HU-31): el id de la cuenta, sus contadores y la relación con quien mira.
     *
     * @return array{usuario_id: string, seguidores: int, seguidos: int, lo_sigues: bool, lo_bloqueaste: bool}
     */
    private function red(string $duenoId, string $visitanteId): array
    {
        return [
            'usuario_id' => $duenoId,
            'seguidores' => Seguidor::query()->where('seguido_id', $duenoId)->count(),
            'seguidos' => Seguidor::query()->where('seguidor_id', $duenoId)->count(),
            'lo_sigues' => Seguidor::query()->where('seguidor_id', $visitanteId)->where('seguido_id', $duenoId)->exists(),
            'lo_bloqueaste' => Bloqueo::query()->where('bloqueador_id', $visitanteId)->where('bloqueado_id', $duenoId)->exists(),
        ];
    }
}
