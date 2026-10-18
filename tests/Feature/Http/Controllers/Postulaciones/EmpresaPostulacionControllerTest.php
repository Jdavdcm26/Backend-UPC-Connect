<?php

use App\Enums\Postulaciones\EstadoPostulacion;
use App\Events\Postulaciones\PostulacionEstadoCambiado;
use App\Models\Catalogos\Habilidad;
use App\Models\Perfiles\Perfil;
use App\Models\Postulaciones\Postulacion;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->empresa = Empresa::factory()->create();
    $this->cuenta = $this->empresa->usuario;
    $this->vacante = Vacante::factory()->for($this->empresa)->create();
});

function postulacionA(Vacante $vacante, array $atributos = []): Postulacion
{
    return Postulacion::factory()->create(['vacante_id' => $vacante->id, ...$atributos]);
}

describe('index', function () {
    it('lists the applications of an own vacancy, newest first, with the visible candidate profile', function () {
        $perfil = Perfil::factory()->hasAttached(Habilidad::factory()->state(['nombre' => 'Laravel']), [], 'habilidades')->create(['nombres' => 'Ana']);
        $antigua = postulacionA($this->vacante, ['created_at' => now()->subDay()]);
        $reciente = Postulacion::factory()->de($perfil->usuario)->create(['vacante_id' => $this->vacante->id]);
        postulacionA(Vacante::factory()->for($this->empresa)->create());
        $nombreCarrera = $perfil->carrera->nombre;

        $this->actingAs($this->cuenta)
            ->getJson(route('api.v1.empresa.vacantes.postulaciones.index', $this->vacante))
            ->assertOk()
            ->assertJsonPath('data.*.id', [$reciente->id, $antigua->id])
            ->assertJsonPath('data.0.candidato.nombres', 'Ana')
            ->assertJsonPath('data.0.candidato.carrera.nombre', $nombreCarrera)
            ->assertJsonPath('data.0.candidato.habilidades.0.nombre', 'Laravel')
            ->assertJsonMissingPath('data.0.candidato.email')
            ->assertJsonMissingPath('data.0.historial')
            ->assertJsonStructure(['meta' => ['next_cursor']]);
    });

    it('returns 404 for a vacancy of another company and 403 to candidates', function () {
        $candidato = Perfil::factory()->create()->usuario;

        $this->actingAs($this->cuenta)
            ->getJson(route('api.v1.empresa.vacantes.postulaciones.index', Vacante::factory()->create()))
            ->assertNotFound();

        $this->actingAs($candidato)
            ->getJson(route('api.v1.empresa.vacantes.postulaciones.index', $this->vacante))
            ->assertForbidden();
    });

    it('loads the list with a constant number of queries', function () {
        Postulacion::factory()->count(3)->create(['vacante_id' => $this->vacante->id]);
        $ruta = route('api.v1.empresa.vacantes.postulaciones.index', $this->vacante);
        $this->actingAs($this->cuenta)->getJson($ruta);

        DB::enableQueryLog();
        $this->actingAs($this->cuenta)->getJson($ruta)->assertJsonCount(3, 'data');
        $consultasConTres = count(DB::getQueryLog());

        Postulacion::factory()->count(3)->create(['vacante_id' => $this->vacante->id]);

        DB::flushQueryLog();
        $this->actingAs($this->cuenta)->getJson($ruta)->assertJsonCount(6, 'data');

        expect(count(DB::getQueryLog()))->toBe($consultasConTres);
    });
});

