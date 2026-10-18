<?php

use App\Enums\Postulaciones\EstadoPostulacion;
use App\Models\Perfiles\Perfil;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->candidato = Perfil::factory()->create()->usuario;
    $this->vacante = Vacante::factory()->create();
});

function postularse(User $usuario, Vacante|string $vacante, array $datos = []): TestResponse
{
    $id = $vacante instanceof Vacante ? $vacante->id : $vacante;

    return test()->actingAs($usuario)->postJson("/api/v1/vacantes/{$id}/postulaciones", $datos);
}

it('returns 401 without a session', function () {
    $this->postJson("/api/v1/vacantes/{$this->vacante->id}/postulaciones")->assertUnauthorized();
});

it('registers the application as received with its first history entry', function () {
    $this->freezeSecond();

    postularse($this->candidato, $this->vacante, ['mensaje' => 'Me interesa mucho.'])
        ->assertCreated()
        ->assertJsonPath('data.estado', 'RECIBIDA')
        ->assertJsonPath('data.mensaje', 'Me interesa mucho.')
        ->assertJsonPath('data.vacante.id', $this->vacante->id)
        ->assertJsonPath('data.vacante.empresa.razon_social', $this->vacante->empresa->razon_social);

    $postulacion = Postulacion::query()->sole();
    $historial = $postulacion->historial()->sole();

    expect($postulacion)
        ->usuario_id->toBe($this->candidato->id)
        ->estado->toBe(EstadoPostulacion::Recibida)
        ->and($historial)
        ->estado->toBe(EstadoPostulacion::Recibida)
        ->cambiado_por->toBe($this->candidato->id)
        ->cambiado_en->toEqual(now());
});

it('accepts an application without a message', function () {
    postularse($this->candidato, $this->vacante)
        ->assertCreated()
        ->assertJsonPath('data.mensaje', null);
});

it('rejects a second application to the same vacancy with 422', function () {
    postularse($this->candidato, $this->vacante)->assertCreated();

    postularse($this->candidato, $this->vacante)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['vacante' => 'Ya te postulaste a esta vacante. Sigue su estado en «Mis postulaciones».']);

    expect(Postulacion::query()->count())->toBe(1);
});

