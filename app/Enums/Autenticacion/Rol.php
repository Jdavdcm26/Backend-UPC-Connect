<?php

namespace App\Enums\Autenticacion;

use Filament\Support\Contracts\HasLabel;

/**
 * Rol principal de una cuenta. Cada cuenta tiene un solo rol.
 */
enum Rol: string implements HasLabel
{
    case Estudiante = 'ESTUDIANTE';
    case Egresado = 'EGRESADO';
    case Empresa = 'EMPRESA';
    case Moderador = 'MODERADOR';
    case Admin = 'ADMIN';

    public function getLabel(): string
    {
        return match ($this) {
            self::Estudiante => 'Estudiante',
            self::Egresado => 'Egresado',
            self::Empresa => 'Empresa',
            self::Moderador => 'Moderador',
            self::Admin => 'Administrador',
        };
    }

    /**
     * Estudiantes y egresados: tienen perfil y se postulan a vacantes.
     */
    public function esCandidato(): bool
    {
        return in_array($this, [self::Estudiante, self::Egresado], true);
    }
}
