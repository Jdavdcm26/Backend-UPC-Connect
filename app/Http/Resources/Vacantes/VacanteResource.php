<?php

namespace App\Http\Resources\Vacantes;

use App\Models\Catalogos\Carrera;
use App\Models\Vacantes\Vacante;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vacante
 */
class VacanteResource extends JsonResource
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
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'requisitos' => $this->requisitos,
            'ciudad' => $this->ciudad,
            'modalidad' => $this->modalidad,
            'estado' => $this->estado,
            'abierta' => $this->estaAbierta(),
            'fecha_cierre' => $this->fecha_cierre->toDateString(),
            'carreras' => $this->whenLoaded('carreras', fn () => $this->carreras->map(fn (Carrera $carrera): array => [
                'id' => $carrera->id,
                'nombre' => $carrera->nombre,
            ])->values()),
            'empresa' => $this->whenLoaded('empresa', fn (): array => [
                'id' => $this->empresa->id,
                'razon_social' => $this->empresa->razon_social,
                'ciudad' => $this->empresa->ciudad,
            ]),
            'creada_en' => $this->created_at,
            'actualizada_en' => $this->updated_at,
        ];
    }
}
