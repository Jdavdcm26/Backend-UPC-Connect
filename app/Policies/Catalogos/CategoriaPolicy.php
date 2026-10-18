<?php

namespace App\Policies\Catalogos;

use App\Enums\Autenticacion\Rol;
use App\Models\Catalogos\Categoria;
use App\Models\User;

/**
 * Solo el administrador gestiona el catálogo. Los registros no se borran:
 * se desactivan, para conservar los datos que ya los usan.
 */
class CategoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->rol === Rol::Admin;
    }

    public function view(User $user, Categoria $registro): bool
    {
        return $user->rol === Rol::Admin;
    }

    public function create(User $user): bool
    {
        return $user->rol === Rol::Admin;
    }

    public function update(User $user, Categoria $registro): bool
    {
        return $user->rol === Rol::Admin;
    }

    public function delete(User $user, Categoria $registro): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
