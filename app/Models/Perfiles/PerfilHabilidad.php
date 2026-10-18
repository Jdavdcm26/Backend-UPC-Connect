<?php

namespace App\Models\Perfiles;

use App\Enums\Perfiles\NivelHabilidad;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Habilidad de un perfil con su nivel.
 */
#[Table('perfil_habilidad')]
class PerfilHabilidad extends Pivot
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nivel' => NivelHabilidad::class,
        ];
    }
}
