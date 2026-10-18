<?php

use App\Actions\Perfiles\AgregarHabilidad;
use App\Models\Catalogos\Habilidad;
use App\Models\Perfiles\Perfil;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->perfil = Perfil::factory()->create();
    $this->candidato = $this->perfil->usuario;
    $this->habilidad = Habilidad::factory()->create(['nombre' => 'Python']);
});

function agregarHabilidad(User $usuario, array $datos): TestResponse
{
    return test()->actingAs($usuario)->postJson(route('api.v1.me.perfil.habilidades.store'), $datos);
}

describe('store', function () {
    it('adds a catalog skill with its level and returns the updated profile', function () {
        agregarHabilidad($this->candidato, ['habilidad_id' => $this->habilidad->id, 'nivel' => 'INTERMEDIO'])
            ->assertOk()
            ->assertJsonPath('data.habilidades.0.nombre', 'Python')
            ->assertJsonPath('data.habilidades.0.nivel', 'INTERMEDIO');

        expect($this->perfil->habilidades()->pluck('habilidades.id')->all())->toBe([$this->habilidad->id]);
    });

    it('adds a skill without a level', function () {
        agregarHabilidad($this->candidato, ['habilidad_id' => $this->habilidad->id])
            ->assertOk()
            ->assertJsonPath('data.habilidades.0.nivel', null);
    });

    it('rejects a skill the profile already has', function () {
        agregarHabilidad($this->candidato, ['habilidad_id' => $this->habilidad->id])->assertOk();

        agregarHabilidad($this->candidato, ['habilidad_id' => $this->habilidad->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['habilidad_id' => 'Ya tienes esta habilidad en tu perfil.']);

        expect($this->perfil->habilidades()->count())->toBe(1);
    });

    it('rejects inactive or unknown skills and invalid levels', function (Closure $datos, string $campo) {
        agregarHabilidad($this->candidato, $datos())
            ->assertUnprocessable()
            ->assertJsonValidationErrors($campo);

        expect($this->perfil->habilidades()->exists())->toBeFalse();
    })->with([
        'habilidad inactiva' => [fn () => ['habilidad_id' => Habilidad::factory()->inactiva()->create()->id], 'habilidad_id'],
        'habilidad inexistente' => [fn () => ['habilidad_id' => '01a14d9e-e90e-7137-ae53-be980e27cddd'], 'habilidad_id'],
        'nivel desconocido' => [fn () => ['habilidad_id' => test()->habilidad->id, 'nivel' => 'EXPERTO'], 'nivel'],
    ]);

    it('limits a profile to 30 skills', function () {
        $this->perfil->habilidades()->attach(Habilidad::factory()->count(AgregarHabilidad::MAXIMO_POR_PERFIL)->create());

        agregarHabilidad($this->candidato, ['habilidad_id' => $this->habilidad->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['habilidad_id' => 'Puedes tener hasta 30 habilidades. Quita alguna para agregar otra.']);
    });

    it('returns 404 with a code when the profile was not created yet', function () {
        agregarHabilidad(User::factory()->create(), ['habilidad_id' => $this->habilidad->id])
            ->assertNotFound()
            ->assertJsonPath('codigo', 'perfil_no_creado');
    });

    it('returns 403 to companies', function () {
        agregarHabilidad(Empresa::factory()->create()->usuario, ['habilidad_id' => $this->habilidad->id])->assertForbidden();
    });
});

describe('destroy', function () {
    it('removes a skill from the own profile', function () {
        $otra = Habilidad::factory()->create(['nombre' => 'SQL']);
        $this->perfil->habilidades()->attach([$this->habilidad->id, $otra->id]);

        $this->actingAs($this->candidato)
            ->deleteJson(route('api.v1.me.perfil.habilidades.destroy', $this->habilidad->id))
            ->assertOk()
            ->assertJsonPath('data.habilidades.*.nombre', ['SQL']);
    });

    it('answers the same when the skill was not in the profile', function () {
        $this->actingAs($this->candidato)
            ->deleteJson(route('api.v1.me.perfil.habilidades.destroy', $this->habilidad->id))
            ->assertOk()
            ->assertJsonPath('data.habilidades', []);
    });

    it('lets the person remove a skill that was deactivated in the catalog', function () {
        $this->perfil->habilidades()->attach($this->habilidad);
        $this->habilidad->update(['activa' => false]);

        $this->actingAs($this->candidato)
            ->deleteJson(route('api.v1.me.perfil.habilidades.destroy', $this->habilidad->id))
            ->assertOk();

        expect($this->perfil->habilidades()->exists())->toBeFalse();
    });

    it('returns 404 for an id that is not a UUID', function () {
        $this->actingAs($this->candidato)->deleteJson('/api/v1/me/perfil/habilidades/123')->assertNotFound();
    });
});
