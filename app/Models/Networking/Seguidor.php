<?php

namespace App\Models\Networking;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Relación de seguimiento entre dos usuarios (HU-28).
 */
#[Table('seguidores')]
class Seguidor extends Pivot
{
    public const null UPDATED_AT = null;

    /**
     * La tabla solo registra cuándo se creó la relación. Un pivote toma por
     * defecto la columna updated_at del modelo padre, así que se anula aquí.
     */
    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }
}
