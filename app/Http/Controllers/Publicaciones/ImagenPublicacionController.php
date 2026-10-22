<?php

namespace App\Http\Controllers\Publicaciones;

use App\Actions\Publicaciones\AgregarImagenes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publicaciones\AgregarImagenesRequest;
use App\Http\Resources\Publicaciones\PublicacionResource;
use App\Models\Publicaciones\Publicacion;
use App\Models\Publicaciones\PublicacionImagen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Imágenes de una publicación (HU-21). Ambas acciones devuelven la
 * publicación actualizada.
 */
class ImagenPublicacionController extends Controller
{
    public function store(AgregarImagenesRequest $request, Publicacion $publicacion, AgregarImagenes $agregarImagenes): JsonResponse
    {
        $agregarImagenes->handle($publicacion, $request->file('imagenes'));

        return $this->respuesta($request, $publicacion)->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Publicacion $publicacion, PublicacionImagen $imagen): PublicacionResource
    {
        abort_unless($imagen->publicacion_id === $publicacion->id, 404);
        Gate::authorize('view', $publicacion);
        Gate::authorize('update', $publicacion);

        $imagen->delete();
        Storage::disk(PublicacionImagen::DISCO)->delete($imagen->ruta);

        return $this->respuesta($request, $publicacion);
    }

    private function respuesta(Request $request, Publicacion $publicacion): PublicacionResource
    {
        return new PublicacionResource(
            Publicacion::query()->conDetallesPara($request->user())->findOrFail($publicacion->id),
        );
    }
}
