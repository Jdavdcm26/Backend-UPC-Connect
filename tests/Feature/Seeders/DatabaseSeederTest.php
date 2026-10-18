<?php

use App\Models\Autenticacion\Universidad;
use App\Models\Catalogos\Carrera;
use App\Models\Catalogos\Categoria;
use App\Models\Catalogos\Habilidad;
use App\Models\User;

it('loads the UPC with its catalogs and test user without duplicates when run twice', function () {
    $this->seed();
    $conteos = fn (): array => [
        Universidad::query()->count(),
        Carrera::query()->count(),
        Habilidad::query()->count(),
        Categoria::query()->count(),
        User::query()->count(),
    ];
    $primeraVez = $conteos();

    $this->seed();

    expect($conteos())->toBe($primeraVez)
        ->and(Carrera::query()->where('nombre', 'Ingeniería de Sistemas')->sole()->universidad->dominio_correo)->toBe('unicesar.edu.co')
        ->and(Habilidad::query()->where('nombre', 'Trabajo en equipo')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'prueba@unicesar.edu.co')->exists())->toBeTrue();
});
