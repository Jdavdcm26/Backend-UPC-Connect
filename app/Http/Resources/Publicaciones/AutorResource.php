<?php

namespace App\Http\Resources\Publicaciones;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Autor de una publicación o de un comentario: lo que se muestra junto al
 * contenido y el slug para abrir su perfil. El correo nunca sale.
 *
 * @mixin User
 */
class AutorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $perfil = $this->perfil;

        return [
            'id' => $this->id,
            'nombres' => $perfil?->nombres,
            'apellidos' => $perfil?->apellidos,
            'titular' => $perfil?->titular,
            'slug' => $perfil?->slug,
            'carrera' => $perfil?->carrera?->nombre,
        ];
    }
}
