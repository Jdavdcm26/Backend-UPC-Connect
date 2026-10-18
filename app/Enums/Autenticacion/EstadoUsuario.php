<?php

namespace App\Enums\Autenticacion;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Estado de una cuenta: solo las cuentas activas pueden iniciar sesión.
 */
enum EstadoUsuario: string implements HasColor, HasLabel
{
    case Pendiente = 'PENDIENTE';
    case Activo = 'ACTIVO';
    case Suspendido = 'SUSPENDIDO';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Activo => 'Activo',
            self::Suspendido => 'Suspendido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Activo => 'success',
            self::Suspendido => 'danger',
        };
    }
}
