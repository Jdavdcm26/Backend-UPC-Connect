<?php

use App\Models\Autenticacion\Universidad;
use Database\Seeders\UniversidadSeeder;

it('registers the Universidad Popular del Cesar only once when run twice', function () {
    $this->seed(UniversidadSeeder::class);
    $this->seed(UniversidadSeeder::class);

    expect(Universidad::query()->sole())
        ->nombre->toBe('Universidad Popular del Cesar')
        ->dominio_correo->toBe('unicesar.edu.co')
        ->activa->toBeTrue();
});
