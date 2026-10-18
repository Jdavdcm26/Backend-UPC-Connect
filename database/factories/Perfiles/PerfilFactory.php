<?php

namespace Database\Factories\Perfiles;

use App\Models\Catalogos\Carrera;
use App\Models\Perfiles\Perfil;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Perfil>
 */
class PerfilFactory extends Factory
{
    /**
     * Define the model's default state: a student's profile.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombres = fake()->firstName();
        $apellidos = fake()->lastName();

        return [
            'usuario_id' => User::factory(),
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'slug' => Str::slug("{$nombres} {$apellidos}").'-'.Str::lower(Str::random(6)),
            'titular' => fake()->sentence(4),
            'descripcion' => fake()->paragraph(),
            'carrera_id' => fn (array $atributos) => Carrera::factory()->create([
                'universidad_id' => User::query()->withoutGlobalScopes()->findOrFail($atributos['usuario_id'])->universidad_id,
            ]),
            'semestre' => fake()->numberBetween(1, 10),
            'anio_grado' => null,
            'ciudad' => 'Valledupar',
            'intereses' => ['Desarrollo web'],
        ];
    }
}
