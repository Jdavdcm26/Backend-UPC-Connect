<?php

namespace Database\Seeders;

use App\Models\Catalogos\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Registra las categorías de publicación iniciales sin duplicarlas.
     */
    public function run(): void
    {
        foreach (['General', 'Servicio o asesoría', 'Búsqueda de integrantes', 'Logro'] as $nombre) {
            Categoria::query()->firstOrCreate(['nombre' => $nombre]);
        }
    }
}
