<?php

use App\Models\Autenticacion\Universidad;
use App\Models\Catalogos\Carrera;
use App\Models\User;

beforeEach(function () {
    $this->upc = Universidad::factory()->create();
    $this->usuario = User::factory()->for($this->upc)->create();
});

it('returns 401 when there is no session', function () {
    $this->getJson(route('api.v1.carreras.index'))->assertUnauthorized();
});

it('lists only the active programs of the user university ordered by faculty and name', function () {
    Carrera::factory()->for($this->upc)->create(['facultad' => 'Salud', 'nombre' => 'Enfermería']);
    Carrera::factory()->for($this->upc)->create(['facultad' => 'Ingenierías', 'nombre' => 'Ingeniería de Sistemas']);
    Carrera::factory()->for($this->upc)->create(['facultad' => 'Ingenierías', 'nombre' => 'Ingeniería Electrónica']);
    Carrera::factory()->for($this->upc)->inactiva()->create(['nombre' => 'Programa cerrado']);
    Carrera::factory()->create(['nombre' => 'Programa de otra universidad']);

    $this->actingAs($this->usuario)
        ->getJson(route('api.v1.carreras.index'))
        ->assertOk()
        ->assertExactJsonStructure(['data' => ['*' => ['id', 'nombre', 'facultad', 'nivel']]])
        ->assertJsonPath('data.*.nombre', ['Ingeniería de Sistemas', 'Ingeniería Electrónica', 'Enfermería'])
        ->assertJsonPath('data.0.nivel', 'PREGRADO');
});

it('serves the list from cache until a program changes', function () {
    $carrera = Carrera::factory()->for($this->upc)->create(['nombre' => 'Derecho']);

    $this->actingAs($this->usuario)->getJson(route('api.v1.carreras.index'))->assertJsonCount(1, 'data');

    Carrera::query()->whereKey($carrera->id)->toBase()->delete();

    $this->actingAs($this->usuario)->getJson(route('api.v1.carreras.index'))->assertJsonCount(1, 'data');

    Carrera::factory()->for($this->upc)->create(['nombre' => 'Economía']);

    $this->actingAs($this->usuario)
        ->getJson(route('api.v1.carreras.index'))
        ->assertJsonPath('data.*.nombre', ['Economía']);
});

it('stops listing a program as soon as it is deactivated', function () {
    $carrera = Carrera::factory()->for($this->upc)->create();

    $this->actingAs($this->usuario)->getJson(route('api.v1.carreras.index'))->assertJsonCount(1, 'data');

    $carrera->update(['activa' => false]);

    $this->actingAs($this->usuario)->getJson(route('api.v1.carreras.index'))->assertJsonCount(0, 'data');
});
