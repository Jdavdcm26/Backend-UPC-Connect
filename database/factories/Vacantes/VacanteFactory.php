<?php

namespace Database\Factories\Vacantes;

use App\Enums\Vacantes\EstadoVacante;
use App\Enums\Vacantes\ModalidadVacante;
use App\Enums\Vacantes\TipoVacante;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vacante>
 */
class VacanteFactory extends Factory
{
    /**
     * Define the model's default state: an open vacancy.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'tipo' => TipoVacante::Vacante,
            'titulo' => fake()->jobTitle(),
            'descripcion' => fake()->paragraph(),
            'requisitos' => fake()->sentence(),
            'ciudad' => 'Valledupar',
            'modalidad' => ModalidadVacante::Presencial,
            'estado' => EstadoVacante::Activa,
            'fecha_cierre' => today()->addMonth(),
        ];
    }

    public function pausada(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => EstadoVacante::Pausada]);
    }

    public function cerrada(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => EstadoVacante::Cerrada]);
    }

    /**
     * Still marked as active but its closing date already passed.
     */
    public function vencida(): static
    {
        return $this->state(fn (array $attributes) => ['fecha_cierre' => today()->subDay()]);
    }
}
