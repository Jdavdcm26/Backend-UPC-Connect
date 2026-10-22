<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalogos\CategoriaResource;
use App\Models\Catalogos\Categoria;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoriaController extends Controller
{
    /**
     * Categorías activas de publicación, para el formulario de publicar y los
     * filtros del feed, ordenadas por nombre sin distinguir tildes ni
     * mayúsculas. El catálogo se guarda en caché y se invalida al cambiar.
     */
    public function index(): AnonymousResourceCollection
    {
        // La caché guarda solo arreglos: Laravel no reconstruye objetos al leerla
        // (cache.serializable_classes), así que los modelos se rearman con hydrate().
        $atributos = Cache::remember(
            Categoria::CLAVE_CACHE_ACTIVAS,
            now()->addDay(),
            fn (): array => Categoria::query()->activas()->get()
                ->sortBy(fn (Categoria $categoria): string => Str::lower(Str::ascii($categoria->nombre)))
                ->map(fn (Categoria $categoria): array => $categoria->getAttributes())
                ->values()
                ->all(),
        );

        return CategoriaResource::collection(Categoria::hydrate($atributos));
    }
}
