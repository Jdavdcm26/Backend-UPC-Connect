<?php

namespace Database\Seeders;

use App\Models\Autenticacion\Universidad;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UniversidadSeeder::class,
            CarreraSeeder::class,
            HabilidadSeeder::class,
            CategoriaSeeder::class,
        ]);

        if (User::query()->where('email', 'prueba@unicesar.edu.co')->doesntExist()) {
            User::factory()
                ->for(Universidad::query()->where('dominio_correo', UniversidadSeeder::DOMINIO_UPC)->sole())
                ->create([
                    'name' => 'Usuario de prueba',
                    'email' => 'prueba@unicesar.edu.co',
                ]);
        }
    }
}
