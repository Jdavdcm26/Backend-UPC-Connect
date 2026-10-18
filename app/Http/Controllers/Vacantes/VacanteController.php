<?php

namespace App\Http\Controllers\Vacantes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vacantes\BuscarVacantesRequest;
use App\Http\Resources\Vacantes\VacanteResource;
use App\Models\User;
use App\Models\Vacantes\Vacante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Vacantes abiertas para quien busca oportunidades (HU-43).
 */
class VacanteController extends Controller
{
    /**
     * Las más recientes primero, de a 15 por cursor. Los filtros se combinan,
     * y el texto y la ciudad se comparan sin tildes ni mayúsculas.
     */
    public function index(BuscarVacantesRequest $request): AnonymousResourceCollection
    {
        $filtros = $request->validated();

        $vacantes = Vacante::query()
            ->abiertas()
            ->with(['empresa', 'carreras'])
            ->tap(fn ($consulta) => $this->marcarPostulaciones($consulta, $request->user()))
            ->when($filtros['q'] ?? null, fn ($consulta, string $texto) => $consulta->whereRaw(
                "texto_busqueda like ? escape '\\'",
                ['%'.addcslashes(Vacante::normalizar($texto), '\\%_').'%'],
            ))
            ->when($filtros['tipo'] ?? null, fn ($consulta, string $tipo) => $consulta->where('tipo', $tipo))
            ->when($filtros['modalidad'] ?? null, fn ($consulta, string $modalidad) => $consulta->where('modalidad', $modalidad))
            ->when($filtros['ciudad'] ?? null, fn ($consulta, string $ciudad) => $consulta->where('ciudad_normalizada', Vacante::normalizar($ciudad)))
            ->when($filtros['carrera_id'] ?? null, fn ($consulta, string $carreraId) => $consulta->whereHas(
                'carreras',
                fn ($carreras) => $carreras->whereKey($carreraId),
            ))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(15)
            ->withQueryString();

        return VacanteResource::collection($vacantes);
    }

    /**
     * Detalle de una vacante abierta. Una pausada, cerrada o vencida responde
     * 404: para el candidato ya no existe.
     */
    public function show(Request $request, string $vacante): VacanteResource
    {
        $vacante = Vacante::query()
            ->abiertas()
            ->with(['empresa', 'carreras'])
            ->tap(fn ($consulta) => $this->marcarPostulaciones($consulta, $request->user()))
            ->findOrFail($vacante);

        return new VacanteResource($vacante);
    }

    /**
     * Para estudiantes y egresados agrega ya_postulado en la misma consulta,
     * sin una consulta extra por vacante.
     *
     * @param  Builder<Vacante>  $consulta
     */
    private function marcarPostulaciones(Builder $consulta, User $usuario): void
    {
        if ($usuario->rol->esCandidato()) {
            $consulta->withExists(['postulaciones as ya_postulado' => fn ($postulaciones) => $postulaciones->where('usuario_id', $usuario->id)]);
        }
    }
}
