<?php

namespace App\Listeners\Notificaciones;

use App\Events\Postulaciones\PostulacionEstadoCambiado;
use App\Notifications\Notificaciones\EstadoPostulacionCambiado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

/**
 * Avisa por correo al candidato cada cambio de estado de su postulación
 * (HU-48). En el Release 1 solo hay correo (hallazgo 8).
 */
class AvisarCambioEstadoPostulacion implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'mail';

    public int $tries = 5;

    /**
     * Segundos de espera antes de cada reintento.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    /**
     * Un correo por fila de historial: si el mismo cambio se procesa dos
     * veces, el segundo no envía nada.
     */
    public function handle(PostulacionEstadoCambiado $event): void
    {
        $clave = "avisos:postulacion:{$event->cambio->id}";

        if (Cache::has($clave)) {
            return;
        }

        $postulacion = $event->postulacion->loadMissing(['vacante.empresa', 'usuario' => fn ($usuarios) => $usuarios->withoutGlobalScopes()]);

        $postulacion->usuario->notifyNow(new EstadoPostulacionCambiado($postulacion, $event->cambio->estado));

        Cache::put($clave, true, now()->addDays(30));
    }
}
