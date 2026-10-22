<?php

use App\Enums\Autenticacion\Rol;
use App\Enums\Perfiles\NivelHabilidad;
use App\Models\Catalogos\Habilidad;
use App\Models\Perfiles\Perfil;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->perfil = Perfil::factory()
        ->hasAttached(Habilidad::factory()->state(['nombre' => 'Laravel']), ['nivel' => NivelHabilidad::Avanzado], 'habilidades')
        ->create(['slug' => 'ana-gomez', 'nombres' => 'Ana']);
    $this->universidadId = $this->perfil->usuario->universidad_id;
});

function verPerfil(User $visitante, string $slug = 'ana-gomez'): TestResponse
{
    return test()->actingAs($visitante)->getJson("/api/v1/perfiles/{$slug}");
}

it('returns 401 without a session', function () {
    $this->getJson('/api/v1/perfiles/ana-gomez')->assertUnauthorized();
});

it('shows the full profile to a student of the same university, without the email', function () {
    verPerfil(User::factory()->create(['universidad_id' => $this->universidadId]))
        ->assertOk()
        ->assertJsonPath('data.slug', 'ana-gomez')
        ->assertJsonPath('data.nombres', 'Ana')
        ->assertJsonPath('data.carrera.nombre', $this->perfil->carrera->nombre)
        ->assertJsonPath('data.habilidades.0.nombre', 'Laravel')
        ->assertJsonPath('data.habilidades.0.nivel', 'AVANZADO')
        ->assertJsonStructure(['data' => ['descripcion', 'intereses', 'ciudad', 'semestre', 'anio_grado', 'titular']])
        ->assertJsonMissingPath('data.email');
});

it('shows the profile to its owner and to the administrator', function () {
    verPerfil($this->perfil->usuario)->assertOk();
    verPerfil(User::factory()->rol(Rol::Admin)->create())->assertOk();
});

it('shows the profile to a company only when the person applied to one of its vacancies', function () {
    $empresa = Empresa::factory()->create();
    $otraEmpresa = Empresa::factory()->create();
    Postulacion::factory()->de($this->perfil->usuario)->create(['vacante_id' => Vacante::factory()->for($empresa)->create()->id]);

    [$cuentaEmpresa, $cuentaOtraEmpresa] = [$empresa->usuario, $otraEmpresa->usuario];

    verPerfil($cuentaEmpresa)->assertOk()->assertJsonPath('data.nombres', 'Ana');
    verPerfil($cuentaOtraEmpresa)->assertNotFound();
});

it('returns 404 alike when the profile is not visible or does not exist', function (Closure $visitante, string $slug) {
    verPerfil($visitante(), $slug)->assertNotFound();
})->with([
    'estudiante de otra universidad' => [fn () => User::factory()->create(), 'ana-gomez'],
    'slug inexistente' => [fn () => User::factory()->create(), 'nadie'],
]);

it('includes the follow counters and the relation with the visitor', function () {
    $duena = $this->perfil->usuario;
    $visitante = User::factory()->create(['universidad_id' => $this->universidadId]);
    $otra = User::factory()->create(['universidad_id' => $this->universidadId]);
    $visitante->seguidos()->attach($duena);
    $otra->seguidos()->attach($duena);
    $duena->seguidos()->attach($otra);

    verPerfil($visitante)
        ->assertOk()
        ->assertJsonPath('red', [
            'usuario_id' => $duena->id,
            'seguidores' => 2,
            'seguidos' => 1,
            'lo_sigues' => true,
            'lo_bloqueaste' => false,
        ]);

    $visitante->bloqueados()->attach($duena);

    verPerfil($visitante)->assertJsonPath('red.lo_bloqueaste', true);
});
