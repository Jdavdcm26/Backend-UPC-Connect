<?php

use App\Enums\Autenticacion\Rol;
use App\Models\User;
use App\Models\Vacantes\Empresa;

beforeEach(function () {
    $this->empresa = Empresa::factory()->create();
});

it('lets the administrator manage companies but never delete them', function () {
    $admin = User::factory()->rol(Rol::Admin)->create();

    expect($admin->can('viewAny', Empresa::class))->toBeTrue()
        ->and($admin->can('view', $this->empresa))->toBeTrue()
        ->and($admin->can('create', Empresa::class))->toBeTrue()
        ->and($admin->can('update', $this->empresa))->toBeTrue()
        ->and($admin->can('delete', $this->empresa))->toBeFalse();
});

it('lets a company see only its own record and not edit it', function () {
    $duena = $this->empresa->usuario;
    $otraEmpresa = Empresa::factory()->create()->usuario;

    expect($duena->can('view', $this->empresa))->toBeTrue()
        ->and($duena->can('update', $this->empresa))->toBeFalse()
        ->and($duena->can('viewAny', Empresa::class))->toBeFalse()
        ->and($otraEmpresa->can('view', $this->empresa))->toBeFalse();
});

it('denies every company ability to candidates', function () {
    $estudiante = User::factory()->create();

    expect($estudiante->can('view', $this->empresa))->toBeFalse()
        ->and($estudiante->can('create', Empresa::class))->toBeFalse();
});
