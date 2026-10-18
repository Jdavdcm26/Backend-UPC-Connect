<?php

namespace App\Policies\Vacantes;

use App\Enums\Autenticacion\Rol;
use App\Models\User;
use App\Models\Vacantes\Empresa;

/**
 * El administrador gestiona las empresas; cada empresa solo ve la suya. En el
 * Release 1 la empresa no edita su perfil (HU-40 es del Release 2) y las
 * empresas no se borran: se suspende su cuenta.
 */
class EmpresaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->rol === Rol::Admin;
    }

    public function view(User $user, Empresa $empresa): bool
    {
        return $user->rol === Rol::Admin || $empresa->usuario_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->rol === Rol::Admin;
    }

    public function update(User $user, Empresa $empresa): bool
    {
        return $user->rol === Rol::Admin;
    }

    public function delete(User $user, Empresa $empresa): bool
    {
        return false;
    }
}
