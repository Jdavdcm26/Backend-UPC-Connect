<?php

namespace App\Actions\Analitica;

use App\Enums\Autenticacion\Rol;
use App\Enums\Postulaciones\EstadoPostulacion;
use App\Enums\Vacantes\EstadoAprobacionEmpresa;
use App\Models\Postulaciones\HistorialPostulacion;
use App\Models\Postulaciones\Postulacion;
use App\Models\Scopes\UniversidadScope;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Indicadores del panel de KPIs (HU-58) para una universidad, un periodo y,
 * opcionalmente, una carrera. Solo usa datos del Release 1 (hallazgo 9).
 *
 * Definiciones provisionales, adaptadas de la sección 17 del documento base y
 * pendientes de aprobación del docente tutor:
 * - Registrados: estudiantes y egresados con el correo verificado al final del periodo.
 * - Activos: candidatos que se postularon y empresas que publicaron una vacante en el periodo.
 * - Activación: % de los registrados en el periodo que en sus primeros 7 días tenía
 *   perfil y al menos 3 habilidades.
 * - Empresas activas: con al menos una vacante en los 90 días previos al final del periodo.
 * - Tasa de selección: seleccionados sobre las postulaciones cerradas del periodo.
 * - Respuesta de las empresas: mediana de horas hasta el primer cambio de estado.
 */
class CalcularIndicadores
{
    public const int DIAS_ACTIVACION = 7;

    public const int HABILIDADES_ACTIVACION = 3;

    public const int DIAS_EMPRESA_ACTIVA = 90;

