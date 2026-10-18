<?php

namespace App\Http\Resources\Perfiles;

use App\Models\Catalogos\Habilidad;
use App\Models\Perfiles\Perfil;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil de otra persona con solo las secciones que el visitante puede ver
 * (HU-27, HU-47). Lo usan la gestión de candidatos y la vista de perfil ajeno,
 * para que la visibilidad se aplique en un solo lugar. El correo nunca sale.
 *
 * @mixin Perfil
 */
class PerfilVisibleResource extends JsonResource
{
    /**
     * @param  list<string>|null  $secciones  Secciones ya autorizadas por quien llama;
     *                                        si es null se calculan para el usuario de la solicitud.
     */
    public function __construct(Perfil $perfil, private ?array $secciones = null)
    {
        parent::__construct($perfil);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $secciones = $this->secciones ?? $this->resource->seccionesVisiblesPara($request->user());

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            $this->mergeWhen(in_array(Perfil::SECCION_BASICOS, $secciones, true), fn (): array => [
                'nombres' => $this->nombres,
                'apellidos' => $this->apellidos,
                'titular' => $this->titular,
                'carrera' => $this->carrera === null ? null : [
                    'id' => $this->carrera->id,
                    'nombre' => $this->carrera->nombre,
                ],
                'semestre' => $this->semestre,
                'anio_grado' => $this->anio_grado,
                'ciudad' => $this->ciudad,
            ]),
            $this->mergeWhen(in_array(Perfil::SECCION_DESCRIPCION, $secciones, true), fn (): array => [
                'descripcion' => $this->descripcion,
                'intereses' => $this->intereses ?? [],
            ]),
            $this->mergeWhen(in_array(Perfil::SECCION_HABILIDADES, $secciones, true), fn (): array => [
                'habilidades' => $this->habilidades->map(fn (Habilidad $habilidad): array => [
                    'id' => $habilidad->id,
                    'nombre' => $habilidad->nombre,
                    'tipo' => $habilidad->tipo,
                    'nivel' => $habilidad->pivot->nivel,
                ])->values(),
            ]),
        ];
    }
}
