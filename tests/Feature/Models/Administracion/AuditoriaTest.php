<?php

use App\Models\Administracion\Auditoria;
use App\Models\Catalogos\Habilidad;
use App\Models\User;
use Illuminate\Database\QueryException;

it('records the authenticated actor, action, entity and detail', function () {
    $this->freezeSecond();
    $actor = User::factory()->create();
    $habilidad = Habilidad::factory()->create();
    $this->actingAs($actor);

    Auditoria::registrar('habilidad.creada', $habilidad, ['nombre' => $habilidad->nombre]);

    expect(Auditoria::query()->sole())
        ->actor_id->toBe($actor->id)
        ->accion->toBe('habilidad.creada')
        ->entidad->toBe('habilidades')
        ->entidad_id->toBe($habilidad->id)
        ->detalle->toBe(['nombre' => $habilidad->nombre])
        ->created_at->toEqual(now());
});

it('refuses to update or delete a record through Eloquent', function (Closure $operacion) {
    $registro = Auditoria::registrar('habilidad.creada', Habilidad::factory()->create());

    $operacion($registro);
})->with([
    'actualizar' => [fn (Auditoria $registro) => $registro->update(['accion' => 'otra'])],
    'borrar' => [fn (Auditoria $registro) => $registro->delete()],
])->throws(LogicException::class);

it('refuses to update or delete a record in the database itself', function (Closure $operacion) {
    Auditoria::registrar('habilidad.creada', Habilidad::factory()->create());

    $operacion();
})->with([
    'actualizar' => [fn () => Auditoria::query()->toBase()->update(['accion' => 'otra'])],
    'borrar' => [fn () => Auditoria::query()->toBase()->delete()],
])->throws(QueryException::class);
