<?php

namespace App\Http\Resources\Catalogos;

use App\Models\Catalogos\Carrera;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Carrera
 */
class CarreraResource extends JsonResource
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
            'nombre' => $this->nombre,
            'facultad' => $this->facultad,
            'nivel' => $this->nivel,
        ];
    }
}
