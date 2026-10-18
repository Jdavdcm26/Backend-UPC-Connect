<?php

namespace App\Actions\Autenticacion;

use App\Enums\Autenticacion\EstadoUsuario;
use App\Enums\Autenticacion\Rol;
use App\Models\Autenticacion\Universidad;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class RegistrarUsuario
{
    /**
     * Crea la cuenta de un estudiante en estado PENDIENTE junto con la
     * evidencia de su consentimiento, y dispara el envío del correo de
     * verificación. Si el correo ya existe no hace nada y devuelve null, para
     * que la respuesta no revele si la cuenta existe.
     */
    public function handle(string $email, string $password, Universidad $universidad, string $versionPolitica): ?User
    {
        if (User::query()->withoutGlobalScopes()->where('email', $email)->exists()) {
            return null;
        }

        try {
            $usuario = DB::transaction(function () use ($email, $password, $universidad, $versionPolitica): User {
                $usuario = User::query()->make([
                    'email' => $email,
                    'password' => $password,
                ])->forceFill([
                    'universidad_id' => $universidad->id,
                    'rol' => Rol::Estudiante,
                    'estado' => EstadoUsuario::Pendiente,
                ]);

                $usuario->save();

                $usuario->consentimientos()->create([
                    'version_documento' => $versionPolitica,
                    'aceptado_en' => now(),
                ]);

                return $usuario;
            });
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        event(new Registered($usuario));

        return $usuario;
    }
}
