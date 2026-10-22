<?php

use App\Enums\Autenticacion\Rol;
use App\Enums\Publicaciones\TipoPublicacion;
use App\Models\Autenticacion\Universidad;
use App\Models\Catalogos\Categoria;
use App\Models\Publicaciones\Comentario;
use App\Models\Publicaciones\Publicacion;
use App\Models\Publicaciones\PublicacionImagen;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->upc = Universidad::factory()->create();
    $this->yo = candidatoDe($this->upc);
    $this->companera = candidatoDe($this->upc);
    $this->categoria = Categoria::factory()->create(['nombre' => 'Tecnología']);
});

function verFeed(User $usuario, array $filtros = []): TestResponse
{
    return test()->actingAs($usuario)->getJson(route('api.v1.publicaciones.index', $filtros));
}

function publicar(User $usuario, array $cambios = []): TestResponse
{
    return test()->actingAs($usuario)->postJson(route('api.v1.publicaciones.store'), [
        'tipo' => 'GENERAL',
        'categoria_id' => test()->categoria->id,
        'contenido' => 'Comparto mi primer proyecto en Laravel.',
        ...$cambios,
    ]);
}

it('returns 401 without a session', function () {
    $this->getJson(route('api.v1.publicaciones.index'))->assertUnauthorized();
});

