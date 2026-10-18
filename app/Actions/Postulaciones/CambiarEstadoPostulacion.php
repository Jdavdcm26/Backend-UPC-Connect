<?php

namespace App\Actions\Postulaciones;

use App\Enums\Postulaciones\EstadoPostulacion;
use App\Events\Postulaciones\PostulacionEstadoCambiado;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CambiarEstadoPostulacion
{
    /**
     * Cambia el estado, deja la fila de historial con responsable y fecha y
     * avisa del cambio (HU-47). Cualquier cambio entre los estados de gestión
     * está permitido: el documento base no define transiciones.
     */
    public function handle(Postulacion $postulacion, EstadoPostulacion $nuevoEstado, User $responsable): Postulacion
    {
        if ($postulacion->estado === $nuevoEstado) {
            throw ValidationException::withMessages([
                'estado' => 'La postulación ya está en ese estado.',
            ]);
        }

        return DB::transaction(function () use ($postulacion, $nuevoEstado, $responsable): Postulacion {
            $postulacion->forceFill(['estado' => $nuevoEstado])->save();

            $cambio = $postulacion->historial()->create([
                'estado' => $nuevoEstado,
                'cambiado_por' => $responsable->id,
                'cambiado_en' => $postulacion->updated_at,
            ]);

            PostulacionEstadoCambiado::dispatch($postulacion, $cambio);

            return $postulacion;
        });
    }
}
