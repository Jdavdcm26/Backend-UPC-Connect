<?php

namespace App\Actions\Autenticacion;

use App\Enums\Autenticacion\EstadoUsuario;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

class ActivarCuenta
{
    /**
     * Marca el correo como verificado y pasa la cuenta de PENDIENTE a ACTIVO.
     * Una cuenta suspendida conserva su estado aunque verifique el correo.
     */
    public function handle(User $usuario): void
    {
        $usuario->forceFill(['email_verified_at' => $usuario->freshTimestamp()]);

        if ($usuario->estado === EstadoUsuario::Pendiente) {
            $usuario->forceFill(['estado' => EstadoUsuario::Activo]);
        }

        $usuario->save();

        event(new Verified($usuario));
    }
}
