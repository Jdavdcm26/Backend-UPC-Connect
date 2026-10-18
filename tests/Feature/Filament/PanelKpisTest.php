<?php

use App\Enums\Autenticacion\Rol;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\IndicadoresKpi;
use App\Filament\Widgets\PostulacionesPorMes;
use App\Models\Autenticacion\Universidad;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 15));
    $this->upc = Universidad::factory()->create();
    $this->admin = User::factory()->for($this->upc)->rol(Rol::Admin)->conDobleFactor()->create();
});

it('shows the KPI dashboard to the administrator', function () {
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Panel de KPIs')
        ->assertSee('Filtrar');
});

it('shows the indicators of the filtered period in Spanish', function () {
    Postulacion::factory()->de(User::factory()->for($this->upc)->create())->create();
    $this->actingAs($this->admin);

    Livewire::test(IndicadoresKpi::class, ['pageFilters' => ['desde' => '2026-10-01', 'hasta' => '2026-10-15']])
        ->assertSee('Usuarios registrados')
        ->assertSee('Postulaciones')
        ->assertSee('Tasa de selección')
        ->assertSee('Sin datos');
});

it('charts the applications sent per month of the period', function () {
    Postulacion::factory()->de(User::factory()->for($this->upc)->create())->create();
    $this->actingAs($this->admin);

    $grafica = Livewire::test(PostulacionesPorMes::class, ['pageFilters' => ['desde' => '2026-09-01', 'hasta' => '2026-10-15']]);

    $widget = $grafica->instance();
    $datos = Closure::bind(fn (): array => $this->getData(), $widget, $widget::class)();

    expect($datos)
        ->toBe(['datasets' => [['label' => 'Postulaciones', 'data' => [0, 1]]], 'labels' => ['2026-09', '2026-10']]);
});

it('uses the last 30 days and every program until the administrator filters', function () {
    expect(Dashboard::filtrosAplicados([]))
        ->desde->toDateString()->toBe('2026-09-16')
        ->hasta->toDateString()->toBe('2026-10-15')
        ->carrera_id->toBeNull();
});
