<?php

namespace App\Http\Resources\Publicaciones;

use App\Models\Publicaciones\Comentario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Comentario de una publicación (HU-26).
 *
 * @mixin Comentario
 */
class ComentarioResource extends JsonResource
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
            'contenido' => $this->contenido,
            'autor' => new AutorResource($this->autor),
            'es_mio' => $this->esDe($request->user()),
            'editado_en' => $this->editado_en,
            'creado_en' => $this->created_at,
        ];
    }
}
