<?php

namespace Database\Factories\Publicaciones;

use App\Enums\Publicaciones\EstadoModeracion;
use App\Models\Publicaciones\Comentario;
use App\Models\Publicaciones\Publicacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comentario>
 */
class ComentarioFactory extends Factory
{
    /**
     * Define the model's default state: a visible comment by the author of
     * the post (use de() to choose another author).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'publicacion_id' => Publicacion::factory(),
            'autor_id' => fn (array $atributos) => Publicacion::query()->withoutGlobalScopes()->findOrFail($atributos['publicacion_id'])->autor_id,
            'contenido' => fake()->sentence(),
            'estado' => EstadoModeracion::Visible,
        ];
    }

    /**
     * Author chosen by the test instead of the post's author.
     */
    public function de(User $autor): static
    {
        return $this->state(fn (array $attributes) => ['autor_id' => $autor->id]);
    }

    public function oculto(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => EstadoModeracion::Oculto]);
    }
}
