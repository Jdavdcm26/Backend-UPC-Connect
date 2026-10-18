<?php

namespace App\Policies;

use App\Enums\Autenticacion\EstadoUsuario;
use App\Enums\Autenticacion\Rol;
use App\Models\User;

/**
 * Gestión de cuentas desde el panel (HU-50). Las cuentas no se borran ni se
 * editan aquí: se suspenden y se reactivan, siempre con auditoría.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->rol === Rol::Admin;
    }

    public function view(User $user, User $model): bool
    {
        return $user->rol === Rol::Admin;
    }

    /**
     * El administrador da de alta egresados (y, con EmpresaPolicy, empresas).
     */
    public function create(User $user): bool
    {
        return $user->rol === Rol::Admin;
    }

    /**
     * Nadie se suspende a sí mismo, para no quedar sin acceso al panel.
     */
    public function suspender(User $user, User $model): bool
    {
        return $user->rol === Rol::Admin
            && $user->isNot($model)
            && $model->estado !== EstadoUsuario::Suspendido;
    }

    public function reactivar(User $user, User $model): bool
    {
        return $user->rol === Rol::Admin && $model->estado === EstadoUsuario::Suspendido;
    }
}
