<?php

namespace App\Actions\Administracion;

use App\Enums\Autenticacion\EstadoUsuario;
use App\Models\Administracion\Auditoria;
use App\Models\User;

class SuspenderUsuario
{
    /**
     * Suspende la cuenta con un motivo y lo deja en la auditoría. La cuenta no
     * puede iniciar sesión y su sesión abierta se cierra en la siguiente
     * solicitud.
     */
    public function handle(User $usuario, string $motivo): void
    {
        $estadoAnterior = $usuario->estado;

        $usuario->forceFill(['estado' => EstadoUsuario::Suspendido])->save();

        Auditoria::registrar('usuario.suspendido', $usuario, [
            'motivo' => $motivo,
            'estado_anterior' => $estadoAnterior->value,
        ]);
    }
}
