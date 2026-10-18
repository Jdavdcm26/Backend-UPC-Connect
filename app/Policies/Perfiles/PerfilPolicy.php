<?php

namespace App\Policies\Perfiles;

use App\Models\Perfiles\Perfil;
use App\Models\User;

class PerfilPolicy
{
    /**
     * Solo la persona dueña edita su perfil.
     */
    public function update(User $user, Perfil $perfil): bool
    {
        return $perfil->usuario_id === $user->id;
    }
}
