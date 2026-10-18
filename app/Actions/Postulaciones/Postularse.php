<?php

namespace App\Actions\Postulaciones;

use App\Enums\Postulaciones\EstadoPostulacion;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;
use App\Models\Vacantes\Vacante;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Postularse
{
    /**
     * Registra la postulación con su primera fila de historial (HU-45). Solo
     * a vacantes abiertas y una sola vez por persona: la restricción única de
     * la base también atrapa dos solicitudes simultáneas.
     */
    public function handle(User $candidato, Vacante $vacante, ?string $mensaje): Postulacion
    {
        if (! $vacante->estaAbierta()) {
            throw ValidationException::withMessages([
                'vacante' => 'Esta vacante ya no recibe postulaciones.',
            ]);
        }

        if ($vacante->postulaciones()->where('usuario_id', $candidato->id)->exists()) {
            $this->rechazarDuplicada();
        }

        try {
            return DB::transaction(function () use ($candidato, $vacante, $mensaje): Postulacion {
                $postulacion = $vacante->postulaciones()->make(['mensaje' => $mensaje])->forceFill([
                    'usuario_id' => $candidato->id,
                    'estado' => EstadoPostulacion::Recibida,
                ]);
                $postulacion->save();

                $postulacion->historial()->create([
                    'estado' => EstadoPostulacion::Recibida,
                    'cambiado_por' => $candidato->id,
                    'cambiado_en' => $postulacion->created_at,
                ]);

                return $postulacion;
            });
        } catch (UniqueConstraintViolationException) {
            $this->rechazarDuplicada();
        }
    }

    private function rechazarDuplicada(): never
    {
        throw ValidationException::withMessages([
            'vacante' => 'Ya te postulaste a esta vacante. Sigue su estado en «Mis postulaciones».',
        ]);
    }
}
