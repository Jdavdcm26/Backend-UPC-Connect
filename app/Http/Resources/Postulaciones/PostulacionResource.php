<?php

namespace App\Http\Resources\Postulaciones;

use App\Models\Postulaciones\Postulacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Postulación vista por el candidato (HU-45, HU-46).
 *
 * @mixin Postulacion
 */
class PostulacionResource extends JsonResource
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
            'estado' => $this->estado,
            'mensaje' => $this->mensaje,
            'vacante' => [
                'id' => $this->vacante->id,
                'titulo' => $this->vacante->titulo,
                'abierta' => $this->vacante->estaAbierta(),
                'empresa' => [
                    'id' => $this->vacante->empresa->id,
                    'razon_social' => $this->vacante->empresa->razon_social,
                ],
            ],
            'creada_en' => $this->created_at,
            'actualizada_en' => $this->updated_at,
        ];
    }
}
