<?php

use App\Enums\Autenticacion\Rol;
use App\Models\Autenticacion\Universidad;
use App\Models\Catalogos\Carrera;
use App\Models\Perfiles\Perfil;
use App\Models\User;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->upc = Universidad::factory()->create();
    $this->carrera = Carrera::factory()->for($this->upc)->create(['nombre' => 'Ingeniería de Sistemas']);
    $this->estudiante = User::factory()->for($this->upc)->create();
});

function datosDePerfil(array $cambios = []): array
{
    return [
        'nombres' => 'José David',
        'apellidos' => 'Castro Muñoz',
        'titular' => 'Estudiante de Ingeniería de Sistemas',
        'descripcion' => 'Me interesa el desarrollo web.',
        'carrera_id' => test()->carrera->id,
        'semestre' => 7,
        'ciudad' => 'Valledupar',
        'intereses' => ['Desarrollo web', 'Datos'],
        ...$cambios,
    ];
}

function guardarPerfil(User $usuario, array $datos): TestResponse
{
    return test()->actingAs($usuario)->putJson(route('api.v1.me.perfil.update'), $datos);
}

describe('show', function () {
    it('returns 401 when there is no session', function () {
        $this->getJson(route('api.v1.me.perfil.show'))->assertUnauthorized();
    });

    it('returns 403 to roles without a professional profile', function (Rol $rol) {
        $this->actingAs(User::factory()->for($this->upc)->rol($rol)->create())
            ->getJson(route('api.v1.me.perfil.show'))
            ->assertForbidden();
    })->with([Rol::Empresa, Rol::Admin]);

    it('returns 404 with a code when the profile was not created yet', function () {
        $this->actingAs($this->estudiante)
            ->getJson(route('api.v1.me.perfil.show'))
            ->assertNotFound()
            ->assertJsonPath('codigo', 'perfil_no_creado');
    });

    it('returns the own profile with its program', function () {
        $perfil = Perfil::factory()->for($this->estudiante, 'usuario')->create(['carrera_id' => $this->carrera->id]);

        $this->actingAs($this->estudiante)
            ->getJson(route('api.v1.me.perfil.show'))
            ->assertOk()
            ->assertJsonPath('data.id', $perfil->id)
            ->assertJsonPath('data.carrera.nombre', 'Ingeniería de Sistemas')
            ->assertJsonPath('data.habilidades', []);
    });

    it('includes the own follow counters', function () {
        Perfil::factory()->for($this->estudiante, 'usuario')->create(['carrera_id' => $this->carrera->id]);
        User::factory()->for($this->upc)->create()->seguidos()->attach($this->estudiante);

        $this->actingAs($this->estudiante)
            ->getJson(route('api.v1.me.perfil.show'))
            ->assertJsonPath('red', ['seguidores' => 1, 'seguidos' => 0]);
    });
});

describe('update', function () {
    it('creates the profile with 201 and a slug from the full name', function () {
        guardarPerfil($this->estudiante, datosDePerfil())
            ->assertCreated()
            ->assertJsonPath('data.slug', 'jose-david-castro-munoz')
            ->assertJsonPath('data.semestre', 7)
            ->assertJsonPath('data.intereses', ['Desarrollo web', 'Datos']);

        expect($this->estudiante->fresh()->perfil)
            ->nombres->toBe('José David')
            ->carrera_id->toBe($this->carrera->id);
    });

    it('updates the profile with 200 and keeps the slug when the name changes', function () {
        guardarPerfil($this->estudiante, datosDePerfil())->assertCreated();

        guardarPerfil($this->estudiante, datosDePerfil(['nombres' => 'José', 'titular' => 'Desarrollador junior']))
            ->assertOk()
            ->assertJsonPath('data.titular', 'Desarrollador junior')
            ->assertJsonPath('data.slug', 'jose-david-castro-munoz');

        expect(Perfil::query()->count())->toBe(1);
    });

    it('adds a numeric suffix when another profile already uses the slug', function () {
        Perfil::factory()->create(['slug' => 'jose-david-castro-munoz']);

        guardarPerfil($this->estudiante, datosDePerfil())->assertJsonPath('data.slug', 'jose-david-castro-munoz-2');
    });

    it('requires program and semester for a student', function () {
        guardarPerfil($this->estudiante, datosDePerfil(['carrera_id' => null, 'semestre' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'carrera_id' => 'El campo carrera es obligatorio.',
                'semestre' => 'El campo semestre es obligatorio.',
            ]);
    });

    it('requires the graduation year and rejects the semester for a graduate', function () {
        $egresado = User::factory()->for($this->upc)->rol(Rol::Egresado)->create();

        guardarPerfil($egresado, datosDePerfil())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'anio_grado' => 'El campo año de grado es obligatorio.',
                'semestre' => 'Los egresados no registran semestre; indica tu año de grado.',
            ]);

        guardarPerfil($egresado, datosDePerfil(['semestre' => null, 'anio_grado' => 2022]))
            ->assertCreated()
            ->assertJsonPath('data.anio_grado', 2022);
    });

    it('rejects the graduation year for a student', function () {
        guardarPerfil($this->estudiante, datosDePerfil(['anio_grado' => 2022]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['anio_grado' => 'Solo los egresados registran año de grado.']);
    });

    it('rejects a program that is inactive or from another university', function (Closure $carrera) {
        guardarPerfil($this->estudiante, datosDePerfil(['carrera_id' => $carrera()->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['carrera_id' => 'Elige una carrera activa de tu universidad.']);
    })->with([
        'carrera inactiva' => [fn () => Carrera::factory()->for(test()->upc)->inactiva()->create()],
        'carrera de otra universidad' => [fn () => Carrera::factory()->create()],
    ]);

    it('keeps an inactive program that the profile already had', function () {
        guardarPerfil($this->estudiante, datosDePerfil())->assertCreated();
        $this->carrera->update(['activa' => false]);

        guardarPerfil($this->estudiante, datosDePerfil(['titular' => 'Nuevo titular']))->assertOk();
    });

    it('enforces the maximum lengths', function () {
        guardarPerfil($this->estudiante, datosDePerfil([
            'titular' => str_repeat('a', 121),
            'descripcion' => str_repeat('a', 1001),
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'titular' => 'El campo titular no puede tener más de 120 caracteres.',
                'descripcion' => 'El campo descripción no puede tener más de 1000 caracteres.',
            ]);
    });

    it('limits interests to 10 without repeated values', function () {
        guardarPerfil($this->estudiante, datosDePerfil(['intereses' => array_map(fn (int $n) => "Interés {$n}", range(1, 11))]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['intereses' => 'Puedes agregar hasta 10 intereses.']);

        guardarPerfil($this->estudiante, datosDePerfil(['intereses' => ['Datos', 'datos']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('intereses.1');
    });

    it('returns 403 to a company account', function () {
        guardarPerfil(User::factory()->for($this->upc)->rol(Rol::Empresa)->create(), datosDePerfil())
            ->assertForbidden();

        expect(Perfil::query()->exists())->toBeFalse();
    });
});
