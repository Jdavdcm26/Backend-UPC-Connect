<?php

namespace Database\Factories\Catalogos;

use App\Enums\Catalogos\NivelCarrera;
use App\Models\Autenticacion\Universidad;
use App\Models\Catalogos\Carrera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Carrera>
 */
class CarreraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'universidad_id' => Universidad::factory(),
            'nombre' => 'Programa de '.fake()->unique()->jobTitle(),
            'facultad' => 'Facultad de '.fake()->word(),
            'nivel' => NivelCarrera::Pregrado,
            'activa' => true,
        ];
    }

    /**
     * Indicate that the program is inactive.
     */
    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'activa' => false,
        ]);
    }
}
