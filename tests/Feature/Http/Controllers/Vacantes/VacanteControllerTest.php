<?php

use App\Enums\Vacantes\ModalidadVacante;
use App\Enums\Vacantes\TipoVacante;
use App\Models\Catalogos\Carrera;
use App\Models\User;
use App\Models\Vacantes\Vacante;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

beforeEach(fn () => $this->estudiante = User::factory()->create());

/**
 * Carreras de la universidad del estudiante: el alcance por universidad
 * oculta las de otras universidades.
 */
function carreraPropia(): Factory
{
    return Carrera::factory()->state(['universidad_id' => test()->estudiante->universidad_id]);
}

function buscarVacantes(array $filtros = []): TestResponse
{
    return test()->actingAs(test()->estudiante)->getJson(route('api.v1.vacantes.index', $filtros));
}

describe('index', function () {
    it('returns 401 without a session', function () {
        $this->getJson(route('api.v1.vacantes.index'))->assertUnauthorized();
    });

    it('lists only open vacancies, newest first, with their company and programs', function () {
        $antigua = Vacante::factory()->create(['created_at' => now()->subDay()]);
        $reciente = Vacante::factory()->hasAttached(carreraPropia())->create();
        Vacante::factory()->pausada()->create();
        Vacante::factory()->cerrada()->create();
        Vacante::factory()->vencida()->create();

        buscarVacantes()
            ->assertOk()
            ->assertJsonPath('data.*.id', [$reciente->id, $antigua->id])
            ->assertJsonPath('data.0.empresa.razon_social', $reciente->empresa->razon_social)
            ->assertJsonCount(1, 'data.0.carreras')
            ->assertJsonMissingPath('data.0.texto_busqueda');
    });

    it('searches title, description and requirements ignoring accents and letter case', function () {
        $practica = Vacante::factory()->create(['titulo' => 'Práctica en Analítica de Datos']);
        $porRequisito = Vacante::factory()->create(['requisitos' => 'Manejo de ANALÍTICA web']);
        Vacante::factory()->create(['titulo' => 'Auxiliar contable', 'descripcion' => 'Contabilidad', 'requisitos' => 'Excel']);

        expect(buscarVacantes(['q' => 'analitica'])->json('data.*.id'))
            ->toEqualCanonicalizing([$practica->id, $porRequisito->id]);
    });

    it('treats percent and underscore in the search as plain text', function () {
        Vacante::factory()->create(['titulo' => 'Descuento del 100% en cursos']);
        Vacante::factory()->create(['titulo' => 'Otra vacante']);

        buscarVacantes(['q' => '%'])->assertJsonCount(1, 'data');
        buscarVacantes(['q' => '_'])->assertJsonCount(0, 'data');
    });

    it('combines the type, modality, city and program filters', function () {
        $carrera = carreraPropia()->create();
        $buscada = Vacante::factory()->hasAttached($carrera)->create([
            'tipo' => TipoVacante::Practica,
            'modalidad' => ModalidadVacante::Remota,
            'ciudad' => 'Bogotá',
        ]);
        Vacante::factory()->hasAttached($carrera)->create(['tipo' => TipoVacante::Practica, 'modalidad' => ModalidadVacante::Presencial, 'ciudad' => 'Bogotá']);
        Vacante::factory()->create(['tipo' => TipoVacante::Practica, 'modalidad' => ModalidadVacante::Remota, 'ciudad' => 'Bogotá']);
        Vacante::factory()->hasAttached($carrera)->create(['tipo' => TipoVacante::Vacante, 'modalidad' => ModalidadVacante::Remota, 'ciudad' => 'Bogotá']);

        buscarVacantes([
            'tipo' => 'PRACTICA',
            'modalidad' => 'REMOTA',
            'ciudad' => 'BOGOTA',
            'carrera_id' => $carrera->id,
        ])->assertJsonPath('data.*.id', [$buscada->id]);
    });

    it('paginates by cursor keeping the filters in the next link', function () {
        Vacante::factory()->count(16)->create(['ciudad' => 'Valledupar']);

        $primera = buscarVacantes(['ciudad' => 'Valledupar'])->assertJsonCount(15, 'data');

        expect($primera->json('links.next'))->toContain('ciudad=Valledupar');

        buscarVacantes(['ciudad' => 'Valledupar', 'cursor' => $primera->json('meta.next_cursor')])
            ->assertJsonCount(1, 'data');
    });

    it('rejects invalid filters with 422', function () {
        buscarVacantes(['tipo' => 'EMPLEO', 'carrera_id' => '123', 'q' => str_repeat('a', 101)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tipo', 'carrera_id' => 'El campo carrera debe ser un UUID válido.', 'q']);
    });

    it('loads the list with a constant number of queries', function () {
        Vacante::factory()->count(5)->hasAttached(carreraPropia()->count(2))->create();
        buscarVacantes();

        DB::enableQueryLog();
        buscarVacantes()->assertJsonCount(5, 'data');
        $consultasConCinco = count(DB::getQueryLog());

        Vacante::factory()->count(5)->hasAttached(carreraPropia()->count(2))->create();

        DB::flushQueryLog();
        buscarVacantes()->assertJsonCount(10, 'data');

        expect(count(DB::getQueryLog()))->toBe($consultasConCinco);
    });
});

describe('show', function () {
    it('returns an open vacancy with its company and programs', function () {
        $vacante = Vacante::factory()->hasAttached(carreraPropia())->create();

        $this->actingAs($this->estudiante)
            ->getJson(route('api.v1.vacantes.show', $vacante))
            ->assertOk()
            ->assertJsonPath('data.id', $vacante->id)
            ->assertJsonPath('data.abierta', true)
            ->assertJsonPath('data.empresa.id', $vacante->empresa->id)
            ->assertJsonCount(1, 'data.carreras');
    });

    it('returns 404 for a vacancy that is no longer open or does not exist', function (Closure $identificador) {
        $this->actingAs($this->estudiante)
            ->getJson('/api/v1/vacantes/'.$identificador())
            ->assertNotFound();
    })->with([
        'pausada' => [fn () => Vacante::factory()->pausada()->create()->id],
        'cerrada' => [fn () => Vacante::factory()->cerrada()->create()->id],
        'vencida' => [fn () => Vacante::factory()->vencida()->create()->id],
        'id que no es UUID' => [fn () => '123'],
    ]);
});