describe('feed', function () {
    it('lists the visible posts of the university, newest first, with what each one shows', function () {
        $vieja = Publicacion::factory()->de($this->companera)->create(['created_at' => now()->subDay()]);
        $nueva = Publicacion::factory()->de($this->companera)->for($this->categoria)->create(['contenido' => 'Hola, comunidad.']);
        Comentario::factory()->for($nueva)->create();

        verFeed($this->yo)
            ->assertOk()
            ->assertJsonPath('data.0.id', $nueva->id)
            ->assertJsonPath('data.0.contenido', 'Hola, comunidad.')
            ->assertJsonPath('data.0.tipo', 'GENERAL')
            ->assertJsonPath('data.0.abierta', null)
            ->assertJsonPath('data.0.categoria.nombre', 'Tecnología')
            ->assertJsonPath('data.0.autor.id', $this->companera->id)
            ->assertJsonPath('data.0.autor.nombres', $this->companera->perfil->nombres)
            ->assertJsonPath('data.0.autor.slug', $this->companera->perfil->slug)
            ->assertJsonPath('data.0.comentarios', 1)
            ->assertJsonPath('data.0.reacciones', 0)
            ->assertJsonPath('data.0.reaccione', false)
            ->assertJsonPath('data.0.es_mia', false)
            ->assertJsonPath('data.1.id', $vieja->id)
            ->assertJsonMissingPath('data.0.autor.email');
    });

    it('leaves out posts of other universities, hidden by moderation, deleted and of blocked people', function () {
        $visible = Publicacion::factory()->de($this->companera)->create();
        Publicacion::factory()->create();
        Publicacion::factory()->de($this->companera)->oculta()->create();
        Publicacion::factory()->de($this->companera)->create()->delete();

        $bloqueada = candidatoDe($this->upc);
        $this->yo->bloqueados()->attach($bloqueada);
        Publicacion::factory()->de($bloqueada)->create();

        $meBloqueo = candidatoDe($this->upc);
        $meBloqueo->bloqueados()->attach($this->yo);
        Publicacion::factory()->de($meBloqueo)->create();

        expect(collect(verFeed($this->yo)->assertOk()->json('data'))->pluck('id')->all())->toBe([$visible->id]);
    });

    it('shows only the posts of followed people with alcance=seguidos', function () {
        $seguida = candidatoDe($this->upc);
        $this->yo->seguidos()->attach($seguida);
        $deSeguida = Publicacion::factory()->de($seguida)->create();
        Publicacion::factory()->de($this->companera)->create();

        expect(collect(verFeed($this->yo, ['alcance' => 'seguidos'])->json('data'))->pluck('id')->all())->toBe([$deSeguida->id]);
    });

    it('filters by type, category and author', function () {
        $servicio = Publicacion::factory()->de($this->companera)->servicio()->create();
        $deCategoria = Publicacion::factory()->de($this->companera)->for($this->categoria)->create();
        $mia = Publicacion::factory()->de($this->yo)->create();

        $ids = fn (array $filtros) => collect(verFeed($this->yo, $filtros)->json('data'))->pluck('id')->all();

        expect($ids(['tipo' => 'SERVICIO']))->toBe([$servicio->id])
            ->and($ids(['categoria_id' => $this->categoria->id]))->toBe([$deCategoria->id])
            ->and($ids(['autor_id' => $this->yo->id]))->toBe([$mia->id]);
    });

    it('rejects an unknown scope or type with 422', function () {
        verFeed($this->yo, ['alcance' => 'todo', 'tipo' => 'LOGRO'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['alcance', 'tipo']);
    });

    it('lets companies of the university read the feed', function () {
        Publicacion::factory()->de($this->companera)->create();
        $empresa = Empresa::factory()->create(['usuario_id' => User::factory()->rol(Rol::Empresa)->for($this->upc)]);

        verFeed($empresa->usuario)->assertOk()->assertJsonCount(1, 'data');
    });

    it('pages with a cursor of 15', function () {
        Publicacion::factory()->count(16)->de($this->companera)->create();

        $primera = verFeed($this->yo)->assertJsonCount(15, 'data');

        $this->actingAs($this->yo)->getJson($primera->json('links.next'))->assertJsonCount(1, 'data');
    });
});

describe('store', function () {
    it('publishes a general post as visible in the author university', function () {
        $this->freezeSecond();

        publicar($this->yo)
            ->assertCreated()
            ->assertJsonPath('data.tipo', 'GENERAL')
            ->assertJsonPath('data.contenido', 'Comparto mi primer proyecto en Laravel.')
            ->assertJsonPath('data.detalles', null)
            ->assertJsonPath('data.estado', 'VISIBLE')
            ->assertJsonPath('data.es_mia', true)
            ->assertJsonPath('data.editada_en', null);

        expect(Publicacion::query()->sole())
            ->autor_id->toBe($this->yo->id)
            ->universidad_id->toBe($this->upc->id)
            ->tipo->toBe(TipoPublicacion::General);
    });

    it('publishes a service and a team search with their template fields only', function (string $tipo, array $detalles) {
        publicar($this->yo, ['tipo' => $tipo, 'detalles' => [...$detalles, 'otro_campo' => 'se ignora']])
            ->assertCreated()
            ->assertJsonPath('data.tipo', $tipo)
            ->assertJsonPath('data.abierta', true)
            ->assertJsonPath('data.detalles', $detalles);
    })->with([
        'servicio' => ['SERVICIO', ['area' => 'Tutorías de cálculo', 'modalidad' => 'REMOTA', 'disponibilidad' => 'Tardes']],
        'búsqueda de equipo' => ['BUSQUEDA_EQUIPO', ['proyecto' => 'Robot seguidor de línea', 'perfiles_requeridos' => 'Electrónica', 'cupos' => 2]],
    ]);

    it('says which template field is missing', function () {
        publicar($this->yo, ['tipo' => 'SERVICIO', 'detalles' => ['area' => 'Tutorías']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'detalles.modalidad' => 'El campo modalidad es obligatorio.',
                'detalles.disponibilidad' => 'El campo disponibilidad es obligatorio.',
            ]);
    });

    it('ignores template fields in a general post', function () {
        publicar($this->yo, ['detalles' => ['area' => 'No aplica']])
            ->assertCreated()
            ->assertJsonPath('data.detalles', null);
    });

    it('rejects empty or too long content and an inactive category', function () {
        publicar($this->yo, ['contenido' => '', 'categoria_id' => Categoria::factory()->inactiva()->create()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'contenido' => 'El campo contenido es obligatorio.',
                'categoria_id' => 'Elige una categoría activa.',
            ]);

        publicar($this->yo, ['contenido' => str_repeat('a', 3001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contenido');

        expect(Publicacion::query()->exists())->toBeFalse();
    });

    it('returns 403 with the reason to accounts that cannot publish', function (Closure $usuario, string $motivo) {
        publicar($usuario($this->upc))
            ->assertForbidden()
            ->assertJsonPath('message', $motivo);
    })->with([
        'estudiante sin perfil' => [fn (Universidad $upc) => User::factory()->for($upc)->create(), 'Completa tu perfil antes de publicar.'],
        'empresa' => [fn (Universidad $upc) => Empresa::factory()->create(['usuario_id' => User::factory()->rol(Rol::Empresa)->for($upc)])->usuario, 'Solo estudiantes y egresados pueden publicar.'],
    ]);

    it('stores up to four images in the public disk', function () {
        Storage::fake('public');

        $respuesta = publicar($this->yo, ['imagenes' => [imagenPng('uno.png'), imagenPng('dos.png')]])
            ->assertCreated()
            ->assertJsonCount(2, 'data.imagenes');

        foreach (PublicacionImagen::query()->get() as $imagen) {
            Storage::disk('public')->assertExists($imagen->ruta);
        }

        expect($respuesta->json('data.imagenes.0.url'))->toContain('/storage/publicaciones/');
    });

    it('rejects more than four images and files that are not images', function () {
        Storage::fake('public');

        publicar($this->yo, ['imagenes' => array_map(fn (int $n) => imagenPng("{$n}.png"), range(1, 5))])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['imagenes' => 'Puedes adjuntar como máximo 4 imágenes.']);

        publicar($this->yo, ['imagenes' => [UploadedFile::fake()->createWithContent('hoja-de-vida.pdf', '%PDF-1.4')]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('imagenes.0');

        expect(Publicacion::query()->exists())->toBeFalse();
    });
});

describe('show', function () {
    it('shows a visible post', function () {
        $publicacion = Publicacion::factory()->de($this->companera)->create();

        $this->actingAs($this->yo)->getJson(route('api.v1.publicaciones.show', $publicacion))
            ->assertOk()
            ->assertJsonPath('data.id', $publicacion->id);
    });

    it('returns 404 for posts of other universities, hidden, of blocked people or missing', function (Closure $publicacion) {
        $this->actingAs($this->yo)
            ->getJson('/api/v1/publicaciones/'.$publicacion($this->companera, $this->yo))
            ->assertNotFound();
    })->with([
        'otra universidad' => [fn () => Publicacion::factory()->create()->id],
        'oculta' => [fn (User $autora) => Publicacion::factory()->de($autora)->oculta()->create()->id],
        'autora bloqueada' => [function (User $autora, User $yo) {
            $yo->bloqueados()->attach($autora);

            return Publicacion::factory()->de($autora)->create()->id;
        }],
        'no existe' => [fn () => '01a14d9e-e90e-7137-ae53-be980e27cddd'],
        'no es UUID' => [fn () => '123'],
    ]);

    it('shows a hidden post to its author', function () {
        $publicacion = Publicacion::factory()->de($this->yo)->oculta()->create();

        $this->actingAs($this->yo)->getJson(route('api.v1.publicaciones.show', $publicacion))
            ->assertOk()
            ->assertJsonPath('data.estado', 'OCULTO');
    });
});

describe('update', function () {
    it('lets the author edit the text, category and details and marks it as edited, keeping the type', function () {
        $this->freezeSecond();
        $publicacion = Publicacion::factory()->de($this->yo)->servicio()->create();
        $otraCategoria = Categoria::factory()->create();

        $this->actingAs($this->yo)->putJson(route('api.v1.publicaciones.update', $publicacion), [
            'tipo' => 'GENERAL',
            'categoria_id' => $otraCategoria->id,
            'contenido' => 'Texto corregido.',
            'detalles' => ['area' => 'Física', 'modalidad' => 'PRESENCIAL', 'disponibilidad' => 'Mañanas'],
        ])
            ->assertOk()
            ->assertJsonPath('data.tipo', 'SERVICIO')
            ->assertJsonPath('data.contenido', 'Texto corregido.')
            ->assertJsonPath('data.categoria.id', $otraCategoria->id)
            ->assertJsonPath('data.detalles.area', 'Física');

        expect($publicacion->fresh()->editada_en)->toEqual(now());
    });

    it('keeps a category that was deactivated after publishing', function () {
        $categoria = Categoria::factory()->inactiva()->create();
        $publicacion = Publicacion::factory()->de($this->yo)->for($categoria)->create();

        $this->actingAs($this->yo)->putJson(route('api.v1.publicaciones.update', $publicacion), [
            'categoria_id' => $categoria->id,
            'contenido' => 'Otro texto.',
        ])->assertOk();
    });

    it('does not mark it as edited when nothing changed', function () {
        $publicacion = Publicacion::factory()->de($this->yo)->create();

        $this->actingAs($this->yo)->putJson(route('api.v1.publicaciones.update', $publicacion), [
            'categoria_id' => $publicacion->categoria_id,
            'contenido' => $publicacion->contenido,
        ])->assertOk()->assertJsonPath('data.editada_en', null);
    });

    it('returns 403 to anyone but the author and to the author of a hidden post', function () {
        $ajena = Publicacion::factory()->de($this->companera)->create();
        $ocultaMia = Publicacion::factory()->de($this->yo)->oculta()->create();
        $datos = ['categoria_id' => $this->categoria->id, 'contenido' => 'Cambio.'];

        $this->actingAs($this->yo)->putJson(route('api.v1.publicaciones.update', $ajena), $datos)->assertForbidden();
        $this->actingAs($this->yo)->putJson(route('api.v1.publicaciones.update', $ocultaMia), $datos)->assertForbidden();
    });
});

describe('destroy', function () {
    it('lets the author delete the post, which stops showing along with its comments', function () {
        $publicacion = Publicacion::factory()->de($this->yo)->create();

        $this->actingAs($this->yo)->deleteJson(route('api.v1.publicaciones.destroy', $publicacion))->assertNoContent();

        expect($publicacion->fresh()->trashed())->toBeTrue();
        $this->actingAs($this->yo)->getJson(route('api.v1.publicaciones.comentarios.index', $publicacion->id))->assertNotFound();
    });

    it('returns 403 to anyone but the author', function () {
        $publicacion = Publicacion::factory()->de($this->companera)->create();

        $this->actingAs($this->yo)->deleteJson(route('api.v1.publicaciones.destroy', $publicacion))->assertForbidden();

        expect($publicacion->fresh()->trashed())->toBeFalse();
    });
});

describe('apertura', function () {
    it('lets the author close and reopen a service', function () {
        $publicacion = Publicacion::factory()->de($this->yo)->servicio()->create();

        $this->actingAs($this->yo)->patchJson(route('api.v1.publicaciones.apertura', $publicacion), ['abierta' => false])
            ->assertOk()
            ->assertJsonPath('data.abierta', false);

        $this->actingAs($this->yo)->patchJson(route('api.v1.publicaciones.apertura', $publicacion), ['abierta' => true])
            ->assertJsonPath('data.abierta', true);
    });

    it('rejects closing a general post with 422', function () {
        $publicacion = Publicacion::factory()->de($this->yo)->create();

        $this->actingAs($this->yo)->patchJson(route('api.v1.publicaciones.apertura', $publicacion), ['abierta' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['abierta' => 'Solo los servicios y las búsquedas de equipo se abren o se cierran.']);
    });

    it('returns 403 to anyone but the author', function () {
        $publicacion = Publicacion::factory()->de($this->companera)->busquedaEquipo()->create();

        $this->actingAs($this->yo)->patchJson(route('api.v1.publicaciones.apertura', $publicacion), ['abierta' => false])
            ->assertForbidden();
    });
});
