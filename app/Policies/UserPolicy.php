<?php

namespace App\Policies;

use App\Enums\Autenticacion\EstadoUsuario;
use App\Enums\Autenticacion\Rol;
use App\Models\Networking\Bloqueo;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Gestión de cuentas desde el panel (HU-50). Las cuentas no se borran ni se
 * editan aquí: se suspenden y se reactivan, siempre con auditoría. También
 * decide quién puede seguir y bloquear a quién (HU-28, HU-31).
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

    /**
     * Solo estudiantes y egresados se siguen entre sí, y no con alguien con
     * quien hay un bloqueo.
     */
    public function seguir(User $user, User $model): Response
    {
        if (! $user->rol->esCandidato()) {
            return Response::deny('Solo estudiantes y egresados pueden seguir a otras personas.');
        }

        if ($user->is($model)) {
            return Response::deny('No puedes seguirte a ti mismo.');
        }

        if (! $model->rol->esCandidato()) {
            return Response::deny('Solo puedes seguir a estudiantes y egresados.');
        }

        return Bloqueo::entre($user, $model)
            ? Response::deny('No puedes seguir a esta persona.')
            : Response::allow();
    }

    /**
     * Estudiantes, egresados y empresas bloquean a otras cuentas de la
     * comunidad; las de administración no se bloquean.
     */
    public function bloquear(User $user, User $model): Response
    {
        if (! $user->rol->esCandidato() && $user->rol !== Rol::Empresa) {
            return Response::deny('Tu cuenta no puede bloquear a otras personas.');
        }

        if ($user->is($model)) {
            return Response::deny('No puedes bloquearte a ti mismo.');
        }

        return $model->rol->esCandidato() || $model->rol === Rol::Empresa
            ? Response::allow()
            : Response::deny('No puedes bloquear cuentas de la administración.');
    }
}
