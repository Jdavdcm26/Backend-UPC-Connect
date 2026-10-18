<?php

namespace App\Http\Resources\Perfiles;

use App\Models\Perfiles\Perfil;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Perfil
 */
class PerfilResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'titular' => $this->titular,
            'descripcion' => $this->descripcion,
            'carrera' => [
                'id' => $this->carrera->id,
                'nombre' => $this->carrera->nombre,
                'facultad' => $this->carrera->facultad,
            ],
            'semestre' => $this->semestre,
            'anio_grado' => $this->anio_grado,
            'ciudad' => $this->ciudad,
            'intereses' => $this->intereses ?? [],
            'habilidades' => $this->habilidades->map(fn ($habilidad): array => [
                'id' => $habilidad->id,
                'nombre' => $habilidad->nombre,
                'tipo' => $habilidad->tipo,
                'nivel' => $habilidad->pivot->nivel,
            ])->values(),
            'actualizado_en' => $this->updated_at,
        ];
    }
}
