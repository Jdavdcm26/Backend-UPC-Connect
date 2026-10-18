<?php

namespace App\Enums\Vacantes;

use Filament\Support\Contracts\HasLabel;

enum TipoVacante: string implements HasLabel
{
    case Vacante = 'VACANTE';
    case Practica = 'PRACTICA';
    case Pasantia = 'PASANTIA';

    public function getLabel(): string
    {
        return match ($this) {
            self::Vacante => 'Vacante',
            self::Practica => 'Práctica',
            self::Pasantia => 'Pasantía',
        };
    }
}
