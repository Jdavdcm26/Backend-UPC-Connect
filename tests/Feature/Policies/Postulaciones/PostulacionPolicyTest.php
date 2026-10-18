<?php

use App\Enums\Autenticacion\Rol;
use App\Models\Perfiles\Perfil;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use Illuminate\Support\Facades\Gate;

it('lets students and graduates with a profile apply, with the reason otherwise', function () {
    $conPerfil = Perfil::factory()->create()->usuario;
    $sinPerfil = User::factory()->create();
    $empresa = Empresa::factory()->create()->usuario;

    expect($conPerfil->can('create', Postulacion::class))->toBeTrue()
        ->and(Gate::forUser($sinPerfil)->inspect('create', Postulacion::class)->message())
        ->toBe('Completa tu perfil (carrera y semestre, o año de grado) antes de postularte.')
        ->and(Gate::forUser($empresa)->inspect('create', Postulacion::class)->message())
        ->toBe('Solo estudiantes y egresados pueden postularse a vacantes.')
        ->and(User::factory()->rol(Rol::Admin)->create()->can('create', Postulacion::class))->toBeFalse();
});

it('shows an application only to its applicant and to the company that owns the vacancy', function () {
    $postulacion = Postulacion::factory()->create();

    expect($postulacion->usuario->can('view', $postulacion))->toBeTrue()
        ->and($postulacion->vacante->empresa->usuario->can('view', $postulacion))->toBeTrue()
        ->and(Empresa::factory()->create()->usuario->can('view', $postulacion))->toBeFalse()
        ->and(Perfil::factory()->create()->usuario->can('view', $postulacion))->toBeFalse();
});

it('lets only the owner company change the state', function () {
    $postulacion = Postulacion::factory()->create();

    expect($postulacion->vacante->empresa->usuario->can('cambiarEstado', $postulacion))->toBeTrue()
        ->and($postulacion->usuario->can('cambiarEstado', $postulacion))->toBeFalse()
        ->and(Empresa::factory()->create()->usuario->can('cambiarEstado', $postulacion))->toBeFalse();
});
