<?php

use App\Models\Catalogos\Carrera;
use App\Models\Vacantes\Vacante;

it('counts as open only while active and before its closing date', function () {
    $abierta = Vacante::factory()->create();
    $cierraHoy = Vacante::factory()->create(['fecha_cierre' => today()]);
    Vacante::factory()->pausada()->create();
    Vacante::factory()->cerrada()->create();
    Vacante::factory()->vencida()->create();

    expect(Vacante::query()->abiertas()->pluck('id')->all())
        ->toEqualCanonicalizing([$abierta->id, $cierraHoy->id])
        ->and($abierta->estaAbierta())->toBeTrue()
        ->and(Vacante::factory()->vencida()->make()->estaAbierta())->toBeFalse();
});

it('keeps its target programs', function () {
    $carreras = Carrera::factory()->count(2)->create();
    $vacante = Vacante::factory()->hasAttached($carreras)->create();

    expect($vacante->carreras()->pluck('carreras.id')->all())->toEqualCanonicalizing($carreras->pluck('id')->all());
});
