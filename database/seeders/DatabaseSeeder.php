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
        $this->call(UniversidadSeeder::class);

        User::factory()
            ->for(Universidad::query()->where('dominio_correo', 'unicesar.edu.co')->sole())
            ->create([
                'name' => 'Usuario de prueba',
                'email' => 'prueba@unicesar.edu.co',
            ]);
    }
}
