<?php

namespace Database\Seeders;

use App\Models\Autenticacion\Universidad;
use Illuminate\Database\Seeder;

class UniversidadSeeder extends Seeder
{
    public const string DOMINIO_UPC = 'unicesar.edu.co';

    /**
     * Registra la Universidad Popular del Cesar. Se puede correr varias veces
     * sin duplicarla.
     */
    public function run(): void
    {
        Universidad::query()->updateOrCreate(
            ['dominio_correo' => self::DOMINIO_UPC],
            ['nombre' => 'Universidad Popular del Cesar', 'activa' => true],
        );
    }
}