describe('show', function () {
    it('returns the detail with the message and the history with its responsible', function () {
        $postulacion = postulacionA($this->vacante, ['mensaje' => 'Tengo experiencia en Laravel.']);
        $this->cuenta->update(['name' => 'Laura Pérez']);
        $postulacion->historial()->create(['estado' => EstadoPostulacion::EnRevision, 'cambiado_por' => $this->cuenta->id, 'cambiado_en' => now()->addHour()]);
        $nombreCandidato = $postulacion->usuario->name;

        $this->actingAs($this->cuenta)
            ->getJson(route('api.v1.empresa.postulaciones.show', $postulacion))
            ->assertOk()
            ->assertJsonPath('data.mensaje', 'Tengo experiencia en Laravel.')
            ->assertJsonPath('data.historial.*.estado', ['RECIBIDA', 'EN_REVISION'])
            ->assertJsonPath('data.historial.0.responsable', $nombreCandidato)
            ->assertJsonPath('data.historial.1.responsable', 'Laura Pérez');
    });

    it('returns 404 for an application to another company or an invalid id', function (Closure $identificador) {
        $this->actingAs($this->cuenta)
            ->getJson('/api/v1/empresa/postulaciones/'.$identificador())
            ->assertNotFound();
    })->with([
        'de otra empresa' => [fn () => Postulacion::factory()->create()->id],
        'id que no es UUID' => [fn () => '123'],
    ]);
});

describe('estado', function () {
    it('changes the state, records who and when, and announces the change', function () {
        Event::fake([PostulacionEstadoCambiado::class]);
        $this->freezeSecond();
        $postulacion = postulacionA($this->vacante);

        $this->actingAs($this->cuenta)
            ->patchJson(route('api.v1.empresa.postulaciones.estado', $postulacion), ['estado' => 'ENTREVISTA'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'ENTREVISTA')
            ->assertJsonPath('data.historial.*.estado', ['RECIBIDA', 'ENTREVISTA']);

        $cambio = $postulacion->historial()->where('estado', EstadoPostulacion::Entrevista)->sole();

        expect($postulacion->fresh()->estado)->toBe(EstadoPostulacion::Entrevista)
            ->and($cambio->cambiado_por)->toBe($this->cuenta->id)
            ->and($cambio->cambiado_en)->toEqual(now());

        Event::assertDispatched(PostulacionEstadoCambiado::class, fn (PostulacionEstadoCambiado $evento): bool => $evento->cambio->is($cambio));
    });

    it('rejects the current state, withdrawn and unknown states with 422', function (string $estado, string $mensaje) {
        $postulacion = postulacionA($this->vacante);

        $this->actingAs($this->cuenta)
            ->patchJson(route('api.v1.empresa.postulaciones.estado', $postulacion), ['estado' => $estado])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['estado' => $mensaje]);

        expect($postulacion->historial()->count())->toBe(1);
    })->with([
        'mismo estado' => ['RECIBIDA', 'La postulación ya está en ese estado.'],
        'retirada' => ['RETIRADA', 'Elige uno de estos estados: Recibida, En revisión, Entrevista, Seleccionado o No seleccionado.'],
        'desconocido' => ['CONTRATADO', 'Elige uno de estos estados: Recibida, En revisión, Entrevista, Seleccionado o No seleccionado.'],
    ]);

    it('lets the company keep managing applications of a closed vacancy', function () {
        $postulacion = postulacionA(Vacante::factory()->for($this->empresa)->cerrada()->create());

        $this->actingAs($this->cuenta)
            ->patchJson(route('api.v1.empresa.postulaciones.estado', $postulacion), ['estado' => 'SELECCIONADO'])
            ->assertOk();
    });

    it('returns 404 when changing an application of another company', function () {
        $ajena = Postulacion::factory()->create();

        $this->actingAs($this->cuenta)
            ->patchJson(route('api.v1.empresa.postulaciones.estado', $ajena), ['estado' => 'SELECCIONADO'])
            ->assertNotFound();

        expect($ajena->fresh()->estado)->toBe(EstadoPostulacion::Recibida);
    });
});

it('adds the number of applications to the own vacancies', function () {
    postulacionA($this->vacante);
    postulacionA($this->vacante);

    $this->actingAs($this->cuenta)
        ->getJson(route('api.v1.empresa.vacantes.index'))
        ->assertJsonPath('data.0.postulaciones_total', 2);

    $this->actingAs($this->cuenta)
        ->getJson(route('api.v1.empresa.vacantes.show', $this->vacante))
        ->assertJsonPath('data.postulaciones_total', 2);
});
