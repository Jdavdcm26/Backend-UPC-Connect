<?php

namespace App\Actions\Perfiles;

use App\Models\Perfiles\Perfil;
use App\Models\User;
use Illuminate\Support\Str;

class GuardarPerfil
{
    /**
     * Crea o actualiza el perfil de la persona. El slug se genera una sola vez
     * a partir de nombres y apellidos y no cambia al editar, para no romper los
     * enlaces que ya se compartieron.
     *
     * @param  array<string, mixed>  $datos
     */
    public function handle(User $usuario, array $datos): Perfil
    {
        $perfil = $usuario->perfil()->first() ?? new Perfil;

        $perfil->fill($datos);

        if (! $perfil->exists) {
            $perfil->usuario()->associate($usuario);
            $perfil->slug = $this->slugUnico("{$datos['nombres']} {$datos['apellidos']}");
        }

        $perfil->save();

        return $perfil;
    }

    private function slugUnico(string $nombreCompleto): string
    {
        $base = Str::slug($nombreCompleto) ?: 'perfil';
        $slug = $base;

        for ($sufijo = 2; Perfil::query()->where('slug', $slug)->exists(); $sufijo++) {
            $slug = "{$base}-{$sufijo}";
        }

        return $slug;
    }
}
