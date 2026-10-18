<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalogos\CarreraResource;
use App\Models\Catalogos\Carrera;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CarreraController extends Controller
{
    /**
     * Carreras activas de la universidad del usuario, para formularios y
     * filtros, ordenadas por facultad y nombre sin distinguir tildes ni
     * mayúsculas. El catálogo se guarda en caché y se invalida al cambiar.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $universidadId = $request->user()->universidad_id;

        // La caché guarda solo arreglos: Laravel no reconstruye objetos al leerla
        // (cache.serializable_classes), así que los modelos se rearman con hydrate().
        $atributos = Cache::remember(
            Carrera::claveCacheActivas($universidadId),
            now()->addDay(),
            fn (): array => Carrera::query()->activas()->get()
                ->sortBy(fn (Carrera $carrera): string => Str::lower(Str::ascii("{$carrera->facultad}|{$carrera->nombre}")))
                ->map(fn (Carrera $carrera): array => $carrera->getAttributes())
                ->values()
                ->all(),
        );

        return CarreraResource::collection(Carrera::hydrate($atributos));
    }
}
