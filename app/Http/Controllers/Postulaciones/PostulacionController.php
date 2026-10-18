<?php

namespace App\Http\Controllers\Postulaciones;

use App\Actions\Postulaciones\Postularse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Postulaciones\PostularseRequest;
use App\Http\Resources\Postulaciones\PostulacionResource;
use App\Models\Vacantes\Vacante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostulacionController extends Controller
{
    /**
     * Postulaciones de la persona con sesión, las más recientes primero, de a
     * 15 por cursor, con el estado vigente (HU-46).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $postulaciones = $request->user()->postulaciones()
            ->with('vacante.empresa')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(15);

        return PostulacionResource::collection($postulaciones);
    }

    /**
     * Postularse a una vacante con un mensaje opcional (HU-45).
     */
    public function store(PostularseRequest $request, Vacante $vacante, Postularse $postularse): JsonResponse
    {
        $postulacion = $postularse->handle($request->user(), $vacante, $request->validated('mensaje'));

        return (new PostulacionResource($postulacion->load('vacante.empresa')))->response()->setStatusCode(201);
    }
}
