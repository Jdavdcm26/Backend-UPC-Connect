<?php

use App\Enums\Vacantes\EstadoVacante;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->empresa = Empresa::factory()->create();
    $this->cuenta = $this->empresa->usuario;
});

function cambiarEstado(Vacante $vacante, string $estado): TestResponse
{
    return test()->actingAs(test()->cuenta)
        ->patchJson(route('api.v1.empresa.vacantes.estado', $vacante), ['estado' => $estado]);
}

it('moves a vacancy between active, paused and closed', function (Closure $vacante, string $estado) {
    $vacante = $vacante($this->empresa);

    cambiarEstado($vacante, $estado)->assertOk()->assertJsonPath('data.estado', $estado);

    expect($vacante->fresh()->estado)->toBe(EstadoVacante::from($estado));
})->with([
    'pausar una activa' => [fn (Empresa $empresa) => Vacante::factory()->for($empresa)->create(), 'PAUSADA'],
    'reactivar una pausada' => [fn (Empresa $empresa) => Vacante::factory()->for($empresa)->pausada()->create(), 'ACTIVA'],
    'cerrar una activa' => [fn (Empresa $empresa) => Vacante::factory()->for($empresa)->create(), 'CERRADA'],
    'cerrar una pausada' => [fn (Empresa $empresa) => Vacante::factory()->for($empresa)->pausada()->create(), 'CERRADA'],
]);

it('does not reopen a closed vacancy', function () {
    $vacante = Vacante::factory()->for($this->empresa)->cerrada()->create();

    cambiarEstado($vacante, 'ACTIVA')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['estado' => 'Esta vacante está cerrada y no se puede reabrir. Publica una vacante nueva.']);

    expect($vacante->fresh()->estado)->toBe(EstadoVacante::Cerrada);
});

it('does not reactivate a vacancy whose closing date already passed', function () {
    $vacante = Vacante::factory()->for($this->empresa)->pausada()->vencida()->create();

    cambiarEstado($vacante, 'ACTIVA')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['estado' => 'La fecha de cierre ya pasó. Actualiza la fecha antes de reactivar la vacante.']);
});

it('rejects an unknown state', function () {
    cambiarEstado(Vacante::factory()->for($this->empresa)->create(), 'BORRADA')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('estado');
});

it('returns 404 for a vacancy of another company', function () {
    $ajena = Vacante::factory()->create();

    cambiarEstado($ajena, 'CERRADA')->assertNotFound();

    expect($ajena->fresh()->estado)->toBe(EstadoVacante::Activa);
});
