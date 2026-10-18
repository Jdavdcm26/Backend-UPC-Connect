<?php

use App\Enums\Autenticacion\EstadoUsuario;
use App\Models\User;

it('logs out an account suspended while its session was open', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario)->getJson(route('api.v1.me'))->assertOk();

    $usuario->forceFill(['estado' => EstadoUsuario::Suspendido])->save();

    $this->actingAs($usuario)
        ->getJson(route('api.v1.me'))
        ->assertUnauthorized()
        ->assertJsonPath('codigo', 'cuenta_suspendida');

    $this->assertGuest('web');
});

it('logs out an account that is no longer active with its own code', function () {
    $usuario = User::factory()->unverified()->create();

    $this->actingAs($usuario)
        ->getJson(route('api.v1.me'))
        ->assertUnauthorized()
        ->assertJsonPath('codigo', 'cuenta_no_activa');

    $this->assertGuest('web');
});

it('applies to every authenticated endpoint', function () {
    $this->actingAs(User::factory()->suspendido()->create())
        ->getJson(route('api.v1.carreras.index'))
        ->assertUnauthorized();
});
