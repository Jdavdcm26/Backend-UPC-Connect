<?php

namespace App\Policies\Postulaciones;

use App\Enums\Autenticacion\Rol;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * El postulante ve sus postulaciones; solo la empresa dueña de la vacante las
 * gestiona. Ninguna otra empresa ni candidato accede a ellas.
 */
class PostulacionPolicy
{
    /**
     * Estudiantes y egresados con perfil (carrera y semestre o año de grado,
     * que el perfil exige al guardarse) pueden postularse (hallazgo 6).
     */
    public function create(User $user): Response
    {
        if (! $user->rol->esCandidato()) {
            return Response::deny('Solo estudiantes y egresados pueden postularse a vacantes.');
        }

        return $user->perfil()->exists()
            ? Response::allow()
            : Response::deny('Completa tu perfil (carrera y semestre, o año de grado) antes de postularte.');
    }

    public function view(User $user, Postulacion $postulacion): bool
    {
        return $postulacion->usuario_id === $user->id || $this->esEmpresaDuena($user, $postulacion);
    }

    public function cambiarEstado(User $user, Postulacion $postulacion): bool
    {
        return $this->esEmpresaDuena($user, $postulacion);
    }

    private function esEmpresaDuena(User $user, Postulacion $postulacion): bool
    {
        return $user->rol === Rol::Empresa && $postulacion->vacante->empresa->usuario_id === $user->id;
    }
}
