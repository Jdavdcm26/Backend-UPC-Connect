<?php

use App\Enums\Autenticacion\Rol;
use App\Models\Perfiles\Perfil;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;

beforeEach(fn () => $this->perfil = Perfil::factory()->create());

it('shows a profile to its owner, the administrator and the community of its university', function () {
    $companero = User::factory()->create(['universidad_id' => $this->perfil->usuario->universidad_id]);

    expect($this->perfil->usuario->can('view', $this->perfil))->toBeTrue()
        ->and(User::factory()->rol(Rol::Admin)->create()->can('view', $this->perfil))->toBeTrue()
        ->and($companero->can('view', $this->perfil))->toBeTrue()
        ->and(User::factory()->create()->can('view', $this->perfil))->toBeFalse();
});

it('shows a profile to a company only when that person applied to one of its vacancies', function () {
    $empresa = Empresa::factory()->create();
    $otraEmpresa = Empresa::factory()->create();
    Postulacion::factory()->de($this->perfil->usuario)->create(['vacante_id' => Vacante::factory()->for($empresa)->create()->id]);

    expect($empresa->usuario->can('view', $this->perfil))->toBeTrue()
        ->and($otraEmpresa->usuario->can('view', $this->perfil))->toBeFalse();
});

it('lets only the owner edit the profile', function () {
    expect($this->perfil->usuario->can('update', $this->perfil))->toBeTrue()
        ->and(User::factory()->rol(Rol::Admin)->create()->can('update', $this->perfil))->toBeFalse();
});
