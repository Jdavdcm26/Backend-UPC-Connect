<?php

use App\Http\Resources\Perfiles\PerfilVisibleResource;
use App\Models\Catalogos\Habilidad;
use App\Models\Perfiles\Perfil;
use App\Models\User;
use Illuminate\Http\Request;

function perfilVistoPor(Perfil $perfil, User $visitante): array
{
    $solicitud = Request::create('/');
    $solicitud->setUserResolver(fn () => $visitante);

    return (new PerfilVisibleResource($perfil->load(['carrera', 'habilidades'])))->resolve($solicitud);
}

it('shows every section to a visitor who may see the profile, never the email', function () {
    $perfil = Perfil::factory()->hasAttached(Habilidad::factory(), ['nivel' => 'AVANZADO'], 'habilidades')->create();
    $companero = User::factory()->create(['universidad_id' => $perfil->usuario->universidad_id]);

    $datos = perfilVistoPor($perfil, $companero);

    expect($datos)
        ->toHaveKeys(['id', 'slug', 'nombres', 'apellidos', 'titular', 'carrera', 'semestre', 'anio_grado', 'ciudad', 'descripcion', 'intereses', 'habilidades'])
        ->not->toHaveKey('email')
        ->and($datos['habilidades'][0]['nivel']->value)->toBe('AVANZADO');
});

it('shows only the identifier to a visitor who may not see the profile', function () {
    $perfil = Perfil::factory()->create();

    expect(array_keys(perfilVistoPor($perfil, User::factory()->create())))->toBe(['id', 'slug']);
});
