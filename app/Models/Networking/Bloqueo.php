<?php

namespace App\Models\Networking;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\Builder;

/**
 * Bloqueo entre dos usuarios (HU-31). El bloqueo se aplica en las dos
 * direcciones: ninguno ve el contenido del otro ni interactúa con él.
 */
#[Table('bloqueos')]
class Bloqueo extends Pivot
{
    public const null UPDATED_AT = null;

    /**
     * La tabla solo registra cuándo se creó la relación. Un pivote toma por
     * defecto la columna updated_at del modelo padre, así que se anula aquí.
     */
    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    /**
     * Subconsulta con los ids de quienes bloqueó el usuario y de quienes lo
     * bloquearon. Se usa dentro de whereNotIn, sin traer los ids a PHP.
     */
    public static function vinculadosCon(User $usuario): Builder
    {
        return static::query()
            ->select('bloqueado_id as usuario_id')
            ->where('bloqueador_id', $usuario->id)
            ->toBase()
            ->union(
                static::query()->select('bloqueador_id as usuario_id')->where('bloqueado_id', $usuario->id)->toBase(),
            );
    }

    /**
     * Si alguno de los dos bloqueó al otro.
     */
    public static function entre(User $uno, User $otro): bool
    {
        return static::query()
            ->where(fn ($consulta) => $consulta->where('bloqueador_id', $uno->id)->where('bloqueado_id', $otro->id))
            ->orWhere(fn ($consulta) => $consulta->where('bloqueador_id', $otro->id)->where('bloqueado_id', $uno->id))
            ->exists();
    }
}
