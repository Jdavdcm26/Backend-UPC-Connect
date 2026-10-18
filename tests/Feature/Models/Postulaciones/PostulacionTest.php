<?php

use App\Enums\Postulaciones\EstadoPostulacion;
use App\Models\Postulaciones\HistorialPostulacion;
use App\Models\Postulaciones\Postulacion;
use Illuminate\Database\UniqueConstraintViolationException;

it('allows a single application per person and vacancy at the database level', function () {
    $postulacion = Postulacion::factory()->create();

    Postulacion::factory()->de($postulacion->usuario)->create(['vacante_id' => $postulacion->vacante_id]);
})->throws(UniqueConstraintViolationException::class);

it('returns its history in chronological order', function () {
    $postulacion = Postulacion::factory()->create();
    $empresa = $postulacion->vacante->empresa->usuario;
    $postulacion->historial()->create(['estado' => EstadoPostulacion::Entrevista, 'cambiado_por' => $empresa->id, 'cambiado_en' => now()->addDays(2)]);
    $postulacion->historial()->create(['estado' => EstadoPostulacion::EnRevision, 'cambiado_por' => $empresa->id, 'cambiado_en' => now()->addDay()]);

    expect($postulacion->historial()->pluck('estado')->all())
        ->toBe([EstadoPostulacion::Recibida, EstadoPostulacion::EnRevision, EstadoPostulacion::Entrevista]);
});

it('refuses to modify or delete a history entry', function (Closure $operacion) {
    $operacion(Postulacion::factory()->create()->historial()->sole());
})->with([
    'modificar' => [fn (HistorialPostulacion $registro) => $registro->update(['estado' => EstadoPostulacion::Seleccionado])],
    'borrar' => [fn (HistorialPostulacion $registro) => $registro->delete()],
])->throws(LogicException::class);

it('lets the company assign every state except withdrawn', function () {
    expect(EstadoPostulacion::deGestion())->not->toContain(EstadoPostulacion::Retirada)
        ->toHaveCount(5);
});
