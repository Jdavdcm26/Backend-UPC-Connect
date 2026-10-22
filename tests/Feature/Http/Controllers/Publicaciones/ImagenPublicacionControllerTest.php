<?php

use App\Models\Autenticacion\Universidad;
use App\Models\Publicaciones\Publicacion;
use App\Models\Publicaciones\PublicacionImagen;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->upc = Universidad::factory()->create();
    $this->yo = candidatoDe($this->upc);
    $this->publicacion = Publicacion::factory()->de($this->yo)->create();
});

function imagenGuardada(Publicacion $publicacion): PublicacionImagen
{
    $ruta = imagenPng()->store("publicaciones/{$publicacion->id}", 'public');

    return $publicacion->imagenes()->create(['ruta' => $ruta]);
}

it('adds images to the author post', function () {
    $this->actingAs($this->yo)
        ->post(route('api.v1.publicaciones.imagenes.store', $this->publicacion), ['imagenes' => [imagenPng()]], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonCount(1, 'data.imagenes');

    Storage::disk('public')->assertExists(PublicacionImagen::query()->sole()->ruta);
});

it('keeps the total at four images', function () {
    foreach (range(1, 3) as $n) {
        imagenGuardada($this->publicacion);
    }

    $this->actingAs($this->yo)
        ->post(route('api.v1.publicaciones.imagenes.store', $this->publicacion), ['imagenes' => [imagenPng(), imagenPng()]], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['imagenes' => 'Una publicación tiene como máximo 4 imágenes.']);
});

it('removes an image and its file', function () {
    $imagen = imagenGuardada($this->publicacion);

    $this->actingAs($this->yo)
        ->deleteJson(route('api.v1.publicaciones.imagenes.destroy', [$this->publicacion, $imagen]))
        ->assertOk()
        ->assertJsonCount(0, 'data.imagenes');

    Storage::disk('public')->assertMissing($imagen->ruta);
    expect(PublicacionImagen::query()->exists())->toBeFalse();
});

it('returns 404 for an image of another post', function () {
    $otra = Publicacion::factory()->de($this->yo)->create();
    $imagen = imagenGuardada($otra);

    $this->actingAs($this->yo)
        ->deleteJson(route('api.v1.publicaciones.imagenes.destroy', [$this->publicacion, $imagen]))
        ->assertNotFound();
});

it('returns 403 to anyone but the author', function () {
    $imagen = imagenGuardada($this->publicacion);
    $otra = candidatoDe($this->upc);

    $this->actingAs($otra)
        ->post(route('api.v1.publicaciones.imagenes.store', $this->publicacion), ['imagenes' => [imagenPng()]], ['Accept' => 'application/json'])
        ->assertForbidden();

    $this->actingAs($otra)
        ->deleteJson(route('api.v1.publicaciones.imagenes.destroy', [$this->publicacion, $imagen]))
        ->assertForbidden();
});
