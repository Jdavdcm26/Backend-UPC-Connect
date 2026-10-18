<?php

namespace App\Filament\Widgets;

use App\Actions\Analitica\CalcularIndicadores;
use App\Filament\Pages\Dashboard;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Métrica estrella del Release 1: postulaciones enviadas por mes.
 */
class PostulacionesPorMes extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Postulaciones enviadas por mes';

    protected ?string $description = 'Métrica estrella del Release 1: oportunidades iniciadas.';

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $filtros = Dashboard::filtrosAplicados($this->pageFilters);
        $porMes = app(CalcularIndicadores::class)->handle(auth()->user()->universidad_id, $filtros['desde'], $filtros['hasta'], $filtros['carrera_id'])['postulaciones_por_mes'];

        return [
            'datasets' => [
                ['label' => 'Postulaciones', 'data' => array_values($porMes)],
            ],
            'labels' => array_keys($porMes),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