it('answers 422 instead of failing when a simultaneous request already saved the application', function () {
    // Simula la otra solicitud insertando la misma postulación justo antes de guardar.
    Postulacion::creating(function (Postulacion $postulacion): void {
        DB::table('postulaciones')->insert([
            'id' => (string) Str::uuid7(),
            'vacante_id' => $postulacion->vacante_id,
            'usuario_id' => $postulacion->usuario_id,
            'estado' => 'RECIBIDA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    postularse($this->candidato, $this->vacante)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['vacante' => 'Ya te postulaste a esta vacante. Sigue su estado en «Mis postulaciones».']);
});

it('rejects applications to vacancies that are no longer open with 422', function (Closure $vacante) {
    postularse($this->candidato, $vacante())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['vacante' => 'Esta vacante ya no recibe postulaciones.']);

    expect(Postulacion::query()->exists())->toBeFalse();
})->with([
    'pausada' => [fn () => Vacante::factory()->pausada()->create()],
    'cerrada' => [fn () => Vacante::factory()->cerrada()->create()],
    'vencida' => [fn () => Vacante::factory()->vencida()->create()],
]);

it('returns 403 with the reason to accounts that cannot apply', function (Closure $usuario, string $motivo) {
    postularse($usuario(), $this->vacante)
        ->assertForbidden()
        ->assertJsonPath('message', $motivo);
})->with([
    'estudiante sin perfil' => [fn () => User::factory()->create(), 'Completa tu perfil (carrera y semestre, o año de grado) antes de postularte.'],
    'empresa' => [fn () => Empresa::factory()->create()->usuario, 'Solo estudiantes y egresados pueden postularse a vacantes.'],
]);

it('returns 404 for a vacancy that does not exist', function (string $id) {
    postularse($this->candidato, $id)->assertNotFound();
})->with(['UUID inexistente' => '01a14d9e-e90e-7137-ae53-be980e27cddd', 'no es UUID' => '123']);

it('rejects a message longer than 1000 characters', function () {
    postularse($this->candidato, $this->vacante, ['mensaje' => str_repeat('a', 1001)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['mensaje' => 'El campo mensaje no puede tener más de 1000 caracteres.']);
});

describe('ya_postulado in the public vacancies', function () {
    it('tells a candidate whether they already applied, in the list and the detail', function () {
        $otra = Vacante::factory()->create();
        Postulacion::factory()->de($this->candidato)->create(['vacante_id' => $this->vacante->id]);

        $this->actingAs($this->candidato)
            ->getJson(route('api.v1.vacantes.show', $this->vacante))
            ->assertJsonPath('data.ya_postulado', true);

        $this->actingAs($this->candidato)
            ->getJson(route('api.v1.vacantes.show', $otra))
            ->assertJsonPath('data.ya_postulado', false);

        $lista = $this->actingAs($this->candidato)->getJson(route('api.v1.vacantes.index'))->json('data');

        expect(collect($lista)->pluck('ya_postulado', 'id')->all())
            ->toEqual([$this->vacante->id => true, $otra->id => false]);
    });

    it('is not included for accounts that cannot apply', function () {
        $this->actingAs(Empresa::factory()->create()->usuario)
            ->getJson(route('api.v1.vacantes.show', $this->vacante))
            ->assertOk()
            ->assertJsonMissingPath('data.ya_postulado');
    });
});

describe('index', function () {
    it('returns 401 without a session and 403 to companies', function () {
        $this->getJson(route('api.v1.me.postulaciones.index'))->assertUnauthorized();

        $this->actingAs(Empresa::factory()->create()->usuario)
            ->getJson(route('api.v1.me.postulaciones.index'))
            ->assertForbidden();
    });

    it('lists only the own applications, newest first, with vacancy, company, date and state', function () {
        $antigua = Postulacion::factory()->de($this->candidato)->create(['created_at' => now()->subDay()]);
        $reciente = Postulacion::factory()->de($this->candidato)->enEstado(EstadoPostulacion::Entrevista)->create();
        Postulacion::factory()->create();

        $this->actingAs($this->candidato)
            ->getJson(route('api.v1.me.postulaciones.index'))
            ->assertOk()
            ->assertJsonPath('data.*.id', [$reciente->id, $antigua->id])
            ->assertJsonPath('data.0.estado', 'ENTREVISTA')
            ->assertJsonPath('data.0.vacante.titulo', $reciente->vacante->titulo)
            ->assertJsonPath('data.0.vacante.empresa.razon_social', $reciente->vacante->empresa->razon_social)
            ->assertJsonStructure(['data' => ['*' => ['creada_en', 'vacante' => ['abierta']]], 'meta' => ['next_cursor']]);
    });

    it('reflects the current state after the company changes it', function () {
        $postulacion = Postulacion::factory()->de($this->candidato)->create();
        $postulacion->forceFill(['estado' => EstadoPostulacion::Seleccionado])->save();

        $this->actingAs($this->candidato)
            ->getJson(route('api.v1.me.postulaciones.index'))
            ->assertJsonPath('data.0.estado', 'SELECCIONADO');
    });

    it('paginates by cursor', function () {
        Postulacion::factory()->de($this->candidato)->count(16)->create();

        $primera = $this->actingAs($this->candidato)
            ->getJson(route('api.v1.me.postulaciones.index'))
            ->assertJsonCount(15, 'data');

        $this->actingAs($this->candidato)
            ->getJson(route('api.v1.me.postulaciones.index', ['cursor' => $primera->json('meta.next_cursor')]))
            ->assertJsonCount(1, 'data');
    });

    it('loads the list with a constant number of queries', function () {
        Postulacion::factory()->de($this->candidato)->count(3)->create();
        $this->actingAs($this->candidato)->getJson(route('api.v1.me.postulaciones.index'));

        DB::enableQueryLog();
        $this->actingAs($this->candidato)->getJson(route('api.v1.me.postulaciones.index'))->assertJsonCount(3, 'data');
        $consultasConTres = count(DB::getQueryLog());

        Postulacion::factory()->de($this->candidato)->count(3)->create();

        DB::flushQueryLog();
        $this->actingAs($this->candidato)->getJson(route('api.v1.me.postulaciones.index'))->assertJsonCount(6, 'data');

        expect(count(DB::getQueryLog()))->toBe($consultasConTres);
    });
});
