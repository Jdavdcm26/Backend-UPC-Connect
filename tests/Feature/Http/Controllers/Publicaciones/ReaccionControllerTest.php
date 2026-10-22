<?php

use App\Enums\Autenticacion\Rol;
use App\Models\Autenticacion\Universidad;
use App\Models\Publicaciones\Publicacion;
use App\Models\Publicaciones\Reaccion;
use App\Models\User;
use App\Models\Vacantes\Empresa;

beforeEach(function () {
    $this->upc = Universidad::factory()->create();
    $this->yo = candidatoDe($this->upc);
    $this->publicacion = Publicacion::factory()->de(candidatoDe($this->upc))->create();
});

it('adds the reaction and returns the updated counter, also when repeated', function () {
    foreach ([1, 2] as $vez) {
        $this->actingAs($this->yo)
            ->postJson(route('api.v1.publicaciones.reaccion.store', $this->publicacion))
            ->assertOk()
            ->assertJson(['data' => ['reacciones' => 1, 'reaccione' => true]]);
    }

    $this->actingAs($this->yo)
        ->getJson(route('api.v1.publicaciones.show', $this->publicacion))
        ->assertJsonPath('data.reacciones', 1)
        ->assertJsonPath('data.reaccione', true);
});

it('removes the reaction and the counter goes down', function () {
    Reaccion::query()->insert(['publicacion_id' => $this->publicacion->id, 'usuario_id' => $this->yo->id]);
    Reaccion::query()->insert(['publicacion_id' => $this->publicacion->id, 'usuario_id' => candidatoDe($this->upc)->id]);

    $this->actingAs($this->yo)
        ->deleteJson(route('api.v1.publicaciones.reaccion.destroy', $this->publicacion))
        ->assertOk()
        ->assertJson(['data' => ['reacciones' => 1, 'reaccione' => false]]);
});

it('returns 403 to companies', function () {
    $empresa = Empresa::factory()->create(['usuario_id' => User::factory()->rol(Rol::Empresa)->for($this->upc)]);

    $this->actingAs($empresa->usuario)
        ->postJson(route('api.v1.publicaciones.reaccion.store', $this->publicacion))
        ->assertForbidden()
        ->assertJsonPath('message', 'Solo estudiantes y egresados pueden reaccionar a las publicaciones.');
});

it('returns 404 for posts the user cannot see', function () {
    $oculta = Publicacion::factory()->de(candidatoDe($this->upc))->oculta()->create();

    $this->actingAs($this->yo)
        ->postJson(route('api.v1.publicaciones.reaccion.store', $oculta))
        ->assertNotFound();

    expect(Reaccion::query()->exists())->toBeFalse();
});
