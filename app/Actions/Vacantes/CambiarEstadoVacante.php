<?php

namespace App\Actions\Vacantes;

use App\Enums\Vacantes\EstadoVacante;
use App\Models\Vacantes\Vacante;
use Illuminate\Validation\ValidationException;

class CambiarEstadoVacante
{
    /**
     * ACTIVA y PAUSADA se alternan; cerrar es definitivo y conserva las
     * postulaciones (HU-42). Una vacante vencida no se reactiva sin antes
     * mover su fecha de cierre.
     */
    public function handle(Vacante $vacante, EstadoVacante $nuevoEstado): Vacante
    {
        if ($vacante->estado === EstadoVacante::Cerrada && $nuevoEstado !== EstadoVacante::Cerrada) {
            throw ValidationException::withMessages([
                'estado' => 'Esta vacante está cerrada y no se puede reabrir. Publica una vacante nueva.',
            ]);
        }

        if ($nuevoEstado === EstadoVacante::Activa && $vacante->fecha_cierre->isBefore(today())) {
            throw ValidationException::withMessages([
                'estado' => 'La fecha de cierre ya pasó. Actualiza la fecha antes de reactivar la vacante.',
            ]);
        }

        $vacante->forceFill(['estado' => $nuevoEstado])->save();

        return $vacante;
    }
}
