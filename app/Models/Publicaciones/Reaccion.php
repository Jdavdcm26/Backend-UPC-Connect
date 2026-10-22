<?php

namespace App\Models\Publicaciones;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Reacción de una persona a una publicación (HU-25). Hay una por persona.
 */
#[Table('reacciones')]
class Reaccion extends Pivot
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
