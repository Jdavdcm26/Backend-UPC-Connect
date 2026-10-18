<?php

namespace Database\Seeders;

use App\Enums\Catalogos\NivelCarrera;
use App\Models\Autenticacion\Universidad;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    /**
     * Programas de pregrado de la UPC agrupados por facultad.
     *
     * PROVISIONAL: borrador mientras la universidad entrega el listado oficial
     * (riesgo R-10). Al recibirlo, se reemplaza esta lista.
     *
     * @var array<string, list<string>>
     */
    private const array PROGRAMAS_UPC = [
        'Facultad de Ingenierías y Tecnológicas' => [
            'Ingeniería de Sistemas',
            'Ingeniería Electrónica',
            'Ingeniería Agroindustrial',
            'Ingeniería Ambiental y Sanitaria',
        ],
        'Facultad de Ciencias Administrativas, Contables y Económicas' => [
            'Administración de Empresas',
            'Administración de Empresas Turísticas y Hoteleras',
            'Contaduría Pública',
            'Economía',
            'Comercio Internacional',
        ],
        'Facultad de Ciencias de la Salud' => [
            'Enfermería',
            'Instrumentación Quirúrgica',
            'Microbiología',
        ],
        'Facultad de Derecho, Ciencias Políticas y Sociales' => [
            'Derecho',
            'Sociología',
            'Psicología',
        ],
        'Facultad de Ciencias Básicas y de la Educación' => [
            'Licenciatura en Matemáticas',
            'Licenciatura en Ciencias Naturales y Educación Ambiental',
            'Licenciatura en Literatura y Lengua Castellana',
            'Licenciatura en Lenguas Extranjeras con Énfasis en Inglés',
            'Licenciatura en Educación Física, Recreación y Deportes',
        ],
        'Facultad de Bellas Artes' => [
            'Licenciatura en Arte y Folclor',
            'Música',
        ],
    ];

    /**
     * Registra los programas de la UPC. Se puede correr varias veces sin
     * duplicarlos.
     */
    public function run(): void
    {
        $upc = Universidad::query()->where('dominio_correo', UniversidadSeeder::DOMINIO_UPC)->sole();

        foreach (self::PROGRAMAS_UPC as $facultad => $programas) {
            foreach ($programas as $programa) {
                $upc->carreras()->updateOrCreate(
                    ['nombre' => $programa],
                    ['facultad' => $facultad, 'nivel' => NivelCarrera::Pregrado],
                );
            }
        }
    }
}
