<?php

namespace Database\Seeders;

use App\Models\Autenticacion\Universidad;
use Illuminate\Database\Seeder;

class UniversidadSeeder extends Seeder
{
    /**
     * Registra la Universidad Popular del Cesar. Se puede correr varias veces
     * sin duplicarla.
     */
    public function run(): void
    {
        Universidad::query()->updateOrCreate(
            ['dominio_correo' => 'unicesar.edu.co'],
            ['nombre' => 'Universidad Popular del Cesar', 'activa' => true],
        );
    }
}
