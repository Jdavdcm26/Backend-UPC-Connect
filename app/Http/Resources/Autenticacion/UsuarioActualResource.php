<?php

namespace App\Http\Resources\Autenticacion;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UsuarioActualResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'rol' => $this->rol,
            'estado' => $this->estado,
            'tiene_perfil' => (bool) $this->perfil_exists,
            'universidad' => [
                'id' => $this->universidad->id,
                'nombre' => $this->universidad->nombre,
            ],
        ];
    }
}
