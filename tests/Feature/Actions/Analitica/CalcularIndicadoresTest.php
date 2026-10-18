<?php

use App\Actions\Analitica\CalcularIndicadores;
use App\Enums\Autenticacion\Rol;
use App\Enums\Postulaciones\EstadoPostulacion;
use App\Models\Autenticacion\Universidad;
use App\Models\Catalogos\Carrera;
use App\Models\Catalogos\Habilidad;
use App\Models\Perfiles\Perfil;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;
use Carbon\CarbonImmutable;

/**
 * Escenario con valores conocidos (periodo del 1 al 15 de octubre de 2026):
 * - c1 (Sistemas) se registra el 2 y completa perfil y 3 habilidades a tiempo: activado.
 * - c2 (Derecho) se registra el 5 con 1 sola habilidad: no activado.
 * - c3 se registra el 6 sin verificar el correo ni crear perfil.
 * - Una cuenta y una empresa de otra universidad no cuentan.
 * - p1 (c1) y p2 (c2) a la vacante del periodo: seleccionado tras 10 h y no seleccionado tras 30 h.
 */
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-01 08:00'));
    $this->upc = Universidad::factory()->create();
    $this->sistemas = Carrera::factory()->for($this->upc)->create();
    $this->derecho = Carrera::factory()->for($this->upc)->create();
    $empresa = Empresa::factory()->for(User::factory()->for($this->upc)->rol(Rol::Empresa), 'usuario')->create(['aprobada_en' => now()]);
    Empresa::factory()->create();

    $this->travelTo(CarbonImmutable::parse('2026-05-01 08:00'));
    $antigua = Vacante::factory()->for($empresa)->hasAttached($this->sistemas)->create(['fecha_cierre' => '2026-12-31']);

    $this->travelTo(CarbonImmutable::parse('2026-10-02 08:00'));
    $c1 = User::factory()->for($this->upc)->create();
    Perfil::factory()->for($c1, 'usuario')->create(['carrera_id' => $this->sistemas->id]);
    $this->travelTo(CarbonImmutable::parse('2026-10-03 08:00'));
    $c1->perfil->habilidades()->attach(Habilidad::factory()->count(3)->create());
    $vacante = Vacante::factory()->for($empresa)->hasAttached([$this->sistemas, $this->derecho])->create();

    $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00'));
    $c2 = User::factory()->for($this->upc)->create();
    Perfil::factory()->for($c2, 'usuario')->create(['carrera_id' => $this->derecho->id]);
    $c2->perfil->habilidades()->attach(Habilidad::factory()->create());

    $this->travelTo(CarbonImmutable::parse('2026-10-06 08:00'));
    User::factory()->for($this->upc)->unverified()->create();
    User::factory()->create();

    $this->travelTo(CarbonImmutable::parse('2026-10-06 10:00'));
    $p1 = Postulacion::factory()->de($c1)->create(['vacante_id' => $vacante->id]);
    $this->travelTo(CarbonImmutable::parse('2026-10-07 10:00'));
    $p2 = Postulacion::factory()->de($c2)->create(['vacante_id' => $vacante->id]);
    $this->travelTo(CarbonImmutable::parse('2026-09-20 10:00'));
    Postulacion::factory()->de($c1)->create(['vacante_id' => $antigua->id]);

    $responsable = $empresa->usuario_id;
    $p1->historial()->create(['estado' => EstadoPostulacion::EnRevision, 'cambiado_por' => $responsable, 'cambiado_en' => '2026-10-06 20:00']);
    $p1->historial()->create(['estado' => EstadoPostulacion::Seleccionado, 'cambiado_por' => $responsable, 'cambiado_en' => '2026-10-08 10:00']);
    $p1->forceFill(['estado' => EstadoPostulacion::Seleccionado])->save();
    $p2->historial()->create(['estado' => EstadoPostulacion::NoSeleccionado, 'cambiado_por' => $responsable, 'cambiado_en' => '2026-10-08 16:00']);
    $p2->forceFill(['estado' => EstadoPostulacion::NoSeleccionado])->save();

    $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00'));
});

function indicadores(?string $carreraId = null, string $desde = '2026-10-01', string $hasta = '2026-10-15'): array
{
    return app(CalcularIndicadores::class)->handle(test()->upc->id, CarbonImmutable::parse($desde), CarbonImmutable::parse($hasta), $carreraId);
}

it('computes every indicator of the period for the university', function () {
    expect(indicadores())->toBe([
        'usuarios_registrados' => 2,
        'usuarios_activos' => 3,
        'activacion' => 33.3,
        'empresas_aprobadas' => 1,
        'empresas_activas' => 1,
        'vacantes_creadas' => 1,
        'postulaciones' => 2,
        'postulaciones_por_vacante' => 2.0,
        'tasa_seleccion' => 50.0,
        'respuesta_empresas_horas' => 20.0,
        'postulaciones_por_mes' => ['2026-10' => 2],
    ]);
});

it('restricts the indicators to one program', function () {
    expect(indicadores($this->sistemas->id))->toMatchArray([
        'usuarios_registrados' => 1,
        'usuarios_activos' => 2,
        'activacion' => 100.0,
        'empresas_activas' => 1,
        'vacantes_creadas' => 1,
        'postulaciones' => 1,
        'tasa_seleccion' => 100.0,
        'respuesta_empresas_horas' => 10.0,
    ]);
});

it('returns zeros and no data instead of failing for a period without activity', function () {
    expect(indicadores(desde: '2026-01-01', hasta: '2026-02-28'))->toBe([
        'usuarios_registrados' => 0,
        'usuarios_activos' => 0,
        'activacion' => null,
        'empresas_aprobadas' => 0,
        'empresas_activas' => 0,
        'vacantes_creadas' => 0,
        'postulaciones' => 0,
        'postulaciones_por_vacante' => null,
        'tasa_seleccion' => null,
        'respuesta_empresas_horas' => null,
        'postulaciones_por_mes' => ['2026-01' => 0, '2026-02' => 0],
    ]);
});

it('caches the result for ten minutes', function () {
    expect(indicadores()['postulaciones'])->toBe(2);

    Postulacion::factory()->de(User::factory()->for($this->upc)->create())->create(['vacante_id' => Vacante::factory()->create()->id]);

    expect(indicadores()['postulaciones'])->toBe(2);

    $this->travel(11)->minutes();

    expect(indicadores()['postulaciones'])->toBe(3);
});
