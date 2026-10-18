<?php

use App\Enums\Autenticacion\EstadoUsuario;
use App\Enums\Autenticacion\Rol;
use App\Enums\Vacantes\EstadoAprobacionEmpresa;
use App\Filament\Resources\Usuarios\Pages\ManageUsuarios;
use App\Models\Administracion\Auditoria;
use App\Models\Autenticacion\Universidad;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use App\Notifications\Administracion\InvitacionCuenta;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->upc = Universidad::factory()->create();
    $this->admin = User::factory()->for($this->upc)->rol(Rol::Admin)->conDobleFactor()->create();
    $this->actingAs($this->admin);
});

function datosDeEmpresa(array $cambios = []): array
{
    return [
        'razon_social' => 'Soluciones del Cesar S.A.S.',
        'nit' => '900.123.456-8',
        'sector' => 'Tecnología',
        'ciudad' => 'Valledupar',
        'nombre_contacto' => 'Laura Pérez',
        'email' => 'Talento@SolucionesCesar.co',
        ...$cambios,
    ];
}

it('creates an approved company with an active account and sends the invitation', function () {
    Livewire::test(ManageUsuarios::class)
        ->callAction('altaEmpresa', datosDeEmpresa())
        ->assertHasNoActionErrors()
        ->assertNotified();

    $empresa = Empresa::query()->sole();
    $cuenta = $empresa->usuario;

    expect($empresa)
        ->nit->toBe('900123456-8')
        ->estado_aprobacion->toBe(EstadoAprobacionEmpresa::Aprobada)
        ->aprobada_por->toBe($this->admin->id)
        ->and($cuenta)
        ->email->toBe('talento@solucionescesar.co')
        ->rol->toBe(Rol::Empresa)
        ->estado->toBe(EstadoUsuario::Activo)
        ->universidad_id->toBe($this->upc->id)
        ->hasVerifiedEmail()->toBeTrue()
        ->and(Auditoria::query()->where('accion', 'empresa.creada')->sole())
        ->actor_id->toBe($this->admin->id)
        ->entidad_id->toBe($empresa->id);

    Notification::assertSentTo($cuenta, InvitacionCuenta::class, function (InvitacionCuenta $invitacion) use ($cuenta): bool {
        return $invitacion->queue === 'mail'
            && str_starts_with($invitacion->toMail($cuenta)->actionUrl, config('app.frontend_url').'/restablecer-contrasena?token=');
    });
});

it('rejects a NIT with a wrong check digit', function () {
    Livewire::test(ManageUsuarios::class)
        ->callAction('altaEmpresa', datosDeEmpresa(['nit' => '900123456-7']))
        ->assertHasActionErrors(['nit']);

    expect(Empresa::query()->exists())->toBeFalse();
});

it('rejects a NIT that is already registered even when written differently', function () {
    Empresa::factory()->create(['nit' => '900123456-8']);

    Livewire::test(ManageUsuarios::class)
        ->callAction('altaEmpresa', datosDeEmpresa(['nit' => '9001234568']))
        ->assertHasActionErrors(['nit']);
});

it('rejects an email that already has an account', function () {
    User::factory()->create(['email' => 'talento@solucionescesar.co']);

    Livewire::test(ManageUsuarios::class)
        ->callAction('altaEmpresa', datosDeEmpresa())
        ->assertHasActionErrors(['email']);

    expect(Empresa::query()->exists())->toBeFalse();
});

it('creates an active graduate account and sends the invitation', function () {
    Livewire::test(ManageUsuarios::class)
        ->callAction('altaEgresado', ['nombre' => 'Carlos Ruiz', 'email' => 'carlos.ruiz@gmail.com'])
        ->assertHasNoActionErrors();

    $egresado = User::query()->where('email', 'carlos.ruiz@gmail.com')->sole();

    expect($egresado)
        ->rol->toBe(Rol::Egresado)
        ->estado->toBe(EstadoUsuario::Activo)
        ->universidad_id->toBe($this->upc->id)
        ->and(Auditoria::query()->where('accion', 'egresado.creado')->sole()->entidad_id)->toBe($egresado->id);

    Notification::assertSentTo($egresado, InvitacionCuenta::class);
});
