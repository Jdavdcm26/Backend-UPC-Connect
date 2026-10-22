<?php

namespace App\Policies\Publicaciones;

use App\Enums\Autenticacion\Rol;
use App\Enums\Publicaciones\EstadoModeracion;
use App\Models\Networking\Bloqueo;
use App\Models\Publicaciones\Publicacion;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Estudiantes y egresados publican, reaccionan y comentan; las empresas leen
 * el feed sin interactuar; el administrador modera desde el panel.
 */
class PublicacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->rol->esCandidato() || in_array($user->rol, [Rol::Empresa, Rol::Admin], true);
    }

    /**
     * Una publicación oculta por moderación o de alguien con quien hay un
     * bloqueo responde 404, igual que una que no existe. El autor siempre ve
     * la suya.
     */
    public function view(User $user, Publicacion $publicacion): Response
    {
        if ($user->rol === Rol::Admin || $publicacion->esDe($user)) {
            return Response::allow();
        }

        $visible = $this->viewAny($user)
            && $publicacion->estado === EstadoModeracion::Visible
            && ! Bloqueo::entre($user, $publicacion->autor()->withoutGlobalScopes()->firstOrFail());

        return $visible ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * Publicar exige perfil, porque la publicación muestra el nombre y la
     * carrera de quien la escribe.
     */
    public function create(User $user): Response
    {
        if (! $user->rol->esCandidato()) {
            return Response::deny('Solo estudiantes y egresados pueden publicar.');
        }

        return $user->perfil()->exists()
            ? Response::allow()
            : Response::deny('Completa tu perfil antes de publicar.');
    }

    /**
     * Lo oculto por moderación ya no se edita; el autor solo puede borrarlo.
     */
    public function update(User $user, Publicacion $publicacion): bool
    {
        return $publicacion->esDe($user) && $publicacion->estado === EstadoModeracion::Visible;
    }

    public function delete(User $user, Publicacion $publicacion): bool
    {
        return $publicacion->esDe($user);
    }

    public function reaccionar(User $user, Publicacion $publicacion): Response
    {
        return $user->rol->esCandidato()
            ? Response::allow()
            : Response::deny('Solo estudiantes y egresados pueden reaccionar a las publicaciones.');
    }

    public function comentar(User $user, Publicacion $publicacion): Response
    {
        if (! $user->rol->esCandidato()) {
            return Response::deny('Solo estudiantes y egresados pueden comentar las publicaciones.');
        }

        return $user->perfil()->exists()
            ? Response::allow()
            : Response::deny('Completa tu perfil antes de comentar.');
    }

    public function moderar(User $user, Publicacion $publicacion): bool
    {
        return $user->rol === Rol::Admin;
    }
}
