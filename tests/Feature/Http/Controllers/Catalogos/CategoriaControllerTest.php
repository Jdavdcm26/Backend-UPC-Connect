<?php

use App\Models\Catalogos\Categoria;
use App\Models\User;

beforeEach(function () {
    $this->usuario = User::factory()->create();
});

it('returns 401 when there is no session', function () {
    $this->getJson(route('api.v1.categorias.index'))->assertUnauthorized();
});

it('lists only the active categories ordered by name ignoring accents', function () {
    Categoria::factory()->create(['nombre' => 'Servicio o asesoría']);
    Categoria::factory()->create(['nombre' => 'Búsqueda de integrantes']);
    Categoria::factory()->create(['nombre' => 'General']);
    Categoria::factory()->inactiva()->create(['nombre' => 'Archivada']);

    $this->actingAs($this->usuario)
        ->getJson(route('api.v1.categorias.index'))
        ->assertOk()
        ->assertExactJsonStructure(['data' => ['*' => ['id', 'nombre']]])
        ->assertJsonPath('data.*.nombre', ['Búsqueda de integrantes', 'General', 'Servicio o asesoría']);
});

it('serves the list from cache until a category changes', function () {
    $categoria = Categoria::factory()->create(['nombre' => 'General']);

    $this->actingAs($this->usuario)->getJson(route('api.v1.categorias.index'))->assertJsonCount(1, 'data');

    Categoria::query()->whereKey($categoria->id)->toBase()->delete();

    $this->actingAs($this->usuario)->getJson(route('api.v1.categorias.index'))->assertJsonCount(1, 'data');

    Categoria::factory()->create(['nombre' => 'Logro']);

    $this->actingAs($this->usuario)
        ->getJson(route('api.v1.categorias.index'))
        ->assertJsonPath('data.*.nombre', ['Logro']);
});

it('stops listing a category as soon as it is deactivated', function () {
    $categoria = Categoria::factory()->create();

    $this->actingAs($this->usuario)->getJson(route('api.v1.categorias.index'))->assertJsonCount(1, 'data');

    $categoria->update(['activa' => false]);

    $this->actingAs($this->usuario)->getJson(route('api.v1.categorias.index'))->assertJsonCount(0, 'data');
});
