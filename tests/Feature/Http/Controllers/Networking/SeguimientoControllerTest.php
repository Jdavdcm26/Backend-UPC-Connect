<?php

use App\Enums\Autenticacion\Rol;
use App\Models\Autenticacion\Universidad;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->upc = Universidad::factory()->create();
    $this->yo = candidatoDe($this->upc);
    $this->companera = candidatoDe($this->upc, Rol::Egresado);
});

function seguir(User $usuario, User|string $seguido): TestResponse
{
    $id = $seguido instanceof User ? $seguido->id : $seguido;

    return test()->actingAs($usuario)->postJson("/api/v1/usuarios/{$id}/seguimiento");
}

it('follows a person and returns their updated follower count, also when repeated', function () {
    seguir($this->yo, $this->companera)->assertOk()->assertJson(['data' => ['siguiendo' => true, 'seguidores' => 1]]);
    seguir($this->yo, $this->companera)->assertOk()->assertJson(['data' => ['siguiendo' => true, 'seguidores' => 1]]);

    expect($this->yo->seguidos()->pluck('users.id')->all())->toBe([$this->companera->id]);
});

it('unfollows a person', function () {
    $this->yo->seguidos()->attach($this->companera);

    $this->actingAs($this->yo)
        ->deleteJson(route('api.v1.usuarios.seguimiento.destroy', $this->companera))
        ->assertOk()
        ->assertJson(['data' => ['siguiendo' => false, 'seguidores' => 0]]);
});

it('returns 403 with the reason when the follow is not allowed', function (Closure $seguidor, Closure $seguido, string $motivo) {
    [$quien, $aQuien] = [$seguidor($this), $seguido($this)];

    seguir($quien, $aQuien)
        ->assertForbidden()
        ->assertJsonPath('message', $motivo);
})->with([
    'a sí mismo' => [fn ($prueba) => $prueba->yo, fn ($prueba) => $prueba->yo, 'No puedes seguirte a ti mismo.'],
    'una empresa sigue' => [
        fn ($prueba) => Empresa::factory()->create(['usuario_id' => User::factory()->rol(Rol::Empresa)->for($prueba->upc)])->usuario,
        fn ($prueba) => $prueba->yo,
        'Solo estudiantes y egresados pueden seguir a otras personas.',
    ],
    'seguir a una empresa' => [
        fn ($prueba) => $prueba->yo,
        fn ($prueba) => Empresa::factory()->create(['usuario_id' => User::factory()->rol(Rol::Empresa)->for($prueba->upc)])->usuario,
        'Solo puedes seguir a estudiantes y egresados.',
    ],
    'con un bloqueo' => [
        fn ($prueba) => $prueba->yo,
        function ($prueba) {
            $prueba->companera->bloqueados()->attach($prueba->yo);

            return $prueba->companera;
        },
        'No puedes seguir a esta persona.',
    ],
]);

it('returns 404 for people of other universities', function () {
    seguir($this->yo, candidatoDe(Universidad::factory()->create()))->assertNotFound();
});
