<?php

namespace App\Http\Resources\Postulaciones;

use App\Http\Resources\Perfiles\PerfilVisibleResource;
use App\Models\Perfiles\Perfil;
use App\Models\Postulaciones\HistorialPostulacion;
use App\Models\Postulaciones\Postulacion;
use App\Models\Scopes\UniversidadScope;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Postulación vista por la empresa dueña de la vacante (HU-47). El candidato
 * sale con el recurso de perfil con visibilidad; el historial, solo en el
 * detalle.
 *
 * @mixin Postulacion
 */
class PostulacionRecibidaResource extends JsonResource
{
    /**
     * Relaciones que necesita el recurso. El candidato, su carrera y el
     * responsable de cada cambio pueden ser de otra universidad que la cuenta
     * de la empresa, así que se cargan sin el alcance por universidad.
     *
     * @return array<string, mixed>
     */
    public static function relaciones(bool $conHistorial = false): array
    {
        return [
            'usuario' => fn ($usuarios) => $usuarios->withoutGlobalScopes(),
            'usuario.perfil.carrera' => fn ($carreras) => $carreras->withoutGlobalScope(UniversidadScope::class),
            'usuario.perfil.habilidades',
            ...($conHistorial ? ['historial.responsable' => fn ($usuarios) => $usuarios->withoutGlobalScopes()] : []),
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $perfil = $this->usuario->perfil;

        return [
            'id' => $this->id,
            'estado' => $this->estado,
            'mensaje' => $this->mensaje,
            // La empresa ya está autorizada: la persona se postuló a su vacante.
            'candidato' => $perfil === null ? null : new PerfilVisibleResource($perfil, Perfil::SECCIONES_POR_DEFECTO),
            'historial' => $this->whenLoaded('historial', fn () => $this->historial->map(fn (HistorialPostulacion $cambio): array => [
                'estado' => $cambio->estado,
                'cambiado_en' => $cambio->cambiado_en,
                'responsable' => $cambio->responsable?->getFilamentName(),
            ])->values()),
            'creada_en' => $this->created_at,
            'actualizada_en' => $this->updated_at,
        ];
    }
}
