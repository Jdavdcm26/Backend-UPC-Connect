<?php

namespace App\Http\Resources\Networking;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cuenta que el usuario bloqueó (HU-31): lo justo para reconocerla y
 * desbloquearla. Espera el perfil y la empresa cargados.
 *
 * @mixin User
 */
class UsuarioBloqueadoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $nombre = $this->perfil !== null
            ? "{$this->perfil->nombres} {$this->perfil->apellidos}"
            : ($this->empresa?->razon_social ?? $this->name);

        return [
            'id' => $this->id,
            'nombre' => $nombre,
            'rol' => $this->rol,
            'slug' => $this->perfil?->slug,
            'bloqueado_en' => $this->pivot->created_at,
        ];
    }
}
