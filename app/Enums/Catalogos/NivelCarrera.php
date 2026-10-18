<?php

namespace App\Enums\Catalogos;

use Filament\Support\Contracts\HasLabel;

enum NivelCarrera: string implements HasLabel
{
    case Pregrado = 'PREGRADO';
    case Posgrado = 'POSGRADO';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pregrado => 'Pregrado',
            self::Posgrado => 'Posgrado',
        };
    }
}
