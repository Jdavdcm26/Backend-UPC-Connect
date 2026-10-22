<?php

namespace App\Enums\Publicaciones;

use Filament\Support\Contracts\HasLabel;

/**
 * GENERAL es texto libre; SERVICIO y BUSQUEDA_EQUIPO traen una plantilla con
 * sus propios campos y se pueden cerrar (HU-18 a HU-20).
 */
enum TipoPublicacion: string implements HasLabel
{
    case General = 'GENERAL';
    case Servicio = 'SERVICIO';
    case BusquedaEquipo = 'BUSQUEDA_EQUIPO';

    public function getLabel(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Servicio => 'Servicio o asesoría',
            self::BusquedaEquipo => 'Búsqueda de equipo',
        };
    }

    /**
     * Servicios y búsquedas de equipo se cierran cuando ya no se ofrecen o se
     * completan los cupos; una publicación general no.
     */
    public function sePuedeCerrar(): bool
    {
        return $this !== self::General;
    }
}
