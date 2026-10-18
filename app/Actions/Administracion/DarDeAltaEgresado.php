<?php

namespace App\Actions\Administracion;

use App\Enums\Autenticacion\Rol;
use App\Models\Administracion\Auditoria;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DarDeAltaEgresado
{
    public function __construct(
        private CrearCuentaInvitada $crearCuentaInvitada,
        private EnviarInvitacion $enviarInvitacion,
    ) {}

    /**
     * Crea la cuenta activa de un egresado y le envía la invitación para
     * definir su contraseña (HU-50, criterio 4).
     */
    public function handle(User $administrador, string $nombre, string $email): User
    {
        $egresado = DB::transaction(function () use ($administrador, $nombre, $email): User {
            $egresado = $this->crearCuentaInvitada->handle($administrador, $nombre, $email, Rol::Egresado);

            Auditoria::registrar('egresado.creado', $egresado, ['email' => $egresado->email]);

            return $egresado;
        });

        $this->enviarInvitacion->handle($egresado);

        return $egresado;
    }
}
