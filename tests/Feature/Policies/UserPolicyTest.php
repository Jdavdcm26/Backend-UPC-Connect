<?php

use App\Enums\Autenticacion\Rol;
use App\Models\User;

it('lets only administrators manage accounts', function () {
    $admin = User::factory()->rol(Rol::Admin)->create();
    $estudiante = User::factory()->create();
    $suspendido = User::factory()->suspendido()->create();

    expect($admin->can('viewAny', User::class))->toBeTrue()
        ->and($admin->can('suspender', $estudiante))->toBeTrue()
        ->and($admin->can('suspender', $suspendido))->toBeFalse()
        ->and($admin->can('reactivar', $suspendido))->toBeTrue()
        ->and($admin->can('reactivar', $estudiante))->toBeFalse()
        ->and($admin->can('suspender', $admin))->toBeFalse()
        ->and($estudiante->can('viewAny', User::class))->toBeFalse()
        ->and($estudiante->can('suspender', $suspendido))->toBeFalse();
});
