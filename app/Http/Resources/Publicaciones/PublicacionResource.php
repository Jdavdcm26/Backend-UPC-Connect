<?php

namespace App\Http\Resources\Publicaciones;

use App\Models\Publicaciones\Publicacion;
use App\Models\Publicaciones\PublicacionImagen;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Publicación tal como la ve el usuario con sesión. Espera la consulta con
 * conDetallesPara(), que trae el autor, los contadores y si ya reaccionó.
 *
 * @mixin Publicacion
 */
class PublicacionResource extends JsonResource
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
            'categoria' => [
                'id' => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
            ],
            'contenido' => $this->contenido,
            'detalles' => $this->detalles,
            // Una publicación general no se abre ni se cierra.
            'abierta' => $this->tipo->sePuedeCerrar() ? $this->abierta : null,
            'estado' => $this->estado,
            'imagenes' => $this->imagenes->map(fn (PublicacionImagen $imagen): array => [
                'id' => $imagen->id,
                'url' => $imagen->url(),
            ])->values(),
            'autor' => new AutorResource($this->autor),
            'reacciones' => $this->reacciones_count,
            'comentarios' => $this->comentarios_count,
            'reaccione' => (bool) $this->reaccione,
            'es_mia' => $this->esDe($request->user()),
            'editada_en' => $this->editada_en,
            'creada_en' => $this->created_at,
        ];
    }
}
