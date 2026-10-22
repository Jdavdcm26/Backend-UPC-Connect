<?php

namespace App\Actions\Networking;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class BloquearUsuario
{
    /**
     * Bloquea a otra cuenta y deshace el seguimiento en las dos direcciones
     * (HU-31). Bloquear dos veces no es un error.
     */
    public function handle(User $usuario, User $bloqueado): void
    {
        DB::transaction(function () use ($usuario, $bloqueado): void {
            $usuario->bloqueados()->syncWithoutDetaching([$bloqueado->id]);
            $usuario->seguidos()->detach($bloqueado->id);
            $bloqueado->seguidos()->detach($usuario->id);
        });
    }
}
