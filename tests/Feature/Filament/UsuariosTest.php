<?php

use App\Enums\Autenticacion\EstadoUsuario;
use App\Enums\Autenticacion\Rol;
use App\Filament\Resources\Usuarios\Pages\ManageUsuarios;
use App\Models\Administracion\Auditoria;
use App\Models\Autenticacion\Universidad;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->upc = Universidad::factory()->create();
    $this->admin = User::factory()->for($this->upc)->rol(Rol::Admin)->conDobleFactor()->create();
    $this->actingAs($this->admin);
});

it('lists only the accounts of the administrator university and searches by email', function () {
    $estudiante = User::factory()->for($this->upc)->create(['email' => 'ana@unicesar.edu.co']);
    $otro = User::factory()->for($this->upc)->create(['email' => 'luis@unicesar.edu.co']);
    $ajeno = User::factory()->create();

    Livewire::test(ManageUsuarios::class)
        ->assertCanSeeTableRecords([$estudiante, $otro])
        ->assertCanNotSeeTableRecords([$ajeno])
        ->searchTable('ana@')
        ->assertCanSeeTableRecords([$estudiante])
        ->assertCanNotSeeTableRecords([$otro]);
});

it('suspends an account with a reason and audits it', function () {
    $estudiante = User::factory()->for($this->upc)->create();

    Livewire::test(ManageUsuarios::class)
        ->callAction(TestAction::make('suspender')->table($estudiante), ['motivo' => 'Publicó contenido ofensivo.'])
        ->assertHasNoActionErrors();

    expect($estudiante->fresh()->estado)->toBe(EstadoUsuario::Suspendido)
        ->and(Auditoria::query()->where('accion', 'usuario.suspendido')->sole())
        ->actor_id->toBe($this->admin->id)
        ->entidad_id->toBe($estudiante->id)
        ->detalle->toBe(['motivo' => 'Publicó contenido ofensivo.', 'estado_anterior' => 'ACTIVO']);
});

it('requires a reason to suspend an account', function () {
    $estudiante = User::factory()->for($this->upc)->create();

    Livewire::test(ManageUsuarios::class)
        ->callAction(TestAction::make('suspender')->table($estudiante), ['motivo' => ''])
        ->assertHasActionErrors(['motivo' => 'required']);

    expect($estudiante->fresh()->estado)->toBe(EstadoUsuario::Activo);
});

it('does not let the administrator suspend their own account', function () {
    Livewire::test(ManageUsuarios::class)
        ->assertActionHidden(TestAction::make('suspender')->table($this->admin));
});

it('reactivates a suspended account according to its email verification', function (Closure $cuenta, EstadoUsuario $estadoEsperado) {
    $usuario = $cuenta($this->upc);

    Livewire::test(ManageUsuarios::class)
        ->callAction(TestAction::make('reactivar')->table($usuario))
        ->assertHasNoActionErrors();

    expect($usuario->fresh()->estado)->toBe($estadoEsperado)
        ->and(Auditoria::query()->where('accion', 'usuario.reactivado')->sole()->detalle)
        ->toBe(['estado_nuevo' => $estadoEsperado->value]);
})->with([
    'correo verificado' => [fn (Universidad $upc) => User::factory()->for($upc)->suspendido()->create(), EstadoUsuario::Activo],
    'correo sin verificar' => [fn (Universidad $upc) => User::factory()->for($upc)->unverified()->suspendido()->create(), EstadoUsuario::Pendiente],
]);

it('offers reactivation only for suspended accounts', function () {
    $activo = User::factory()->for($this->upc)->create();

    Livewire::test(ManageUsuarios::class)
        ->assertActionHidden(TestAction::make('reactivar')->table($activo));
});

it('returns 403 on the users page to accounts that are not administrators', function () {
    $this->actingAs(User::factory()->for($this->upc)->create())
        ->get('/admin/usuarios')
        ->assertForbidden();
});
