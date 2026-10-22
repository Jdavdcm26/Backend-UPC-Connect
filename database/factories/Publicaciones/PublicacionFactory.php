<?php

namespace Database\Factories\Publicaciones;

use App\Enums\Publicaciones\EstadoModeracion;
use App\Enums\Publicaciones\TipoPublicacion;
use App\Enums\Vacantes\ModalidadVacante;
use App\Models\Catalogos\Categoria;
use App\Models\Perfiles\Perfil;
use App\Models\Publicaciones\Publicacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publicacion>
 */
class PublicacionFactory extends Factory
{
    /**
     * Define the model's default state: a visible general post by a student
     * with a profile, in the author's university.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'autor_id' => fn () => Perfil::factory()->create()->usuario_id,
            'universidad_id' => fn (array $atributos) => User::query()->withoutGlobalScopes()->findOrFail($atributos['autor_id'])->universidad_id,
            'categoria_id' => Categoria::factory(),
            'tipo' => TipoPublicacion::General,
            'contenido' => fake()->paragraph(),
            'detalles' => null,
            'abierta' => true,
            'estado' => EstadoModeracion::Visible,
        ];
    }

    /**
     * Author chosen by the test instead of a generated one.
     */
    public function de(User $autor): static
    {
        return $this->state(fn (array $attributes) => ['autor_id' => $autor->id]);
    }

    public function servicio(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => TipoPublicacion::Servicio,
            'detalles' => [
                'area' => 'Programación en Python',
                'modalidad' => ModalidadVacante::Remota->value,
                'disponibilidad' => 'Fines de semana',
            ],
        ]);
    }

    public function busquedaEquipo(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => TipoPublicacion::BusquedaEquipo,
            'detalles' => [
                'proyecto' => 'App de reciclaje para Valledupar',
                'perfiles_requeridos' => 'Diseño de interfaces y desarrollo móvil',
                'cupos' => 2,
            ],
        ]);
    }

    public function oculta(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => EstadoModeracion::Oculto]);
    }

    public function cerrada(): static
    {
        return $this->state(fn (array $attributes) => ['abierta' => false]);
    }
}
