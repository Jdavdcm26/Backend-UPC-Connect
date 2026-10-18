<?php

namespace App\Policies\Vacantes;

use App\Enums\Autenticacion\Rol;
use App\Enums\Vacantes\EstadoVacante;
use App\Models\User;
use App\Models\Vacantes\Vacante;
use Illuminate\Auth\Access\Response;

/**
 * Solo las empresas aprobadas publican (RF-42); cada empresa gestiona solo sus
 * vacantes. Una vacante cerrada es definitiva y ya no se edita.
 */
class VacantePolicy
{
    /**
     * Cualquier persona autenticada ve las vacantes abiertas; la empresa dueña
     * y el administrador ven también las pausadas y cerradas.
     */
    public function view(User $user, Vacante $vacante): bool
    {
        return $vacante->estaAbierta() || $user->rol === Rol::Admin || $this->esDuena($user, $vacante);
    }

    public function create(User $user): Response
    {
        if ($user->rol !== Rol::Empresa) {
            return Response::deny('Solo las empresas publican vacantes.');
        }

        return $user->empresa?->estaAprobada()
            ? Response::allow()
            : Response::deny('Tu empresa aún no está aprobada para publicar vacantes. Comunícate con la administración de la plataforma.');
    }

    public function update(User $user, Vacante $vacante): bool
    {
        return $this->esDuena($user, $vacante) && $vacante->estado !== EstadoVacante::Cerrada;
    }

    public function delete(User $user, Vacante $vacante): bool
    {
        return false;
    }

    private function esDuena(User $user, Vacante $vacante): bool
    {
        return $user->rol === Rol::Empresa && $vacante->empresa->usuario_id === $user->id;
    }
}
