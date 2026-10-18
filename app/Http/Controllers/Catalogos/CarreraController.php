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

        $carreras = Cache::remember(
            Carrera::claveCacheActivas($universidadId),
            now()->addDay(),
            fn () => Carrera::query()->activas()->get()
                ->sortBy(fn (Carrera $carrera): string => Str::lower(Str::ascii("{$carrera->facultad}|{$carrera->nombre}")))
                ->values(),
        );

        return CarreraResource::collection($carreras);
    }
}
