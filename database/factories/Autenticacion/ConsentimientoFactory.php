<?php

namespace Database\Factories\Autenticacion;

use App\Models\Autenticacion\Consentimiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consentimiento>
 */
class ConsentimientoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'version_documento' => config('app.version_politica_datos'),
            'aceptado_en' => now(),
        ];
    }
}
