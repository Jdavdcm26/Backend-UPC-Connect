<?php

namespace App\Policies\Perfiles;

use App\Enums\Autenticacion\Rol;
use App\Models\Networking\Bloqueo;
use App\Models\Perfiles\Perfil;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;

class PerfilPolicy
{
    /**
     * Ven el perfil: la persona dueña, el administrador, la comunidad de su
     * universidad (estudiantes y egresados) y la empresa que recibió su
     * postulación (hallazgo 10). Quien fue bloqueado ya no ve el perfil de
     * quien lo bloqueó; quien bloquea sí ve el del otro, para poder
     * desbloquearlo (HU-31).
     */
    public function view(User $user, Perfil $perfil): bool
    {
        if ($perfil->usuario_id === $user->id || $user->rol === Rol::Admin) {
            return true;
        }

        if (Bloqueo::query()->where('bloqueador_id', $perfil->usuario_id)->where('bloqueado_id', $user->id)->exists()) {
            return false;
        }

        if ($user->rol->esCandidato()) {
            return $perfil->usuario()->withoutGlobalScopes()->value('universidad_id') === $user->universidad_id;
        }

        return $user->rol === Rol::Empresa
            && Postulacion::query()
                ->where('usuario_id', $perfil->usuario_id)
                ->whereHas('vacante', fn ($vacantes) => $vacantes->where('empresa_id', $user->empresa?->id))
                ->exists();
    }

    /**
     * Solo la persona dueña edita su perfil.
     */
    public function update(User $user, Perfil $perfil): bool
    {
        return $perfil->usuario_id === $user->id;
    }
}
