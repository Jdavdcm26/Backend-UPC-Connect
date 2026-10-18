<?php

namespace App\Enums\Vacantes;

use Filament\Support\Contracts\HasLabel;

enum ModalidadVacante: string implements HasLabel
{
    case Presencial = 'PRESENCIAL';
    case Remota = 'REMOTA';
    case Hibrida = 'HIBRIDA';

    public function getLabel(): string
    {
        return match ($this) {
            self::Presencial => 'Presencial',
            self::Remota => 'Remota',
            self::Hibrida => 'Híbrida',
        };
    }
}
