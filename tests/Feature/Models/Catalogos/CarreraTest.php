<?php

use App\Models\Autenticacion\Universidad;
use App\Models\Catalogos\Carrera;
use App\Models\User;

it('limits programs to the university of the authenticated user', function () {
    $upc = Universidad::factory()->create();
    $propia = Carrera::factory()->for($upc)->create();
    Carrera::factory()->create();

    $this->actingAs(User::factory()->for($upc)->create());

    expect(Carrera::query()->pluck('id')->all())->toBe([$propia->id]);
});

it('excludes inactive programs from the active scope', function () {
    $activa = Carrera::factory()->create();
    Carrera::factory()->inactiva()->create();

    expect(Carrera::query()->activas()->pluck('id')->all())->toBe([$activa->id]);
});
