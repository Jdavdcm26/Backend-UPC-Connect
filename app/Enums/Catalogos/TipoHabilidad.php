<?php

namespace App\Enums\Catalogos;

use Filament\Support\Contracts\HasLabel;

enum TipoHabilidad: string implements HasLabel
{
    case Tecnica = 'TECNICA';
    case Blanda = 'BLANDA';
    case Idioma = 'IDIOMA';

    public function getLabel(): string
    {
        return match ($this) {
            self::Tecnica => 'Técnica',
            self::Blanda => 'Blanda',
            self::Idioma => 'Idioma',
        };
    }
}
