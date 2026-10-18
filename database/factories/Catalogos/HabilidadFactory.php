<?php

namespace Database\Factories\Catalogos;

use App\Enums\Catalogos\TipoHabilidad;
use App\Models\Catalogos\Habilidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Habilidad>
 */
class HabilidadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'tipo' => TipoHabilidad::Tecnica,
            'activa' => true,
        ];
    }

    /**
     * Indicate that the skill is inactive.
     */
    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'activa' => false,
        ]);
    }
}
