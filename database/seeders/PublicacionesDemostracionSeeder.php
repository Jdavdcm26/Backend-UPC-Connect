<?php

namespace Database\Seeders;

use App\Enums\Publicaciones\EstadoModeracion;
use App\Enums\Publicaciones\TipoPublicacion;
use App\Models\Autenticacion\Universidad;
use App\Models\Catalogos\Categoria;
use App\Models\Publicaciones\Comentario;
use App\Models\Publicaciones\Publicacion;
use App\Models\Publicaciones\Reaccion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Publicaciones de demostración entre los candidatos de la UPC: generales,
 * servicios y búsquedas de equipo de las últimas dos semanas, con
 * comentarios, reacciones y seguidores. Se puede correr sola sobre datos que
 * ya existen:
 *
 * php artisan db:seed --class=PublicacionesDemostracionSeeder
 */
class PublicacionesDemostracionSeeder extends Seeder
{
    /**
     * @var list<array{TipoPublicacion, string, string, array<string, mixed>|null}>
     */
    private const array PUBLICACIONES = [
        [TipoPublicacion::General, 'Logro', 'Terminé mi práctica en una empresa de software de Valledupar. Aprendí muchísimo sobre trabajo en equipo y despliegues. ¡Gracias a todos los que me ayudaron a prepararme para la entrevista!', null],
        [TipoPublicacion::Servicio, 'Servicio o asesoría', 'Ofrezco tutorías de cálculo diferencial e integral para primeros semestres. Llevo dos años como monitor de la asignatura.', ['area' => 'Cálculo diferencial e integral', 'modalidad' => 'HIBRIDA', 'disponibilidad' => 'Lunes a jueves, de 4:00 p. m. a 7:00 p. m.']],
        [TipoPublicacion::BusquedaEquipo, 'Búsqueda de integrantes', 'Buscamos integrantes para el concurso de innovación de la universidad. La idea es una app que conecte a los agricultores del Cesar con compradores locales.', ['proyecto' => 'App de comercio justo para agricultores del Cesar', 'perfiles_requeridos' => 'Diseño de interfaces, desarrollo móvil y alguien de Administración de Empresas', 'cupos' => 3]],
        [TipoPublicacion::General, 'General', '¿Alguien sabe si este semestre abren el curso de inglés B2 en la tarde? Lo necesito para el requisito de grado.', null],
        [TipoPublicacion::Servicio, 'Servicio o asesoría', 'Reviso hojas de vida y simulo entrevistas para prácticas. Soy egresada y trabajé dos años en selección de personal.', ['area' => 'Hoja de vida y entrevistas', 'modalidad' => 'REMOTA', 'disponibilidad' => 'Sábados en la mañana']],
        [TipoPublicacion::General, 'Logro', 'Nuestro semillero de investigación presentó una ponencia en el encuentro regional de RedCOLSI. Fue la primera vez que exponía frente a tanta gente.', null],
        [TipoPublicacion::BusquedaEquipo, 'Búsqueda de integrantes', 'Necesitamos dos personas para el proyecto de grado sobre calidad del agua del río Guatapurí. Ideal si saben de análisis de datos.', ['proyecto' => 'Monitoreo de calidad del agua del río Guatapurí', 'perfiles_requeridos' => 'Análisis de datos y química ambiental', 'cupos' => 2]],
        [TipoPublicacion::General, 'General', 'Comparto una guía que armé con los pasos para registrar la práctica empresarial. A mí me costó entender el proceso, ojalá les sirva.', null],
        [TipoPublicacion::Servicio, 'Servicio o asesoría', 'Doy clases de Excel intermedio y avanzado: tablas dinámicas, fórmulas y gráficos. Útil para Contaduría y Administración.', ['area' => 'Excel intermedio y avanzado', 'modalidad' => 'PRESENCIAL', 'disponibilidad' => 'Viernes en la tarde, en la biblioteca']],
        [TipoPublicacion::General, 'General', 'Mañana hay charla sobre emprendimiento en el auditorio. Van a hablar egresados que montaron su empresa en Valledupar.', null],
    ];

    private const array COMENTARIOS = [
        '¡Felicitaciones! Qué buena noticia.',
        'Me interesa, te escribo por mensaje.',
        'Gracias por compartir, me sirve mucho.',
        '¿Todavía hay cupo?',
        'Yo fui el semestre pasado y vale mucho la pena.',
        'Excelente iniciativa.',
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Los datos de demostración no se cargan en producción.');
        }

        if (Publicacion::query()->withoutGlobalScopes()->exists()) {
            $this->command?->info('Las publicaciones de demostración ya estaban cargadas.');

            return;
        }

        $upc = Universidad::query()->where('dominio_correo', UniversidadSeeder::DOMINIO_UPC)->first();
        $candidatos = $upc === null ? collect() : User::query()
            ->withoutGlobalScopes()
            ->where('universidad_id', $upc->id)
            ->whereHas('perfil')
            ->orderBy('created_at')
            ->get();

        if ($candidatos->count() < 4) {
            $this->command?->warn('Faltan candidatos con perfil: corre primero DatosDemostracionSeeder.');

            return;
        }

        $this->call(CategoriaSeeder::class);
        $categorias = Categoria::query()->pluck('id', 'nombre');
        $hoy = CarbonImmutable::now();
        $faker = fake('es_ES');

        foreach (self::PUBLICACIONES as $posicion => [$tipo, $categoria, $contenido, $detalles]) {
            $autor = $candidatos[$posicion % $candidatos->count()];
            $publicada = $hoy->subDays(14 - $posicion)->setTime($faker->numberBetween(7, 21), $faker->numberBetween(0, 59));

            $publicacion = Publicacion::factory()->de($autor)->create([
                'universidad_id' => $upc->id,
                'categoria_id' => $categorias[$categoria],
                'tipo' => $tipo,
                'contenido' => $contenido,
                'detalles' => $detalles,
                // La primera búsqueda de equipo ya completó sus cupos.
                'abierta' => $posicion !== 2,
                'estado' => EstadoModeracion::Visible,
                'created_at' => $publicada,
                'updated_at' => $publicada,
            ]);

            $otros = $candidatos->reject(fn (User $candidato): bool => $candidato->is($autor));

            $otros->random(min($otros->count(), $faker->numberBetween(0, 3)))->values()->each(
                fn (User $comentarista, int $orden) => Comentario::factory()->for($publicacion)->de($comentarista)->create([
                    'contenido' => self::COMENTARIOS[($posicion + $orden) % count(self::COMENTARIOS)],
                    'created_at' => $publicada->addHours($orden + 1),
                    'updated_at' => $publicada->addHours($orden + 1),
                ]),
            );

            Reaccion::query()->insert($otros->random(min($otros->count(), $faker->numberBetween(1, 8)))->map(fn (User $usuario): array => [
                'publicacion_id' => $publicacion->id,
                'usuario_id' => $usuario->id,
                'created_at' => $publicada->addHours(2),
            ])->all());
        }

        // Cada candidato sigue a dos o tres compañeros.
        $candidatos->each(function (User $candidato) use ($candidatos, $faker): void {
            $candidato->seguidos()->syncWithoutDetaching(
                $candidatos->reject(fn (User $otro): bool => $otro->is($candidato))->random($faker->numberBetween(2, 3))->modelKeys(),
            );
        });

        $this->command?->info('Publicaciones de demostración cargadas.');
    }
}
