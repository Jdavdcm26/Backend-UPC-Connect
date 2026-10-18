<?php

namespace Database\Factories\Vacantes;

use App\Enums\Autenticacion\Rol;
use App\Enums\Vacantes\EstadoAprobacionEmpresa;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empresa>
 */
class EmpresaFactory extends Factory
{
    /**
     * Define the model's default state: an approved company, as the
     * administrator creates them in Release 1.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory()->rol(Rol::Empresa),
            'razon_social' => fake()->company().' S.A.S.',
            'nit' => fake()->unique()->numerify('9########-#'),
            'sector' => 'Tecnología',
            'descripcion' => fake()->paragraph(),
            'sitio_web' => fake()->url(),
            'ciudad' => 'Valledupar',
            'estado_aprobacion' => EstadoAprobacionEmpresa::Aprobada,
            'aprobada_en' => now(),
        ];
    }

    /**
     * Indicate that the company is waiting for approval.
     */
    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_aprobacion' => EstadoAprobacionEmpresa::Pendiente,
            'aprobada_en' => null,
        ]);
    }

    /**
     * Indicate that the company was rejected.
     */
    public function rechazada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_aprobacion' => EstadoAprobacionEmpresa::Rechazada,
            'aprobada_en' => null,
        ]);
    }
}
