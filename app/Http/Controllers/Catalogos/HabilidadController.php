<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\BuscarHabilidadesRequest;
use App\Http\Resources\Catalogos\HabilidadResource;
use App\Models\Catalogos\Habilidad;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HabilidadController extends Controller
{
    private const int LIMITE_SUGERENCIAS = 20;

    /**
     * Habilidades activas ordenadas por nombre sin distinguir tildes ni
     * mayúsculas. Con "q" sirve de autocompletado: ignora tildes y
     * mayúsculas, pone primero las que empiezan por el texto y devuelve como
     * máximo 20. El catálogo es pequeño, así que se filtra sobre la caché.
     */
    public function index(BuscarHabilidadesRequest $request): AnonymousResourceCollection
    {
        // La caché guarda solo arreglos: Laravel no reconstruye objetos al leerla
        // (cache.serializable_classes), así que los modelos se rearman con hydrate().
        $atributos = Cache::remember(
            Habilidad::CLAVE_CACHE_ACTIVAS,
            now()->addDay(),
            fn (): array => Habilidad::query()->activas()->get()
                ->sortBy(fn (Habilidad $habilidad): string => $this->normalizar($habilidad->nombre))
                ->map(fn (Habilidad $habilidad): array => $habilidad->getAttributes())
                ->values()
                ->all(),
        );

        $habilidades = Habilidad::hydrate($atributos);

        $busqueda = $this->normalizar((string) $request->validated('q'));

        if ($busqueda !== '') {
            $habilidades = $habilidades
                ->filter(fn (Habilidad $habilidad): bool => str_contains($this->normalizar($habilidad->nombre), $busqueda))
                ->sortBy(fn (Habilidad $habilidad): int => str_starts_with($this->normalizar($habilidad->nombre), $busqueda) ? 0 : 1)
                ->take(self::LIMITE_SUGERENCIAS)
                ->values();
        }

        return HabilidadResource::collection($habilidades);
    }

    private function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii(trim($texto)));
    }
}
