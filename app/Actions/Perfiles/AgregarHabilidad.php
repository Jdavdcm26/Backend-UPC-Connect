<?php

namespace App\Actions\Perfiles;

use App\Enums\Perfiles\NivelHabilidad;
use App\Models\Perfiles\Perfil;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class AgregarHabilidad
{
    public const int MAXIMO_POR_PERFIL = 30;

    /**
     * Agrega una habilidad del catálogo al perfil, sin duplicados (HU-11). La
     * llave primaria del pivote también atrapa dos solicitudes simultáneas.
     */
    public function handle(Perfil $perfil, string $habilidadId, ?NivelHabilidad $nivel): void
    {
        if ($perfil->habilidades()->whereKey($habilidadId)->exists()) {
            $this->rechazarDuplicada();
        }

        if ($perfil->habilidades()->count() >= self::MAXIMO_POR_PERFIL) {
            throw ValidationException::withMessages([
                'habilidad_id' => 'Puedes tener hasta '.self::MAXIMO_POR_PERFIL.' habilidades. Quita alguna para agregar otra.',
            ]);
        }

        try {
            $perfil->habilidades()->attach($habilidadId, ['nivel' => $nivel]);
        } catch (UniqueConstraintViolationException) {
            $this->rechazarDuplicada();
        }
    }

    private function rechazarDuplicada(): never
    {
        throw ValidationException::withMessages([
            'habilidad_id' => 'Ya tienes esta habilidad en tu perfil.',
        ]);
    }
}
