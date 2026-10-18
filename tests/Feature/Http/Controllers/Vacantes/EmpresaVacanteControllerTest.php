<?php

use App\Enums\Vacantes\EstadoVacante;
use App\Models\Catalogos\Carrera;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;

beforeEach(function () {
    $this->empresa = Empresa::factory()->create();
    $this->cuenta = $this->empresa->usuario;
    $this->carrera = Carrera::factory()->create(['universidad_id' => $this->cuenta->universidad_id, 'nombre' => 'Ingeniería de Sistemas']);
});

function datosDeVacante(array $cambios = []): array
{
    return [
        'tipo' => 'PRACTICA',
        'titulo' => 'Practicante de desarrollo web',
        'descripcion' => 'Apoyo al equipo de desarrollo.',
        'requisitos' => 'Estudiante de octavo semestre en adelante.',
        'ciudad' => 'Valledupar',
        'modalidad' => 'HIBRIDA',
        'fecha_cierre' => today()->addMonth()->toDateString(),
        'carreras' => [test()->carrera->id],
        ...$cambios,
    ];
}

it('returns 401 without a session and 403 to accounts that are not companies', function () {
    $vacante = Vacante::factory()->for($this->empresa)->create();
    $this->getJson(route('api.v1.empresa.vacantes.index'))->assertUnauthorized();

    $this->actingAs(User::factory()->create())
        ->getJson(route('api.v1.empresa.vacantes.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->getJson(route('api.v1.empresa.vacantes.show', $vacante))
        ->assertForbidden();
});

describe('store', function () {
    it('publishes an active vacancy with its target programs', function () {
        $this->actingAs($this->cuenta)
            ->postJson(route('api.v1.empresa.vacantes.store'), datosDeVacante())
            ->assertCreated()
            ->assertJsonPath('data.estado', 'ACTIVA')
            ->assertJsonPath('data.abierta', true)
            ->assertJsonPath('data.tipo', 'PRACTICA')
            ->assertJsonPath('data.carreras.0.nombre', 'Ingeniería de Sistemas');

        expect($this->empresa->vacantes()->sole())
            ->titulo->toBe('Practicante de desarrollo web')
            ->estado->toBe(EstadoVacante::Activa);
    });

    it('returns 403 with the reason when the company is not approved, even with incomplete data', function () {
        $pendiente = Empresa::factory()->pendiente()->create();

        $this->actingAs($pendiente->usuario)
            ->postJson(route('api.v1.empresa.vacantes.store'), [])
            ->assertForbidden()
            ->assertJsonPath('message', 'Tu empresa aún no está aprobada para publicar vacantes. Comunícate con la administración de la plataforma.');

        expect(Vacante::query()->exists())->toBeFalse();
    });

    it('returns the required-field errors for an empty vacancy', function () {
        $this->actingAs($this->cuenta)
            ->postJson(route('api.v1.empresa.vacantes.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'tipo', 'descripcion', 'requisitos', 'ciudad', 'modalidad',
                'titulo' => 'El campo título es obligatorio.',
                'fecha_cierre' => 'El campo fecha de cierre es obligatorio.',
                'carreras' => 'Elige al menos una carrera objetivo.',
            ]);
    });

    it('rejects a closing date in the past', function () {
        $this->actingAs($this->cuenta)
            ->postJson(route('api.v1.empresa.vacantes.store'), datosDeVacante(['fecha_cierre' => today()->subDay()->toDateString()]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fecha_cierre' => 'La fecha de cierre no puede ser anterior a hoy.']);
    });

    it('rejects target programs that are inactive or from another university', function (Closure $carrera) {
        $this->actingAs($this->cuenta)
            ->postJson(route('api.v1.empresa.vacantes.store'), datosDeVacante(['carreras' => [$carrera()->id]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['carreras' => 'Elige carreras activas de tu universidad.']);
    })->with([
        'inactiva' => [fn () => Carrera::factory()->inactiva()->create(['universidad_id' => test()->cuenta->universidad_id])],
        'de otra universidad' => [fn () => Carrera::factory()->create()],
    ]);
});

describe('index', function () {
    it('lists only the own vacancies in every state, newest first, with a cursor', function () {
        $antigua = Vacante::factory()->for($this->empresa)->cerrada()->create(['created_at' => now()->subDay()]);
        $reciente = Vacante::factory()->for($this->empresa)->pausada()->create();
        Vacante::factory()->create();

        $this->actingAs($this->cuenta)
            ->getJson(route('api.v1.empresa.vacantes.index'))
            ->assertOk()
            ->assertJsonPath('data.*.id', [$reciente->id, $antigua->id])
            ->assertJsonStructure(['meta' => ['next_cursor', 'per_page']]);
    });

    it('filters the own vacancies by state', function () {
        $pausada = Vacante::factory()->for($this->empresa)->pausada()->create();
        Vacante::factory()->for($this->empresa)->create();

        $this->actingAs($this->cuenta)
            ->getJson(route('api.v1.empresa.vacantes.index', ['estado' => 'PAUSADA']))
            ->assertJsonPath('data.*.id', [$pausada->id]);
    });
});

describe('show', function () {
    it('returns an own vacancy even when paused', function () {
        $vacante = Vacante::factory()->for($this->empresa)->pausada()->create();

        $this->actingAs($this->cuenta)
            ->getJson(route('api.v1.empresa.vacantes.show', $vacante))
            ->assertOk()
            ->assertJsonPath('data.estado', 'PAUSADA');
    });

    it('returns 404 for a vacancy of another company or an invalid id', function (Closure $identificador) {
        $this->actingAs($this->cuenta)
            ->getJson(route('api.v1.empresa.vacantes.show', $identificador()))
            ->assertNotFound();
    })->with([
        'de otra empresa' => [fn () => Vacante::factory()->create()->id],
        'id que no es UUID' => [fn () => '123'],
    ]);
});

describe('update', function () {
    it('updates the own vacancy and its target programs', function () {
        $vacante = Vacante::factory()->for($this->empresa)->create();
        $otraCarrera = Carrera::factory()->create(['universidad_id' => $this->cuenta->universidad_id]);

        $this->actingAs($this->cuenta)
            ->putJson(route('api.v1.empresa.vacantes.update', $vacante), datosDeVacante([
                'titulo' => 'Desarrollador junior',
                'carreras' => [$otraCarrera->id],
            ]))
            ->assertOk()
            ->assertJsonPath('data.titulo', 'Desarrollador junior');

        expect($vacante->carreras()->pluck('carreras.id')->all())->toBe([$otraCarrera->id]);
    });

    it('keeps a target program that was deactivated after publishing', function () {
        $vacante = Vacante::factory()->for($this->empresa)->hasAttached($this->carrera)->create();
        $this->carrera->update(['activa' => false]);

        $this->actingAs($this->cuenta)
            ->putJson(route('api.v1.empresa.vacantes.update', $vacante), datosDeVacante())
            ->assertOk();
    });

    it('returns 403 when the vacancy is closed', function () {
        $vacante = Vacante::factory()->for($this->empresa)->cerrada()->create();

        $this->actingAs($this->cuenta)
            ->putJson(route('api.v1.empresa.vacantes.update', $vacante), datosDeVacante())
            ->assertForbidden();
    });

    it('returns 404 for a vacancy of another company', function () {
        $ajena = Vacante::factory()->create(['titulo' => 'Original']);

        $this->actingAs($this->cuenta)
            ->putJson(route('api.v1.empresa.vacantes.update', $ajena), datosDeVacante())
            ->assertNotFound();

        expect($ajena->fresh()->titulo)->toBe('Original');
    });
});
