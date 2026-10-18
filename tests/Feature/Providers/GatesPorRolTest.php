<?php

use App\Enums\Autenticacion\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('grants each role gate only to its roles', function (Rol $rol, array $gatesPermitidos) {
    $usuario = User::factory()->rol($rol)->make();

    foreach (['es-admin', 'es-empresa', 'es-candidato'] as $gate) {
        expect(Gate::forUser($usuario)->allows($gate))->toBe(in_array($gate, $gatesPermitidos, true), "{$rol->value} / {$gate}");
    }
})->with([
    'estudiante' => [Rol::Estudiante, ['es-candidato']],
    'egresado' => [Rol::Egresado, ['es-candidato']],
    'empresa' => [Rol::Empresa, ['es-empresa']],
    'moderador' => [Rol::Moderador, []],
    'administrador' => [Rol::Admin, ['es-admin']],
]);
