<?php

namespace App\Enums\Autenticacion;

/**
 * Estado de una cuenta: solo las cuentas activas pueden iniciar sesión.
 */
enum EstadoUsuario: string
{
    case Pendiente = 'PENDIENTE';
    case Activo = 'ACTIVO';
    case Suspendido = 'SUSPENDIDO';
}
