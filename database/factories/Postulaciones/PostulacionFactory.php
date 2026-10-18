<?php

namespace Database\Factories\Postulaciones;

use App\Enums\Postulaciones\EstadoPostulacion;
use App\Models\Perfiles\Perfil;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;
use App\Models\Vacantes\Vacante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Postulacion>
 */
class PostulacionFactory extends Factory
{
    /**
     * Define the model's default state: a received application from a student
     * with a profile, with its first history entry.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vacante_id' => Vacante::factory(),
            'usuario_id' => fn () => Perfil::factory()->create()->usuario_id,
            'mensaje' => fake()->sentence(),
            'estado' => EstadoPostulacion::Recibida,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Postulacion $postulacion): void {
            $postulacion->historial()->create([
                'estado' => $postulacion->estado,
                'cambiado_por' => $postulacion->usuario_id,
                'cambiado_en' => $postulacion->created_at,
            ]);
        });
    }

    public function enEstado(EstadoPostulacion $estado): static
    {
        return $this->state(fn (array $attributes) => ['estado' => $estado]);
    }

    /**
     * Applicant chosen by the test instead of a generated one.
     */
    public function de(User $usuario): static
    {
        return $this->state(fn (array $attributes) => ['usuario_id' => $usuario->id]);
    }
}
