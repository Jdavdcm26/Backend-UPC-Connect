<?php

namespace Database\Seeders;

use App\Enums\Catalogos\TipoHabilidad;
use App\Models\Catalogos\Habilidad;
use Illuminate\Database\Seeder;

class HabilidadSeeder extends Seeder
{
    /**
     * Conjunto inicial de habilidades; el administrador lo amplía desde el
     * panel.
     *
     * @var array<string, list<string>>
     */
    private const array HABILIDADES = [
        'TECNICA' => [
            'Python', 'Java', 'JavaScript', 'TypeScript', 'PHP', 'SQL', 'React', 'Laravel',
            'Excel avanzado', 'Power BI', 'Análisis de datos', 'AutoCAD', 'Diseño gráfico',
            'Contabilidad financiera', 'Gestión de proyectos', 'Marketing digital',
        ],
        'BLANDA' => [
            'Trabajo en equipo', 'Comunicación asertiva', 'Liderazgo', 'Resolución de problemas',
            'Pensamiento crítico', 'Gestión del tiempo', 'Adaptabilidad',
        ],
        'IDIOMA' => [
            'Inglés', 'Francés', 'Portugués',
        ],
    ];

    /**
     * Registra las habilidades iniciales sin duplicarlas.
     */
    public function run(): void
    {
        foreach (self::HABILIDADES as $tipo => $nombres) {
            foreach ($nombres as $nombre) {
                Habilidad::query()->updateOrCreate(
                    ['nombre' => $nombre],
                    ['tipo' => TipoHabilidad::from($tipo)],
                );
            }
        }
    }
}
