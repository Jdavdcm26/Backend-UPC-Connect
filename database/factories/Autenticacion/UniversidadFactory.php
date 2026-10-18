<?php

namespace Database\Factories\Autenticacion;

use App\Models\Autenticacion\Universidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Universidad>
 */
class UniversidadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Universidad '.fake()->unique()->lastName(),
            'dominio_correo' => fake()->unique()->domainWord().'.edu.co',
            'activa' => true,
        ];
    }

    /**
     * Indicate that the university is inactive.
     */
    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'activa' => false,
        ]);
    }
}
