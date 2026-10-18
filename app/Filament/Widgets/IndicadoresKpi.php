<?php

namespace App\Filament\Widgets;

use App\Actions\Analitica\CalcularIndicadores;
use App\Filament\Pages\Dashboard;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Indicadores de uso y empleabilidad del periodo filtrado (HU-58).
 */
class IndicadoresKpi extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Indicadores del periodo';

    protected ?string $description = 'Definiciones provisionales del Release 1, pendientes de aprobación del docente tutor.';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $filtros = Dashboard::filtrosAplicados($this->pageFilters);
        $kpi = app(CalcularIndicadores::class)->handle(auth()->user()->universidad_id, $filtros['desde'], $filtros['hasta'], $filtros['carrera_id']);

        return [
            Stat::make('Usuarios registrados', $this->numero($kpi['usuarios_registrados']))
                ->description('Estudiantes y egresados con el correo verificado'),
            Stat::make('Usuarios activos', $this->numero($kpi['usuarios_activos']))
                ->description('Se postularon o publicaron una vacante en el periodo'),
            Stat::make('Activación', $this->porcentaje($kpi['activacion']))
                ->description('Perfil y 3 habilidades en sus primeros 7 días'),
            Stat::make('Empresas aprobadas', $this->numero($kpi['empresas_aprobadas']))
                ->description("{$this->numero($kpi['empresas_activas'])} activas en los últimos 90 días"),
            Stat::make('Vacantes creadas', $this->numero($kpi['vacantes_creadas'])),
            Stat::make('Postulaciones', $this->numero($kpi['postulaciones']))
                ->description('Promedio por vacante: '.$this->decimal($kpi['postulaciones_por_vacante'])),
            Stat::make('Tasa de selección', $this->porcentaje($kpi['tasa_seleccion']))
                ->description('Seleccionados sobre postulaciones cerradas'),
            Stat::make('Respuesta de las empresas', $kpi['respuesta_empresas_horas'] === null ? 'Sin datos' : $this->decimal($kpi['respuesta_empresas_horas']).' h')
                ->description('Mediana hasta el primer cambio de estado'),
        ];
    }

    private function numero(int $valor): string
    {
        return number_format($valor, 0, ',', '.');
    }

    private function decimal(?float $valor): string
    {
        return $valor === null ? 'Sin datos' : number_format($valor, 1, ',', '.');
    }

    private function porcentaje(?float $valor): string
    {
        return $valor === null ? 'Sin datos' : $this->decimal($valor).' %';
    }
}
