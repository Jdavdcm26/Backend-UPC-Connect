<?php

namespace App\Enums\Autenticacion;

/**
 * Rol principal de una cuenta. Cada cuenta tiene un solo rol.
 */
enum Rol: string
{
    case Estudiante = 'ESTUDIANTE';
    case Egresado = 'EGRESADO';
    case Empresa = 'EMPRESA';
    case Moderador = 'MODERADOR';
    case Admin = 'ADMIN';

    /**
     * Estudiantes y egresados: tienen perfil y se postulan a vacantes.
     */
    public function esCandidato(): bool
    {
        return in_array($this, [self::Estudiante, self::Egresado], true);
    }
}
