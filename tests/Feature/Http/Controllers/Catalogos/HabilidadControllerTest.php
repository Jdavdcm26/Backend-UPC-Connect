<?php

use App\Enums\Catalogos\TipoHabilidad;
use App\Models\Catalogos\Habilidad;
use App\Models\User;
use Illuminate\Testing\TestResponse;

beforeEach(fn () => $this->usuario = User::factory()->create());

function buscarHabilidades(?string $busqueda = null): TestResponse
{
    return test()->actingAs(test()->usuario)
        ->getJson(route('api.v1.habilidades.index', $busqueda === null ? [] : ['q' => $busqueda]));
}

it('returns 401 when there is no session', function () {
    $this->getJson(route('api.v1.habilidades.index'))->assertUnauthorized();
});

it('lists all active skills by name without a search term', function () {
    Habilidad::factory()->create(['nombre' => 'SQL']);
    Habilidad::factory()->create(['nombre' => 'Ética profesional']);
    Habilidad::factory()->create(['nombre' => 'Inglés', 'tipo' => TipoHabilidad::Idioma]);
    Habilidad::factory()->inactiva()->create(['nombre' => 'Flash']);

    buscarHabilidades()
        ->assertOk()
        ->assertExactJsonStructure(['data' => ['*' => ['id', 'nombre', 'tipo']]])
        ->assertJsonPath('data.*.nombre', ['Ética profesional', 'Inglés', 'SQL'])
        ->assertJsonPath('data.1.tipo', 'IDIOMA');
});

it('finds skills ignoring accents and letter case', function () {
    Habilidad::factory()->create(['nombre' => 'Inglés']);
    Habilidad::factory()->create(['nombre' => 'Python']);

    buscarHabilidades('INGLES')->assertJsonPath('data.*.nombre', ['Inglés']);
    buscarHabilidades('pyt')->assertJsonPath('data.*.nombre', ['Python']);
});

it('puts skills that start with the search term first', function () {
    Habilidad::factory()->create(['nombre' => 'Análisis de datos']);
    Habilidad::factory()->create(['nombre' => 'Datos abiertos']);

    buscarHabilidades('datos')->assertJsonPath('data.*.nombre', ['Datos abiertos', 'Análisis de datos']);
});

it('returns at most 20 suggestions for a search term', function () {
    Habilidad::factory()->count(25)->sequence(fn ($secuencia) => ['nombre' => "Programación {$secuencia->index}"])->create();

    buscarHabilidades('program')->assertJsonCount(20, 'data');
});

it('rejects a search term longer than 100 characters with 422', function () {
    buscarHabilidades(str_repeat('a', 101))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q' => 'El campo búsqueda no puede tener más de 100 caracteres.']);
});

it('refreshes the cached catalog when a skill is added', function () {
    Habilidad::factory()->create(['nombre' => 'SQL']);
    buscarHabilidades()->assertJsonCount(1, 'data');

    Habilidad::factory()->create(['nombre' => 'Laravel']);

    buscarHabilidades()->assertJsonPath('data.*.nombre', ['Laravel', 'SQL']);
});
