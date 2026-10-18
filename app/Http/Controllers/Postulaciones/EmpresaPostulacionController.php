<?php

namespace App\Http\Controllers\Postulaciones;

use App\Http\Controllers\Controller;
use App\Http\Resources\Postulaciones\PostulacionRecibidaResource;
use App\Models\Postulaciones\Postulacion;
use App\Models\Vacantes\Vacante;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Postulaciones que recibe la empresa en sus vacantes (HU-47). Las de otra
 * empresa responden 404.
 */
class EmpresaPostulacionController extends Controller
{
    public function index(Vacante $vacantePropia): AnonymousResourceCollection
    {
        $postulaciones = $vacantePropia->postulaciones()
            ->with(PostulacionRecibidaResource::relaciones())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(15);

        return PostulacionRecibidaResource::collection($postulaciones);
    }

    public function show(Postulacion $postulacionPropia): PostulacionRecibidaResource
    {
        return new PostulacionRecibidaResource($postulacionPropia->load(PostulacionRecibidaResource::relaciones(conHistorial: true)));
    }
}
