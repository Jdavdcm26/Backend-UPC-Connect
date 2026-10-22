<?php

use App\Enums\Autenticacion\Rol;
use App\Models\Autenticacion\Universidad;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->upc = Universidad::factory()->create();
    $this->yo = candidatoDe($this->upc);
    $this->companera = candidatoDe($this->upc);
});

function bloquear(User $usuario, User $bloqueado): TestResponse
{
    return test()->actingAs($usuario)->postJson(route('api.v1.usuarios.bloqueo.store', $bloqueado));
}

it('blocks a person and undoes the follow in both directions, also when repeated', function () {
    $this->yo->seguidos()->attach($this->companera);
    $this->companera->seguidos()->attach($this->yo);

    bloquear($this->yo, $this->companera)->assertNoContent();
    bloquear($this->yo, $this->companera)->assertNoContent();

    expect($this->yo->bloqueados()->pluck('users.id')->all())->toBe([$this->companera->id])
        ->and($this->yo->seguidos()->exists())->toBeFalse()
        ->and($this->companera->seguidos()->exists())->toBeFalse();
});

it('lists the blocked accounts with what is needed to unblock them', function () {
    $this->yo->bloqueados()->attach($this->companera);

    $this->actingAs($this->yo)
        ->getJson(route('api.v1.me.bloqueados.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $this->companera->id)
        ->assertJsonPath('data.0.nombre', "{$this->companera->perfil->nombres} {$this->companera->perfil->apellidos}")
        ->assertJsonPath('data.0.slug', $this->companera->perfil->slug)
        ->assertJsonStructure(['data' => [['bloqueado_en']]]);
});

it('unblocks a person', function () {
    $this->yo->bloqueados()->attach($this->companera);

    $this->actingAs($this->yo)
        ->deleteJson(route('api.v1.usuarios.bloqueo.destroy', $this->companera))
        ->assertNoContent();

    expect($this->yo->bloqueados()->exists())->toBeFalse();
});

it('lets a company block a candidate', function () {
    $empresa = Empresa::factory()->create(['usuario_id' => User::factory()->rol(Rol::Empresa)->for($this->upc)]);

    bloquear($empresa->usuario, $this->yo)->assertNoContent();
});

it('does not allow blocking oneself or an administrator', function () {
    bloquear($this->yo, $this->yo)
        ->assertForbidden()
        ->assertJsonPath('message', 'No puedes bloquearte a ti mismo.');

    bloquear($this->yo, User::factory()->rol(Rol::Admin)->for($this->upc)->create())
        ->assertForbidden()
        ->assertJsonPath('message', 'No puedes bloquear cuentas de la administración.');
});

it('hides the blocker profile from the blocked person, but not the other way around', function () {
    $this->companera->bloqueados()->attach($this->yo);

    $this->actingAs($this->yo)->getJson("/api/v1/perfiles/{$this->companera->perfil->slug}")->assertNotFound();
    $this->actingAs($this->companera)->getJson("/api/v1/perfiles/{$this->yo->perfil->slug}")->assertOk();
});
