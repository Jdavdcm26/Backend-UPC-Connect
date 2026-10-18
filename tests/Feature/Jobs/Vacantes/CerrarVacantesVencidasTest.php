<?php

use App\Enums\Vacantes\EstadoVacante;
use App\Jobs\Vacantes\CerrarVacantesVencidas;
use App\Models\Vacantes\Vacante;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;

it('closes active and paused vacancies whose closing date already passed', function () {
    $activaVencida = Vacante::factory()->vencida()->create();
    $pausadaVencida = Vacante::factory()->pausada()->vencida()->create();

    (new CerrarVacantesVencidas)->handle();

    expect($activaVencida->fresh()->estado)->toBe(EstadoVacante::Cerrada)
        ->and($pausadaVencida->fresh()->estado)->toBe(EstadoVacante::Cerrada);
});

it('leaves vacancies that close today or later, and already closed ones, untouched', function () {
    $cierraHoy = Vacante::factory()->create(['fecha_cierre' => today()]);
    $futura = Vacante::factory()->pausada()->create();
    $cerrada = Vacante::factory()->cerrada()->vencida()->create(['updated_at' => now()->subWeek()]);

    (new CerrarVacantesVencidas)->handle();

    expect($cierraHoy->fresh()->estado)->toBe(EstadoVacante::Activa)
        ->and($futura->fresh()->estado)->toBe(EstadoVacante::Pausada)
        ->and($cerrada->fresh()->updated_at->toDateString())->toBe(now()->subWeek()->toDateString());
});

it('is scheduled daily shortly after midnight in Colombia', function () {
    $evento = collect(app(Schedule::class)->events())
        ->first(fn (Event $evento): bool => $evento->description === 'cerrar-vacantes-vencidas');

    expect($evento)->not->toBeNull()
        ->and($evento->expression)->toBe('5 0 * * *')
        ->and($evento->timezone)->toBe('America/Bogota');
});

it('keeps a vacancy that closes today open until midnight in Colombia', function () {
    $this->travelTo(Carbon::parse('2026-11-10 23:30', 'America/Bogota'));

    $vacante = Vacante::factory()->create(['fecha_cierre' => '2026-11-10']);

    expect($vacante->estaAbierta())->toBeTrue()
        ->and(Vacante::query()->abiertas()->whereKey($vacante->id)->exists())->toBeTrue();
});
