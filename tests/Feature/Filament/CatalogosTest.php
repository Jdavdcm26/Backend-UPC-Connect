<?php

use App\Enums\Autenticacion\Rol;
use App\Enums\Catalogos\NivelCarrera;
use App\Enums\Catalogos\TipoHabilidad;
use App\Filament\Resources\Carreras\Pages\ManageCarreras;
use App\Filament\Resources\Categorias\Pages\ManageCategorias;
use App\Filament\Resources\Habilidades\Pages\ManageHabilidades;
use App\Models\Administracion\Auditoria;
use App\Models\Autenticacion\Universidad;
use App\Models\Catalogos\Carrera;
use App\Models\Catalogos\Categoria;
use App\Models\Catalogos\Habilidad;
use App\Models\Perfiles\Perfil;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->upc = Universidad::factory()->create();
    $this->admin = User::factory()->for($this->upc)->rol(Rol::Admin)->create();
    $this->actingAs($this->admin);
});

describe('carreras', function () {
    it('lists only the programs of the administrator university', function () {
        $propia = Carrera::factory()->for($this->upc)->create();
        $ajena = Carrera::factory()->create();

        Livewire::test(ManageCarreras::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$propia])
            ->assertCanNotSeeTableRecords([$ajena]);
    });

    it('creates a program in the administrator university', function () {
        Livewire::test(ManageCarreras::class)
            ->callAction('create', ['nombre' => 'Ingeniería Civil', 'facultad' => 'Ingenierías', 'nivel' => NivelCarrera::Pregrado->value, 'activa' => true])
            ->assertHasNoActionErrors();

        $carrera = Carrera::query()->where('nombre', 'Ingeniería Civil')->sole();

        expect($carrera)
            ->universidad_id->toBe($this->upc->id)
            ->activa->toBeTrue()
            ->and(Auditoria::query()->where('accion', 'carrera.creada')->sole())
            ->actor_id->toBe($this->admin->id)
            ->entidad_id->toBe($carrera->id);
    });

    it('rejects a program name repeated within the university', function () {
        Carrera::factory()->for($this->upc)->create(['nombre' => 'Derecho']);

        Livewire::test(ManageCarreras::class)
            ->callAction('create', ['nombre' => 'Derecho', 'facultad' => 'Derecho', 'nivel' => NivelCarrera::Pregrado->value])
            ->assertHasActionErrors(['nombre' => 'unique']);
    });

    it('deactivates a program from the edit action', function () {
        $carrera = Carrera::factory()->for($this->upc)->create();

        Livewire::test(ManageCarreras::class)
            ->callAction(TestAction::make('edit')->table($carrera), ['activa' => false])
            ->assertHasNoActionErrors();

        expect($carrera->fresh()->activa)->toBeFalse()
            ->and(Auditoria::query()->where('accion', 'carrera.actualizada')->sole()->detalle)
            ->toBe(['cambios' => ['activa' => false]]);
    });

    it('does not offer deleting programs', function () {
        $carrera = Carrera::factory()->for($this->upc)->create();

        Livewire::test(ManageCarreras::class)->assertActionDoesNotExist(TestAction::make('delete')->table($carrera));
    });
});

describe('habilidades', function () {
    it('creates a skill and rejects a repeated name', function () {
        Livewire::test(ManageHabilidades::class)
            ->callAction('create', ['nombre' => 'Docker', 'tipo' => TipoHabilidad::Tecnica->value, 'activa' => true])
            ->assertHasNoActionErrors()
            ->callAction('create', ['nombre' => 'Docker', 'tipo' => TipoHabilidad::Tecnica->value])
            ->assertHasActionErrors(['nombre' => 'unique']);

        expect(Habilidad::query()->where('nombre', 'Docker')->count())->toBe(1);
    });

    it('merges a duplicated skill into another from the table', function () {
        $origen = Habilidad::factory()->create(['nombre' => 'Javascript']);
        $destino = Habilidad::factory()->create(['nombre' => 'JavaScript']);
        $perfil = Perfil::factory()->create();
        $perfil->habilidades()->attach($origen);

        Livewire::test(ManageHabilidades::class)
            ->callAction(TestAction::make('fusionar')->table($origen), ['destino_id' => $destino->id])
            ->assertHasNoActionErrors()
            ->assertNotified();

        expect($perfil->habilidades()->pluck('habilidades.id')->all())->toBe([$destino->id])
            ->and($origen->fresh()->activa)->toBeFalse()
            ->and(Auditoria::query()->where('accion', 'habilidad.fusionada')->sole())
            ->actor_id->toBe($this->admin->id)
            ->entidad_id->toBe($origen->id)
            ->detalle->toMatchArray(['destino_id' => $destino->id, 'perfiles' => 1]);
    });

    it('hides the merge action for an inactive skill', function () {
        $inactiva = Habilidad::factory()->inactiva()->create();

        Livewire::test(ManageHabilidades::class)->assertActionHidden(TestAction::make('fusionar')->table($inactiva));
    });
});

describe('categorías', function () {
    it('creates and deactivates a category', function () {
        Livewire::test(ManageCategorias::class)
            ->callAction('create', ['nombre' => 'Convocatoria', 'activa' => true])
            ->assertHasNoActionErrors();

        $categoria = Categoria::query()->where('nombre', 'Convocatoria')->sole();

        Livewire::test(ManageCategorias::class)
            ->callAction(TestAction::make('edit')->table($categoria), ['activa' => false])
            ->assertHasNoActionErrors();

        expect($categoria->fresh()->activa)->toBeFalse()
            ->and(Auditoria::query()->where('entidad_id', $categoria->id)->pluck('accion')->all())
            ->toEqualCanonicalizing(['categoria.creada', 'categoria.actualizada']);
    });
});

it('returns 403 on the catalog pages to accounts that are not administrators', function (string $ruta) {
    $this->actingAs(User::factory()->for($this->upc)->create())
        ->get($ruta)
        ->assertForbidden();
})->with(['/admin/carreras', '/admin/habilidades', '/admin/categorias']);
