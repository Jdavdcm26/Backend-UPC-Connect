<?php

namespace App\Http\Controllers\Postulaciones;

use App\Actions\Postulaciones\Postularse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Postulaciones\PostularseRequest;
use App\Http\Resources\Postulaciones\PostulacionResource;
use App\Models\Vacantes\Vacante;
use Illuminate\Http\JsonResponse;

class PostulacionController extends Controller
{
    /**
     * Postularse a una vacante con un mensaje opcional (HU-45).
     */
    public function store(PostularseRequest $request, Vacante $vacante, Postularse $postularse): JsonResponse
    {
        $postulacion = $postularse->handle($request->user(), $vacante, $request->validated('mensaje'));

        return (new PostulacionResource($postulacion->load('vacante.empresa')))->response()->setStatusCode(201);
    }
}
