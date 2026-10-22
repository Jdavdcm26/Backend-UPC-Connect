<?php

use App\Enums\Autenticacion\Rol;
use App\Enums\Publicaciones\EstadoModeracion;
use App\Filament\Resources\Comentarios\Pages\ManageComentarios;
use App\Filament\Resources\Publicaciones\Pages\ManagePublicaciones;
use App\Models\Administracion\Auditoria;
use App\Models\Autenticacion\Universidad;
use App\Models\Publicaciones\Comentario;
use App\Models\Publicaciones\Publicacion;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->upc = Universidad::factory()->create();
    $this->admin = User::factory()->for($this->upc)->rol(Rol::Admin)->conDobleFactor()->create();
    $this->publicacion = Publicacion::factory()->de(candidatoDe($this->upc))->create();
});

describe('publicaciones', function () {
    it('lists only the posts of the administrator university', function () {
        $ajena = Publicacion::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(ManagePublicaciones::class)
            ->assertCanSeeTableRecords([$this->publicacion])
            ->assertCanNotSeeTableRecords([$ajena]);
    });

    it('hides a post with a reason, audits it and takes it out of the feed', function () {
        $this->actingAs($this->admin);

        Livewire::test(ManagePublicaciones::class)
            ->callAction(TestAction::make('ocultar')->table($this->publicacion), ['motivo' => 'Contenido ofensivo.'])
            ->assertHasNoActionErrors();

        expect($this->publicacion->fresh()->estado)->toBe(EstadoModeracion::Oculto)
            ->and(Auditoria::query()->where('accion', 'publicacion.ocultado')->sole())
            ->actor_id->toBe($this->admin->id)
            ->entidad_id->toBe($this->publicacion->id)
            ->detalle->toBe(['motivo' => 'Contenido ofensivo.']);

        $this->actingAs(candidatoDe($this->upc))
            ->getJson(route('api.v1.publicaciones.index'))
            ->assertJsonCount(0, 'data');
    });

    it('requires a reason to hide a post', function () {
        $this->actingAs($this->admin);

        Livewire::test(ManagePublicaciones::class)
            ->callAction(TestAction::make('ocultar')->table($this->publicacion), ['motivo' => ''])
            ->assertHasActionErrors(['motivo' => 'required']);

        expect($this->publicacion->fresh()->estado)->toBe(EstadoModeracion::Visible);
    });

    it('shows a hidden post again and offers each action only when it applies', function () {
        $this->publicacion->forceFill(['estado' => EstadoModeracion::Oculto])->save();

        $this->actingAs($this->admin);

        Livewire::test(ManagePublicaciones::class)
            ->assertActionHidden(TestAction::make('ocultar')->table($this->publicacion))
            ->callAction(TestAction::make('mostrar')->table($this->publicacion))
            ->assertHasNoActionErrors();

        expect($this->publicacion->fresh()->estado)->toBe(EstadoModeracion::Visible)
            ->and(Auditoria::query()->where('accion', 'publicacion.mostrado')->exists())->toBeTrue();
    });
});

describe('comentarios', function () {
    it('lists only the comments of posts of the administrator university and hides one with a reason', function () {
        $comentario = Comentario::factory()->for($this->publicacion)->create();
        $ajeno = Comentario::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(ManageComentarios::class)
            ->assertCanSeeTableRecords([$comentario])
            ->assertCanNotSeeTableRecords([$ajeno])
            ->callAction(TestAction::make('ocultar')->table($comentario), ['motivo' => 'Spam.'])
            ->assertHasNoActionErrors();

        expect($comentario->fresh()->estado)->toBe(EstadoModeracion::Oculto)
            ->and(Auditoria::query()->where('accion', 'comentario.ocultado')->sole()->detalle)->toBe(['motivo' => 'Spam.']);
    });
});
