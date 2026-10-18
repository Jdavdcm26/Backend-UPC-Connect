<?php

namespace App\Enums\Vacantes;

use Filament\Support\Contracts\HasLabel;

/**
 * Solo las empresas aprobadas publican vacantes (RF-42).
 */
enum EstadoAprobacionEmpresa: string implements HasLabel
{
    case Pendiente = 'PENDIENTE';
    case Aprobada = 'APROBADA';
    case Rechazada = 'RECHAZADA';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Aprobada => 'Aprobada',
            self::Rechazada => 'Rechazada',
        };
    }
}
