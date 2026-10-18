<?php

namespace App\Http\Controllers\Postulaciones;

use App\Actions\Postulaciones\CambiarEstadoPostulacion;
use App\Enums\Postulaciones\EstadoPostulacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Postulaciones\CambiarEstadoPostulacionRequest;
use App\Http\Resources\Postulaciones\PostulacionRecibidaResource;
use App\Models\Postulaciones\Postulacion;

class EstadoPostulacionController extends Controller
{
    /**
     * Cambia el estado de una postulación de una vacante propia y devuelve el
     * detalle con el historial actualizado (HU-47).
     */
    public function __invoke(
        CambiarEstadoPostulacionRequest $request,
        Postulacion $postulacionPropia,
        CambiarEstadoPostulacion $cambiarEstado,
    ): PostulacionRecibidaResource {
        $postulacion = $cambiarEstado->handle(
            $postulacionPropia,
            EstadoPostulacion::from($request->validated('estado')),
            $request->user(),
        );

        return new PostulacionRecibidaResource($postulacion->load(PostulacionRecibidaResource::relaciones(conHistorial: true)));
    }
}
