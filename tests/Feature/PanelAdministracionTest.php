<?php

use App\Enums\Autenticacion\Rol;
use App\Models\User;

it('renders the admin panel login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('UPC Connect')
        ->assertSee('Entre a su cuenta');
});

it('redirects guests to the admin panel login', function () {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

it('lets an active administrator into the panel', function () {
    $this->actingAs(User::factory()->rol(Rol::Admin)->create())
        ->get('/admin')
        ->assertOk();
});

it('returns 403 to accounts that are not active administrators', function (Closure $usuario) {
    $this->actingAs($usuario())
        ->get('/admin')
        ->assertForbidden();
})->with([
    'estudiante' => [fn () => User::factory()->create()],
    'empresa' => [fn () => User::factory()->rol(Rol::Empresa)->create()],
    'moderador' => [fn () => User::factory()->rol(Rol::Moderador)->create()],
    'administrador suspendido' => [fn () => User::factory()->rol(Rol::Admin)->suspendido()->create()],
]);