    /**
     * @return array{
     *     usuarios_registrados: int,
     *     usuarios_activos: int,
     *     activacion: ?float,
     *     empresas_aprobadas: int,
     *     empresas_activas: int,
     *     vacantes_creadas: int,
     *     postulaciones: int,
     *     postulaciones_por_vacante: ?float,
     *     tasa_seleccion: ?float,
     *     respuesta_empresas_horas: ?float,
     *     postulaciones_por_mes: array<string, int>
     * }
     */
    public function handle(string $universidadId, CarbonImmutable $desde, CarbonImmutable $hasta, ?string $carreraId = null): array
    {
        $desde = $desde->startOfDay();
        $hasta = $hasta->endOfDay();

        return Cache::remember(
            'kpis:'.hash('sha256', implode('|', [$universidadId, $desde->toIso8601String(), $hasta->toIso8601String(), $carreraId])),
            now()->addMinutes(10),
            fn (): array => $this->calcular($universidadId, $desde, $hasta, $carreraId),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calcular(string $universidadId, CarbonImmutable $desde, CarbonImmutable $hasta, ?string $carreraId): array
    {
        $postulaciones = $this->postulaciones($universidadId, $carreraId)
            ->whereBetween('created_at', [$desde, $hasta])
            ->get(['id', 'vacante_id', 'usuario_id', 'estado', 'created_at']);

        $vacantesDelPeriodo = $this->vacantes($universidadId, $carreraId)->whereBetween('created_at', [$desde, $hasta]);

        $cerradas = $postulaciones->whereIn('estado', [EstadoPostulacion::Seleccionado, EstadoPostulacion::NoSeleccionado]);

        return [
            'usuarios_registrados' => $this->candidatos($universidadId, $carreraId)
                ->whereNotNull('email_verified_at')
                ->where('email_verified_at', '<=', $hasta)
                ->count(),
            'usuarios_activos' => $postulaciones->pluck('usuario_id')->unique()->count()
                + (clone $vacantesDelPeriodo)->distinct()->count('empresa_id'),
            'activacion' => $this->activacion($universidadId, $desde, $hasta, $carreraId),
            'empresas_aprobadas' => $this->empresas($universidadId)
                ->where('estado_aprobacion', EstadoAprobacionEmpresa::Aprobada)
                ->where('aprobada_en', '<=', $hasta)
                ->count(),
            'empresas_activas' => $this->vacantes($universidadId, $carreraId)
                ->whereBetween('created_at', [$hasta->subDays(self::DIAS_EMPRESA_ACTIVA), $hasta])
                ->distinct()
                ->count('empresa_id'),
            'vacantes_creadas' => (clone $vacantesDelPeriodo)->count(),
            'postulaciones' => $postulaciones->count(),
            'postulaciones_por_vacante' => $postulaciones->isEmpty()
                ? null
                : round($postulaciones->count() / $postulaciones->pluck('vacante_id')->unique()->count(), 1),
            'tasa_seleccion' => $cerradas->isEmpty()
                ? null
                : round($cerradas->where('estado', EstadoPostulacion::Seleccionado)->count() / $cerradas->count() * 100, 1),
            'respuesta_empresas_horas' => $this->medianaDeRespuesta($postulaciones),
            'postulaciones_por_mes' => $this->porMes($postulaciones, $desde, $hasta),
        ];
    }

    /**
     * Estudiantes y egresados de la universidad (y de la carrera, si se filtra).
     *
     * @return Builder<User>
     */
    private function candidatos(string $universidadId, ?string $carreraId): Builder
    {
        return User::query()
            ->withoutGlobalScopes()
            ->where('universidad_id', $universidadId)
            ->whereIn('rol', [Rol::Estudiante, Rol::Egresado])
            ->when($carreraId, fn (Builder $consulta) => $consulta->whereHas('perfil', fn (Builder $perfiles) => $perfiles->where('carrera_id', $carreraId)));
    }

    /**
     * @return Builder<Postulacion>
     */
    private function postulaciones(string $universidadId, ?string $carreraId): Builder
    {
        return Postulacion::query()->whereIn('usuario_id', $this->candidatos($universidadId, $carreraId)->select('id'));
    }

    /**
     * @return Builder<Empresa>
     */
    private function empresas(string $universidadId): Builder
    {
        return Empresa::query()->whereIn(
            'usuario_id',
            User::query()->withoutGlobalScopes()->where('universidad_id', $universidadId)->select('id'),
        );
    }

    /**
     * Vacantes de las empresas de la universidad (dirigidas a la carrera, si se filtra).
     *
     * @return Builder<Vacante>
     */
    private function vacantes(string $universidadId, ?string $carreraId): Builder
    {
        return Vacante::query()
            ->whereIn('empresa_id', $this->empresas($universidadId)->select('id'))
            ->when($carreraId, fn (Builder $consulta) => $consulta->whereHas(
                'carreras',
                fn (Builder $carreras) => $carreras->withoutGlobalScope(UniversidadScope::class)->whereKey($carreraId),
            ));
    }

    private function activacion(string $universidadId, CarbonImmutable $desde, CarbonImmutable $hasta, ?string $carreraId): ?float
    {
        $registrados = $this->candidatos($universidadId, $carreraId)
            ->whereBetween('created_at', [$desde, $hasta])
            ->with('perfil.habilidades')
            ->get(['id', 'created_at']);

        if ($registrados->isEmpty()) {
            return null;
        }

        $activados = $registrados->filter(function (User $usuario): bool {
            $limite = $usuario->created_at->addDays(self::DIAS_ACTIVACION);
            $perfil = $usuario->perfil;

            return $perfil !== null
                && $perfil->created_at->lessThanOrEqualTo($limite)
                && $perfil->habilidades->filter(fn ($habilidad) => $habilidad->pivot->created_at?->lessThanOrEqualTo($limite))->count() >= self::HABILIDADES_ACTIVACION;
        });

        return round($activados->count() / $registrados->count() * 100, 1);
    }

    /**
     * @param  Collection<int, Postulacion>  $postulaciones
     */
    private function medianaDeRespuesta(Collection $postulaciones): ?float
    {
        $primerCambio = HistorialPostulacion::query()
            ->whereIn('postulacion_id', $postulaciones->modelKeys())
            ->where('estado', '!=', EstadoPostulacion::Recibida)
            ->selectRaw('postulacion_id, min(cambiado_en) as primer_cambio')
            ->groupBy('postulacion_id')
            ->pluck('primer_cambio', 'postulacion_id');

        $horas = $postulaciones
            ->filter(fn (Postulacion $postulacion): bool => $primerCambio->has($postulacion->id))
            ->map(fn (Postulacion $postulacion): float => $postulacion->created_at->diffInHours(CarbonImmutable::parse($primerCambio[$postulacion->id])))
            ->sort()
            ->values();

        if ($horas->isEmpty()) {
            return null;
        }

        return round($horas->median(), 1);
    }

    /**
     * Postulaciones enviadas en cada mes del periodo, incluidos los meses sin
     * postulaciones (métrica estrella del Release 1).
     *
     * @param  Collection<int, Postulacion>  $postulaciones
     * @return array<string, int>
     */
    private function porMes(Collection $postulaciones, CarbonImmutable $desde, CarbonImmutable $hasta): array
    {
        $conteo = $postulaciones->countBy(fn (Postulacion $postulacion): string => $postulacion->created_at->format('Y-m'));

        return collect(CarbonPeriod::create($desde->startOfMonth(), '1 month', $hasta->startOfMonth()))
            ->mapWithKeys(fn ($mes): array => [$mes->format('Y-m') => $conteo->get($mes->format('Y-m'), 0)])
            ->all();
    }
}
