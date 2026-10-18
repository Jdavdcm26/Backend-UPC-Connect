<?php

namespace App\Jobs\Vacantes;

use App\Enums\Vacantes\EstadoVacante;
use App\Models\Vacantes\Vacante;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Cierra las vacantes activas o pausadas cuya fecha de cierre ya pasó
 * (HU-42). Corre a diario poco después de la medianoche, hora de Colombia.
 * El listado ya las excluye por fecha, así que si un día no corre, ninguna
 * vacante vencida queda visible para los candidatos.
 */
class CerrarVacantesVencidas implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $cerradas = Vacante::query()
            ->whereIn('estado', [EstadoVacante::Activa, EstadoVacante::Pausada])
            ->whereDate('fecha_cierre', '<', today())
            ->update(['estado' => EstadoVacante::Cerrada, 'updated_at' => now()]);

        Log::info('Vacantes vencidas cerradas automáticamente.', ['cerradas' => $cerradas]);
    }
}
