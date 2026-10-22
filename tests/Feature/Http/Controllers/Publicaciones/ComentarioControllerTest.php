<?php

use App\Enums\Autenticacion\Rol;
use App\Models\Autenticacion\Universidad;
use App\Models\Publicaciones\Comentario;
use App\Models\Publicaciones\Publicacion;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->upc = Universidad::factory()->create();
    $this->yo = candidatoDe($this->upc);
    $this->companera = candidatoDe($this->upc);
    $this->publicacion = Publicacion::factory()->de($this->companera)->create();
});

function comentar(User $usuario, Publicacion $publicacion, array $datos = ['contenido' => '¡Muy buen proyecto!']): TestResponse
{
    return test()->actingAs($usuario)->postJson(route('api.v1.publicaciones.comentarios.store', $publicacion), $datos);
}

describe('index', function () {
    it('lists the comments oldest first, without hidden ones nor those of blocked people', function () {
        $primero = Comentario::factory()->for($this->publicacion)->de($this->yo)->create(['created_at' => now()->subHour()]);
        $segundo = Comentario::factory()->for($this->publicacion)->create();
        Comentario::factory()->for($this->publicacion)->oculto()->create();

        $bloqueada = candidatoDe($this->upc);
        $this->yo->bloqueados()->attach($bloqueada);
        Comentario::factory()->for($this->publicacion)->de($bloqueada)->create();

        $this->actingAs($this->yo)
            ->getJson(route('api.v1.publicaciones.comentarios.index', $this->publicacion))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $primero->id)
            ->assertJsonPath('data.0.es_mio', true)
            ->assertJsonPath('data.0.autor.nombres', $this->yo->perfil->nombres)
            ->assertJsonPath('data.1.id', $segundo->id)
            ->assertJsonPath('data.1.es_mio', false);
    });

    it('returns 404 for a post the user cannot see', function () {
        $oculta = Publicacion::factory()->de($this->companera)->oculta()->create();

        $this->actingAs($this->yo)
            ->getJson(route('api.v1.publicaciones.comentarios.index', $oculta))
            ->assertNotFound();
    });
});

describe('store', function () {
    it('adds the comment under the post', function () {
        comentar($this->yo, $this->publicacion)
            ->assertCreated()
            ->assertJsonPath('data.contenido', '¡Muy buen proyecto!')
            ->assertJsonPath('data.autor.id', $this->yo->id)
            ->assertJsonPath('data.editado_en', null);

        expect(Comentario::query()->sole())
            ->publicacion_id->toBe($this->publicacion->id)
            ->autor_id->toBe($this->yo->id);
    });

    it('rejects an empty or too long comment', function (string $contenido) {
        comentar($this->yo, $this->publicacion, ['contenido' => $contenido])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contenido');
    })->with(['vacío' => '', 'más de 1000 caracteres' => str_repeat('a', 1001)]);

    it('returns 403 with the reason to accounts that cannot comment', function (Closure $usuario, string $motivo) {
        comentar($usuario($this->upc), $this->publicacion)
            ->assertForbidden()
            ->assertJsonPath('message', $motivo);
    })->with([
        'estudiante sin perfil' => [fn (Universidad $upc) => User::factory()->for($upc)->create(), 'Completa tu perfil antes de comentar.'],
        'empresa' => [fn (Universidad $upc) => Empresa::factory()->create(['usuario_id' => User::factory()->rol(Rol::Empresa)->for($upc)])->usuario, 'Solo estudiantes y egresados pueden comentar las publicaciones.'],
    ]);

    it('returns 404 when the author of the post blocked the user', function () {
        $this->companera->bloqueados()->attach($this->yo);

        comentar($this->yo, $this->publicacion)->assertNotFound();
    });

    it('counts only the comments the user can see in the feed', function () {
        Comentario::factory()->for($this->publicacion)->create();
        Comentario::factory()->for($this->publicacion)->oculto()->create();

        $this->actingAs($this->yo)
            ->getJson(route('api.v1.publicaciones.show', $this->publicacion))
            ->assertJsonPath('data.comentarios', 1);
    });
});

describe('update', function () {
    it('lets the author edit the comment and marks it as edited', function () {
        $this->freezeSecond();
        $comentario = Comentario::factory()->for($this->publicacion)->de($this->yo)->create();

        $this->actingAs($this->yo)
            ->putJson(route('api.v1.comentarios.update', $comentario), ['contenido' => 'Corregido.'])
            ->assertOk()
            ->assertJsonPath('data.contenido', 'Corregido.');

        expect($comentario->fresh()->editado_en)->toEqual(now());
    });

    it('returns 403 to anyone but the author and for hidden comments', function () {
        $ajeno = Comentario::factory()->for($this->publicacion)->create();
        $ocultoMio = Comentario::factory()->for($this->publicacion)->de($this->yo)->oculto()->create();

        $this->actingAs($this->yo)->putJson(route('api.v1.comentarios.update', $ajeno), ['contenido' => 'x'])->assertForbidden();
        $this->actingAs($this->yo)->putJson(route('api.v1.comentarios.update', $ocultoMio), ['contenido' => 'x'])->assertForbidden();
    });

    it('returns 404 when the post was deleted', function () {
        $comentario = Comentario::factory()->for($this->publicacion)->de($this->yo)->create();
        $this->publicacion->delete();

        $this->actingAs($this->yo)
            ->putJson(route('api.v1.comentarios.update', $comentario), ['contenido' => 'x'])
            ->assertNotFound();
    });
});

describe('destroy', function () {
    it('lets the author delete the comment', function () {
        $comentario = Comentario::factory()->for($this->publicacion)->de($this->yo)->create();

        $this->actingAs($this->yo)->deleteJson(route('api.v1.comentarios.destroy', $comentario))->assertNoContent();

        expect($comentario->fresh()->trashed())->toBeTrue();
    });

    it('returns 403 to anyone but the author', function () {
        $comentario = Comentario::factory()->for($this->publicacion)->create();

        $this->actingAs($this->yo)->deleteJson(route('api.v1.comentarios.destroy', $comentario))->assertForbidden();
    });
});
