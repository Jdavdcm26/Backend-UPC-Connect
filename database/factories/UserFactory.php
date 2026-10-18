<?php

namespace Database\Factories;

use App\Enums\Autenticacion\EstadoUsuario;
use App\Enums\Autenticacion\Rol;
use App\Models\Autenticacion\Universidad;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state: an active, verified student.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'universidad_id' => Universidad::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'rol' => Rol::Estudiante,
            'estado' => EstadoUsuario::Activo,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the account has the given role.
     */
    public function rol(Rol $rol): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => $rol,
        ]);
    }

    /**
     * Indicate that the account is pending email verification.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'estado' => EstadoUsuario::Pendiente,
        ]);
    }

    /**
     * Indicate that the account is suspended.
     */
    public function suspendido(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoUsuario::Suspendido,
        ]);
    }
}
