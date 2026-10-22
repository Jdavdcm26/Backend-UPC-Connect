<?php

namespace App\Policies\Publicaciones;

use App\Enums\Autenticacion\Rol;
use App\Enums\Publicaciones\EstadoModeracion;
use App\Models\Publicaciones\Comentario;
use App\Models\User;

/**
 * Cada autor edita y borra sus comentarios; el administrador los modera desde
 * el panel.
 */
class ComentarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->rol === Rol::Admin;
    }

    /**
     * Lo oculto por moderación ya no se edita; el autor solo puede borrarlo.
     */
    public function update(User $user, Comentario $comentario): bool
    {
        return $comentario->esDe($user) && $comentario->estado === EstadoModeracion::Visible;
    }

    public function delete(User $user, Comentario $comentario): bool
    {
        return $comentario->esDe($user);
    }

    public function moderar(User $user, Comentario $comentario): bool
    {
        return $user->rol === Rol::Admin;
    }
}
