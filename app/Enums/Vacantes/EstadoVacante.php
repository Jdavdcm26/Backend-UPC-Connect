<?php

namespace App\Enums\Vacantes;

use Filament\Support\Contracts\HasLabel;

/**
 * ACTIVA y PAUSADA se alternan; CERRADA es definitiva y conserva las
 * postulaciones (HU-42).
 */
enum EstadoVacante: string implements HasLabel
{
    case Activa = 'ACTIVA';
    case Pausada = 'PAUSADA';
    case Cerrada = 'CERRADA';

    public function getLabel(): string
    {
        return match ($this) {
            self::Activa => 'Activa',
            self::Pausada => 'Pausada',
            self::Cerrada => 'Cerrada',
        };
    }
}
