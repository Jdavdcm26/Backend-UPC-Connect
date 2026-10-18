<?php

namespace App\Actions\Vacantes;

use App\Enums\Vacantes\EstadoVacante;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;
use Illuminate\Support\Facades\DB;

class GuardarVacante
{
    /**
     * Crea una vacante activa de la empresa, o actualiza una existente, junto
     * con sus carreras objetivo.
     *
     * @param  array<string, mixed>  $datos
     */
    public function handle(Empresa $empresa, array $datos, ?Vacante $vacante = null): Vacante
    {
        return DB::transaction(function () use ($empresa, $datos, $vacante): Vacante {
            $vacante ??= $empresa->vacantes()->make()->forceFill(['estado' => EstadoVacante::Activa]);

            $vacante->fill($datos)->save();
            $vacante->carreras()->sync($datos['carreras']);

            return $vacante;
        });
    }
}
