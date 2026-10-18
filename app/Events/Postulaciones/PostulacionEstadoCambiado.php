<?php

namespace App\Events\Postulaciones;

use App\Models\Postulaciones\HistorialPostulacion;
use App\Models\Postulaciones\Postulacion;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * La empresa cambió el estado de una postulación. Se despacha cuando la
 * transacción termina, y el registro de historial identifica el cambio para
 * no avisar dos veces (HU-48).
 */
class PostulacionEstadoCambiado implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Postulacion $postulacion,
        public HistorialPostulacion $cambio,
    ) {}
}
