<?php

use App\Enums\Publicaciones\TipoPublicacion;
use App\Models\Networking\Seguidor;
use App\Models\Publicaciones\Comentario;
use App\Models\Publicaciones\Publicacion;
use App\Models\Publicaciones\Reaccion;
use Database\Seeders\DatosDemostracionSeeder;
use Database\Seeders\PublicacionesDemostracionSeeder;

it('loads posts of every type with reactions and follows along with the demo data', function () {
    $this->seed(DatosDemostracionSeeder::class);

    expect(Publicacion::query()->count())->toBe(10)
        ->and(Publicacion::query()->toBase()->distinct()->orderBy('tipo')->pluck('tipo')->all())
        ->toBe(['BUSQUEDA_EQUIPO', 'GENERAL', 'SERVICIO'])
        ->and(Publicacion::query()->where('tipo', TipoPublicacion::BusquedaEquipo)->where('abierta', false)->exists())->toBeTrue()
        ->and(Reaccion::query()->count())->toBeGreaterThanOrEqual(10)
        ->and(Seguidor::query()->count())->toBeGreaterThanOrEqual(30)
        ->and(Comentario::query()->exists())->toBeTrue();
});

it('can run on its own over existing demo data without duplicating posts', function () {
    $this->seed(DatosDemostracionSeeder::class);
    $this->seed(PublicacionesDemostracionSeeder::class);

    expect(Publicacion::query()->count())->toBe(10);
});

it('does nothing when there are no candidates yet', function () {
    $this->seed(PublicacionesDemostracionSeeder::class);

    expect(Publicacion::query()->exists())->toBeFalse();
});
