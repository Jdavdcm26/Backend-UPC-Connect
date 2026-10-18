<?php

namespace App\Actions\Administracion;

use App\Enums\Autenticacion\EstadoUsuario;
use App\Enums\Autenticacion\Rol;
use App\Models\User;
use Illuminate\Support\Str;

class CrearCuentaInvitada
{
    /**
     * Cuenta activa y con el correo verificado (el administrador responde por
     * ella) en la universidad del administrador, con una contraseña aleatoria
     * que nadie conoce hasta que la persona defina la suya con la invitación.
     */
    public function handle(User $administrador, string $nombre, string $email, Rol $rol): User
    {
        $usuario = User::query()->make([
            'name' => $nombre,
            'email' => Str::lower(trim($email)),
            'password' => Str::random(64),
        ])->forceFill([
            'universidad_id' => $administrador->universidad_id,
            'rol' => $rol,
            'estado' => EstadoUsuario::Activo,
            'email_verified_at' => now(),
        ]);

        $usuario->save();

        return $usuario;
    }
}
