<?php

use App\Enums\Autenticacion\Rol;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;
use Illuminate\Support\Facades\Gate;

it('lets only an approved company publish, with a clear reason otherwise', function () {
    $aprobada = Empresa::factory()->create()->usuario;
    $pendiente = Empresa::factory()->pendiente()->create()->usuario;
    $estudiante = User::factory()->create();

    $respuestaPendiente = Gate::forUser($pendiente)->inspect('create', Vacante::class);

    expect($aprobada->can('create', Vacante::class))->toBeTrue()
        ->and($respuestaPendiente->allowed())->toBeFalse()
        ->and($respuestaPendiente->message())
        ->toBe('Tu empresa aún no está aprobada para publicar vacantes. Comunícate con la administración de la plataforma.')
        ->and($estudiante->can('create', Vacante::class))->toBeFalse();
});

it('lets the owner company edit its vacancies until they are closed, and no other company', function () {
    $vacante = Vacante::factory()->create();
    $duena = $vacante->empresa->usuario;
    $otraEmpresa = Empresa::factory()->create()->usuario;

    expect($duena->can('update', $vacante))->toBeTrue()
        ->and($duena->can('update', Vacante::factory()->for($vacante->empresa)->cerrada()->create()))->toBeFalse()
        ->and($otraEmpresa->can('update', $vacante))->toBeFalse()
        ->and($duena->can('delete', $vacante))->toBeFalse();
});

it('shows open vacancies to anyone and the rest only to the owner and the administrator', function () {
    $pausada = Vacante::factory()->pausada()->create();
    $estudiante = User::factory()->create();

    expect($estudiante->can('view', Vacante::factory()->create()))->toBeTrue()
        ->and($estudiante->can('view', $pausada))->toBeFalse()
        ->and(Empresa::factory()->create()->usuario->can('view', $pausada))->toBeFalse()
        ->and($pausada->empresa->usuario->can('view', $pausada))->toBeTrue()
        ->and(User::factory()->rol(Rol::Admin)->create()->can('view', $pausada))->toBeTrue();
});
