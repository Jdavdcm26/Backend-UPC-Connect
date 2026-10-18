<?php

namespace App\Policies\Catalogos;

use App\Enums\Autenticacion\Rol;
use App\Models\Catalogos\Carrera;
use App\Models\User;

/**
 * Solo el administrador gestiona el catálogo. Los registros no se borran:
 * se desactivan, para conservar los datos que ya los usan.
 */
class CarreraPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->rol === Rol::Admin;
    }

    public function view(User $user, Carrera $registro): bool
    {
        return $user->rol === Rol::Admin;
    }

    public function create(User $user): bool
    {
        return $user->rol === Rol::Admin;
    }

    public function update(User $user, Carrera $registro): bool
    {
        return $user->rol === Rol::Admin;
    }

    public function delete(User $user, Carrera $registro): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
