<?php

namespace App\Http\Controllers\Vacantes;

use App\Actions\Vacantes\CambiarEstadoVacante;
use App\Enums\Vacantes\EstadoVacante;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vacantes\CambiarEstadoVacanteRequest;
use App\Http\Resources\Vacantes\VacanteResource;
use App\Models\Vacantes\Vacante;

class EstadoVacanteController extends Controller
{
    /**
     * Pausa, reactiva o cierra una vacante propia (HU-42).
     */
    public function __invoke(CambiarEstadoVacanteRequest $request, Vacante $vacantePropia, CambiarEstadoVacante $cambiarEstado): VacanteResource
    {
        $vacante = $cambiarEstado->handle($vacantePropia, EstadoVacante::from($request->validated('estado')));

        return new VacanteResource($vacante->load('carreras'));
    }
}
