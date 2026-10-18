<?php

namespace App\Actions\Administracion;

use App\Enums\Autenticacion\EstadoUsuario;
use App\Models\Administracion\Auditoria;
use App\Models\User;

class ReactivarUsuario
{
    /**
     * Devuelve el acceso a una cuenta suspendida. Si nunca verificó su correo
     * vuelve a PENDIENTE, porque activarla saltaría la verificación.
     */
    public function handle(User $usuario): void
    {
        $nuevoEstado = $usuario->hasVerifiedEmail() ? EstadoUsuario::Activo : EstadoUsuario::Pendiente;

        $usuario->forceFill(['estado' => $nuevoEstado])->save();

        Auditoria::registrar('usuario.reactivado', $usuario, ['estado_nuevo' => $nuevoEstado->value]);
    }
}
