<?php

namespace Database\Seeders;

use App\Enums\Autenticacion\EstadoUsuario;
use App\Enums\Autenticacion\Rol;
use App\Enums\Perfiles\NivelHabilidad;
use App\Enums\Postulaciones\EstadoPostulacion;
use App\Enums\Vacantes\EstadoVacante;
use App\Enums\Vacantes\ModalidadVacante;
use App\Enums\Vacantes\TipoVacante;
use App\Models\Autenticacion\Universidad;
use App\Models\Catalogos\Carrera;
use App\Models\Catalogos\Habilidad;
use App\Models\Perfiles\Perfil;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;
use Carbon\CarbonImmutable;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Datos de demostración para probar en local: un administrador, empresas,
 * candidatos con perfil, vacantes y postulaciones con historial repartidos
 * en los últimos tres meses. Todas las cuentas usan la contraseña "password".
 *
 * Solo para desarrollo: php artisan db:seed --class=DatosDemostracionSeeder
 */
class DatosDemostracionSeeder extends Seeder
{
    public const string CORREO_ADMIN = 'admin@unicesar.edu.co';

    private const array EMPRESAS = [
        // Empresas y NIT inventados (con su dígito de verificación válido).
        ['razon_social' => 'Soluciones Digitales del Cesar S.A.S.', 'nit' => '901900101', 'sector' => 'Tecnología', 'email' => 'talento@solucionesdelcesar.test'],
        ['razon_social' => 'Agroindustrias del Valle S.A.', 'nit' => '901900202', 'sector' => 'Agroindustria', 'email' => 'seleccion@agrovalle.test'],
        ['razon_social' => 'Clínica Santa Isabel Ltda.', 'nit' => '901900303', 'sector' => 'Salud', 'email' => 'rrhh@clinicasantaisabel.test'],
    ];

    private const array VACANTES = [
        ['Practicante de desarrollo web', TipoVacante::Practica, ModalidadVacante::Hibrida, 0],
        ['Desarrollador junior Laravel', TipoVacante::Vacante, ModalidadVacante::Remota, 0],
        ['Pasante de soporte de TI', TipoVacante::Pasantia, ModalidadVacante::Presencial, 0],
        ['Analista de datos', TipoVacante::Vacante, ModalidadVacante::Hibrida, 0],
        ['Practicante de control de calidad agroindustrial', TipoVacante::Practica, ModalidadVacante::Presencial, 1],
        ['Auxiliar contable', TipoVacante::Vacante, ModalidadVacante::Presencial, 1],
        ['Practicante de gestión ambiental', TipoVacante::Practica, ModalidadVacante::Presencial, 1],
        ['Analista de comercio exterior', TipoVacante::Vacante, ModalidadVacante::Hibrida, 1],
        ['Enfermero(a) jefe de turno', TipoVacante::Vacante, ModalidadVacante::Presencial, 2],
        ['Practicante de instrumentación quirúrgica', TipoVacante::Practica, ModalidadVacante::Presencial, 2],
        ['Auxiliar administrativo en salud', TipoVacante::Vacante, ModalidadVacante::Presencial, 2],
        ['Pasante de microbiología', TipoVacante::Pasantia, ModalidadVacante::Presencial, 2],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Los datos de demostración no se cargan en producción.');
        }

        if (User::query()->withoutGlobalScopes()->where('email', self::CORREO_ADMIN)->exists()) {
            $this->command?->info('Los datos de demostración ya estaban cargados.');

            return;
        }

        $this->call([UniversidadSeeder::class, CarreraSeeder::class, HabilidadSeeder::class, CategoriaSeeder::class]);

        $upc = Universidad::query()->where('dominio_correo', UniversidadSeeder::DOMINIO_UPC)->sole();
        $carreras = Carrera::query()->withoutGlobalScopes()->where('universidad_id', $upc->id)->where('activa', true)->get();
        $habilidades = Habilidad::query()->where('activa', true)->get();
        $faker = fake('es_ES');
        $hoy = CarbonImmutable::now();

        $admin = User::factory()->for($upc)->rol(Rol::Admin)->create([
            'name' => 'Administración UPC Connect',
            'email' => self::CORREO_ADMIN,
            'created_at' => $hoy->subMonths(4),
        ]);

        $empresas = collect(self::EMPRESAS)->map(fn (array $datos): Empresa => Empresa::factory()
            ->for(User::factory()->for($upc)->rol(Rol::Empresa)->state([
                'name' => $faker->name(),
                'email' => $datos['email'],
                'created_at' => $hoy->subMonths(4),
            ]), 'usuario')
            ->create([
                'razon_social' => $datos['razon_social'],
                'nit' => $datos['nit'].'-'.Empresa::digitoVerificacionNit($datos['nit']),
                'sector' => $datos['sector'],
                'aprobada_por' => $admin->id,
                'aprobada_en' => $hoy->subMonths(4),
            ]));

        $candidatos = $this->crearCandidatos($upc, $carreras, $habilidades, $faker, $hoy);
        $vacantes = $this->crearVacantes($empresas, $carreras, $faker, $hoy);
        $this->crearPostulaciones($candidatos, $vacantes, $faker, $hoy);

