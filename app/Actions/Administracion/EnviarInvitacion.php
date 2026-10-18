<?php

namespace App\Actions\Administracion;

use App\Models\User;
use App\Notifications\Administracion\InvitacionCuenta;
use Illuminate\Support\Facades\Password;

class EnviarInvitacion
{
    /**
     * Genera un token de invitación (7 días) y envía el correo para que la
     * persona defina su contraseña.
     */
    public function handle(User $usuario): void
    {
        $token = Password::broker('invitaciones')->createToken($usuario);

        $usuario->notify(new InvitacionCuenta($token));
    }
}
