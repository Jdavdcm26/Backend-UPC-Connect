<?php

namespace App\Filament;

use App\Models\Administracion\Auditoria;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Callbacks para auditar las acciones de crear y editar del panel (RF-58).
 * Se pasan a ->after() de CreateAction y EditAction.
 */
final class RegistroDeAuditoria
{
    public static function alCrear(string $accion): Closure
    {
        return fn (Model $record) => Auditoria::registrar($accion, $record, $record->only($record->getFillable()));
    }

    public static function alEditar(string $accion): Closure
    {
        return fn (Model $record) => Auditoria::registrar($accion, $record, [
            'cambios' => Arr::except($record->getChanges(), ['updated_at']),
        ]);
    }
}