        $this->command?->info('Datos de demostración cargados. Contraseña de todas las cuentas: password');
    }

    /**
     * Estudiantes y egresados registrados en los últimos tres meses. Algunos
     * completan su perfil y habilidades enseguida (cuentan como activados).
     *
     * @param  Collection<int, Carrera>  $carreras
     * @param  Collection<int, Habilidad>  $habilidades
     * @return Collection<int, User>
     */
    private function crearCandidatos(Universidad $upc, Collection $carreras, Collection $habilidades, Generator $faker, CarbonImmutable $hoy): Collection
    {
        return collect(range(1, 15))->map(function (int $numero) use ($upc, $carreras, $habilidades, $faker, $hoy): User {
            $esEgresado = $numero % 5 === 0;
            $nombres = $faker->firstName();
            $apellidos = $faker->lastName().' '.$faker->lastName();
            $registro = $hoy->subDays($faker->numberBetween(5, 90))->setTime(9, 0);

            $candidato = User::factory()->for($upc)->rol($esEgresado ? Rol::Egresado : Rol::Estudiante)->create([
                'name' => "{$nombres} {$apellidos}",
                'email' => $numero === 1 ? 'estudiante@unicesar.edu.co' : "candidato{$numero}@unicesar.edu.co",
                'created_at' => $registro,
                'email_verified_at' => $registro,
                'estado' => EstadoUsuario::Activo,
            ]);

            $perfil = Perfil::factory()->for($candidato, 'usuario')->create([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'carrera_id' => $carreras->random()->id,
                'semestre' => $esEgresado ? null : $faker->numberBetween(4, 10),
                'anio_grado' => $esEgresado ? $faker->numberBetween(2018, (int) $hoy->year - 1) : null,
                'titular' => $esEgresado ? 'Egresado de la UPC' : 'Estudiante de la UPC',
                'created_at' => $registro->addDay(),
            ]);

            $diasHastaHabilidades = $numero % 3 === 0 ? 20 : 2;
            $habilidades->random($faker->numberBetween(2, 5))->each(fn (Habilidad $habilidad) => $perfil->habilidades()->attach($habilidad, [
                'nivel' => $faker->randomElement(NivelHabilidad::cases()),
                'created_at' => $registro->addDays($diasHastaHabilidades),
                'updated_at' => $registro->addDays($diasHastaHabilidades),
            ]));

            return $candidato;
        });
    }

    /**
     * @param  Collection<int, Empresa>  $empresas
     * @param  Collection<int, Carrera>  $carreras
     * @return Collection<int, Vacante>
     */
    private function crearVacantes(Collection $empresas, Collection $carreras, Generator $faker, CarbonImmutable $hoy): Collection
    {
        return collect(self::VACANTES)->map(function (array $datos, int $indice) use ($empresas, $carreras, $faker, $hoy): Vacante {
            [$titulo, $tipo, $modalidad, $empresa] = $datos;
            $publicada = $hoy->subDays(85 - $indice * 7)->setTime(10, 0);

            $estado = match (true) {
                $indice === 3 => EstadoVacante::Pausada,
                $indice === 7 => EstadoVacante::Cerrada,
                default => EstadoVacante::Activa,
            };

            return Vacante::factory()
                ->for($empresas[$empresa])
                ->hasAttached($carreras->random(2))
                ->create([
                    'titulo' => $titulo,
                    'tipo' => $tipo,
                    'modalidad' => $modalidad,
                    'estado' => $estado,
                    'descripcion' => $faker->paragraph(3),
                    'requisitos' => $faker->sentence(12),
                    'ciudad' => $modalidad === ModalidadVacante::Remota ? 'Remoto' : 'Valledupar',
                    'fecha_cierre' => $hoy->addDays($faker->numberBetween(15, 60))->toDateString(),
                    'created_at' => $publicada,
                ]);
        });
    }

    /**
     * Postulaciones con historial: algunas siguen recibidas y otras avanzaron
     * hasta seleccionado o no seleccionado, con tiempos de respuesta variados.
     *
     * @param  Collection<int, User>  $candidatos
     * @param  Collection<int, Vacante>  $vacantes
     */
    private function crearPostulaciones(Collection $candidatos, Collection $vacantes, Generator $faker, CarbonImmutable $hoy): void
    {
        $recorridos = [
            [],
            [EstadoPostulacion::EnRevision],
            [EstadoPostulacion::EnRevision, EstadoPostulacion::Entrevista],
            [EstadoPostulacion::EnRevision, EstadoPostulacion::Entrevista, EstadoPostulacion::Seleccionado],
            [EstadoPostulacion::EnRevision, EstadoPostulacion::NoSeleccionado],
        ];

        $candidatos->each(function (User $candidato, int $indice) use ($vacantes, $recorridos, $faker, $hoy): void {
            $vacantes
                ->filter(fn (Vacante $vacante): bool => $vacante->created_at->lessThan($hoy->subDays(3)))
                ->random(2)
                ->each(function (Vacante $vacante) use ($candidato, $indice, $recorridos, $faker, $hoy): void {
                    $enviada = CarbonImmutable::parse($faker->dateTimeBetween($vacante->created_at, $hoy->subDays(2)));
                    $recorrido = $recorridos[($indice + strlen($vacante->titulo)) % count($recorridos)];

                    $postulacion = Postulacion::factory()->de($candidato)->create([
                        'vacante_id' => $vacante->id,
                        'mensaje' => $faker->optional(0.6)->sentence(14),
                        'estado' => EstadoPostulacion::Recibida,
                        'created_at' => $enviada,
                        'updated_at' => $enviada,
                    ]);

                    $momento = $enviada;

                    foreach ($recorrido as $estado) {
                        $momento = $momento->addHours($faker->numberBetween(6, 72));

                        $postulacion->historial()->create([
                            'estado' => $estado,
                            'cambiado_por' => $vacante->empresa->usuario_id,
                            'cambiado_en' => $momento,
                        ]);
                    }

                    if ($recorrido !== []) {
                        $postulacion->timestamps = false;
                        $postulacion->forceFill(['estado' => end($recorrido), 'updated_at' => $momento])->save();
                    }
                });
        });
    }
}
