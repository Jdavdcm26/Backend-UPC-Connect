<?php

namespace App\Http\Controllers\Publicaciones;

use App\Actions\Publicaciones\GuardarPublicacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publicaciones\BuscarPublicacionesRequest;
use App\Http\Requests\Publicaciones\GuardarPublicacionRequest;
use App\Http\Resources\Publicaciones\PublicacionResource;
use App\Models\Networking\Seguidor;
use App\Models\Publicaciones\Publicacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Publicaciones de la comunidad de la universidad (HU-18 a HU-24).
 */
class PublicacionController extends Controller
{
    /**
     * Feed de la comunidad, las más recientes primero y de a 15 por cursor.
     * Con alcance=seguidos solo trae las de las personas que el usuario sigue
     * (HU-24). Nunca incluye lo oculto por moderación ni lo de personas con
     * quienes hay un bloqueo.
     */
    public function index(BuscarPublicacionesRequest $request): AnonymousResourceCollection
    {
        $usuario = $request->user();
        $filtros = $request->validated();

        $publicaciones = Publicacion::query()
            ->visiblesPara($usuario)
            ->conDetallesPara($usuario)
            ->when(($filtros['alcance'] ?? null) === 'seguidos', fn ($consulta) => $consulta->whereIn(
                'autor_id',
                Seguidor::query()->select('seguido_id')->where('seguidor_id', $usuario->id),
            ))
            ->when($filtros['tipo'] ?? null, fn ($consulta, string $tipo) => $consulta->where('tipo', $tipo))
            ->when($filtros['categoria_id'] ?? null, fn ($consulta, string $categoria) => $consulta->where('categoria_id', $categoria))
            ->when($filtros['autor_id'] ?? null, fn ($consulta, string $autor) => $consulta->where('autor_id', $autor))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(15)
            ->withQueryString();

        return PublicacionResource::collection($publicaciones);
    }

    public function store(GuardarPublicacionRequest $request, GuardarPublicacion $guardarPublicacion): JsonResponse
    {
        $usuario = $request->user();

        $publicacion = $guardarPublicacion->handle(
            $usuario,
            $request->datos(),
            $request->tipo(),
            imagenes: $request->file('imagenes', []),
        );

        return $this->respuesta($publicacion, $usuario)->response()->setStatusCode(201);
    }

    public function show(Request $request, Publicacion $publicacion): PublicacionResource
    {
        Gate::authorize('view', $publicacion);

        return $this->respuesta($publicacion, $request->user());
    }

    public function update(GuardarPublicacionRequest $request, Publicacion $publicacion, GuardarPublicacion $guardarPublicacion): PublicacionResource
    {
        $guardarPublicacion->handle($request->user(), $request->datos(), publicacion: $publicacion);

        return $this->respuesta($publicacion, $request->user());
    }

    /**
     * Borrado lógico: la publicación y sus comentarios dejan de mostrarse
     * (HU-23).
     */
    public function destroy(Request $request, Publicacion $publicacion): Response
    {
        Gate::authorize('view', $publicacion);
        Gate::authorize('delete', $publicacion);

        $publicacion->delete();

        return response()->noContent();
    }

    /**
     * La publicación con todo lo que muestra, en una sola consulta.
     */
    private function respuesta(Publicacion $publicacion, User $usuario): PublicacionResource
    {
        return new PublicacionResource(
            Publicacion::query()->conDetallesPara($usuario)->findOrFail($publicacion->id),
        );
    }
}
