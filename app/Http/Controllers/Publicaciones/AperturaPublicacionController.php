<?php

namespace App\Http\Controllers\Publicaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Publicaciones\CambiarAperturaRequest;
use App\Http\Resources\Publicaciones\PublicacionResource;
use App\Models\Publicaciones\Publicacion;

class AperturaPublicacionController extends Controller
{
    /**
     * El autor cierra un servicio o una búsqueda de equipo cuando ya no se
     * ofrece o se completan los cupos, y puede volver a abrirlo (HU-20).
     */
    public function __invoke(CambiarAperturaRequest $request, Publicacion $publicacion): PublicacionResource
    {
        $publicacion->forceFill(['abierta' => $request->boolean('abierta')])->save();

        return new PublicacionResource(
            Publicacion::query()->conDetallesPara($request->user())->findOrFail($publicacion->id),
        );
    }
}
