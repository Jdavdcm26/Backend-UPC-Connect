<?php

namespace App\Enums\Publicaciones;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Estado de moderación de publicaciones y comentarios. Lo oculto deja de
 * mostrarse en la plataforma, pero se conserva para revisarlo.
 */
enum EstadoModeracion: string implements HasColor, HasLabel
{
    case Visible = 'VISIBLE';
    case Oculto = 'OCULTO';

    public function getLabel(): string
    {
        return match ($this) {
            self::Visible => 'Visible',
            self::Oculto => 'Oculto',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Visible => 'success',
            self::Oculto => 'danger',
        };
    }
}
