<?php

use App\Enums\Autenticacion\Rol;
use App\Models\Autenticacion\Universidad;
use App\Models\Perfiles\Perfil;
use App\Models\User;

it('returns 401 when there is no session', function () {
    $this->getJson(route('api.v1.me'))
        ->assertUnauthorized();
});

it('returns the authenticated user with role, status and university without sensitive fields', function () {
    $upc = Universidad::factory()->create(['nombre' => 'Universidad Popular del Cesar']);
    $user = User::factory()->for($upc)->rol(Rol::Egresado)->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.me'))
        ->assertOk()
        ->assertExactJsonStructure(['data' => [
            'id', 'name', 'email', 'email_verified_at', 'rol', 'estado', 'tiene_perfil', 'universidad' => ['id', 'nombre'],
        ]])
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.rol', 'EGRESADO')
        ->assertJsonPath('data.estado', 'ACTIVO')
        ->assertJsonPath('data.tiene_perfil', false)
        ->assertJsonPath('data.universidad.nombre', 'Universidad Popular del Cesar');
});

it('reports that the user has a profile once it exists', function () {
    $perfil = Perfil::factory()->create();

    $this->actingAs($perfil->usuario)
        ->getJson(route('api.v1.me'))
        ->assertJsonPath('data.tiene_perfil', true);
});
