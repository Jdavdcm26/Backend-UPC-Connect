<?php

namespace App\Http\Controllers\Publicaciones;

use App\Enums\Publicaciones\EstadoModeracion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publicaciones\GuardarComentarioRequest;
use App\Http\Resources\Publicaciones\ComentarioResource;
use App\Models\Publicaciones\Comentario;
use App\Models\Publicaciones\Publicacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Comentarios de las publicaciones (HU-26).
 */
class ComentarioController extends Controller
{
    /**
     * En orden de conversación (los más antiguos primero), de a 20 por cursor,
     * sin los ocultos ni los de personas con quienes hay un bloqueo.
     */
    public function index(Request $request, Publicacion $publicacion): AnonymousResourceCollection
    {
        Gate::authorize('view', $publicacion);

        $comentarios = $publicacion->comentarios()
            ->visiblesPara($request->user())
            ->with('autor.perfil.carrera')
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate(20);

        return ComentarioResource::collection($comentarios);
    }

    public function store(GuardarComentarioRequest $request, Publicacion $publicacion): JsonResponse
    {
        Gate::authorize('view', $publicacion);
        Gate::authorize('comentar', $publicacion);

        $comentario = $publicacion->comentarios()->make($request->validated())->forceFill([
            'autor_id' => $request->user()->id,
            'estado' => EstadoModeracion::Visible,
        ]);
        $comentario->save();

        return (new ComentarioResource($comentario->load('autor.perfil.carrera')))->response()->setStatusCode(201);
    }

    /**
     * Un comentario de una publicación borrada ya no se edita.
     */
    public function update(GuardarComentarioRequest $request, Comentario $comentario): ComentarioResource
    {
        abort_unless($comentario->publicacion()->exists(), 404);
        Gate::authorize('update', $comentario);

        $comentario->fill($request->validated());

        if ($comentario->isDirty()) {
            $comentario->editado_en = now();
            $comentario->save();
        }

        return new ComentarioResource($comentario->load('autor.perfil.carrera'));
    }

    public function destroy(Comentario $comentario): Response
    {
        Gate::authorize('delete', $comentario);

        $comentario->delete();

        return response()->noContent();
    }
}
