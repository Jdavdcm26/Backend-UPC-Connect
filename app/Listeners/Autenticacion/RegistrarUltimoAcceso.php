<?php

namespace App\Listeners\Autenticacion;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class RegistrarUltimoAcceso
{
    /**
     * Guarda el momento del inicio de sesión sin tocar updated_at.
     */
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->newQueryWithoutScopes()
            ->whereKey($event->user->getKey())
            ->toBase()
            ->update(['ultimo_acceso_en' => now()]);
    }
}
