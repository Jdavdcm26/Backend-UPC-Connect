<?php

namespace App\Filament\Pages;

use App\Models\Catalogos\Carrera;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;

/**
 * Panel de KPIs de la administración (HU-58), filtrable por periodo y carrera.
 * Los filtros se aplican al confirmar, así que llegan validados.
 */
class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    public const int DIAS_POR_DEFECTO = 30;

    protected static ?string $title = 'Panel de KPIs';

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->label('Filtrar')
                ->schema([
                    DatePicker::make('desde')
                        ->label('Desde')
                        ->default(today()->subDays(self::DIAS_POR_DEFECTO - 1))
                        ->maxDate(today())
                        ->required(),
                    DatePicker::make('hasta')
                        ->label('Hasta')
                        ->default(today())
                        ->afterOrEqual('desde')
                        ->required(),
                    Select::make('carrera_id')
                        ->label('Carrera')
                        ->placeholder('Todas las carreras')
                        ->options(fn (): array => Carrera::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                        ->searchable(),
                ]),
        ];
    }

    /**
     * Filtros aplicados, o los últimos 30 días y todas las carreras si aún
     * no se ha filtrado.
     *
     * @param  array<string, mixed>|null  $filtros
     * @return array{desde: CarbonImmutable, hasta: CarbonImmutable, carrera_id: ?string}
     */
    public static function filtrosAplicados(?array $filtros): array
    {
        return [
            'desde' => CarbonImmutable::parse($filtros['desde'] ?? today()->subDays(self::DIAS_POR_DEFECTO - 1)),
            'hasta' => CarbonImmutable::parse($filtros['hasta'] ?? today()),
            'carrera_id' => $filtros['carrera_id'] ?? null,
        ];
    }
}
