<?php

namespace App\Enums\Postulaciones;

use Filament\Support\Contracts\HasLabel;

enum EstadoPostulacion: string implements HasLabel
{
    case Recibida = 'RECIBIDA';
    case EnRevision = 'EN_REVISION';
    case Entrevista = 'ENTREVISTA';
    case Seleccionado = 'SELECCIONADO';
    case NoSeleccionado = 'NO_SELECCIONADO';
    // Reservado para HU-49 (retirar postulación), del Release 2.
    case Retirada = 'RETIRADA';

    public function getLabel(): string
    {
        return match ($this) {
            self::Recibida => 'Recibida',
            self::EnRevision => 'En revisión',
            self::Entrevista => 'Entrevista',
            self::Seleccionado => 'Seleccionado',
            self::NoSeleccionado => 'No seleccionado',
            self::Retirada => 'Retirada',
        };
    }

    /**
     * Estados que la empresa puede asignar al gestionar candidatos (HU-47).
     *
     * @return list<self>
     */
    public static function deGestion(): array
    {
        return [self::Recibida, self::EnRevision, self::Entrevista, self::Seleccionado, self::NoSeleccionado];
    }
}
