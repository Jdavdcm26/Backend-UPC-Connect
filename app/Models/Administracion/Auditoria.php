<?php

namespace App\Models\Administracion;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Registro de una acción administrativa: quién, qué, sobre qué y cuándo. Las
 * filas no se modifican ni se borran (RNF-17).
 */
#[Table('auditoria')]
#[Fillable(['actor_id', 'accion', 'entidad', 'entidad_id', 'detalle'])]
class Auditoria extends Model
{
    use HasUuids;

    public const null UPDATED_AT = null;

    /**
     * Registra una acción hecha por el usuario con sesión sobre una entidad.
     *
     * @param  array<string, mixed>  $detalle
     */
    public static function registrar(string $accion, Model $entidad, array $detalle = []): self
    {
        return static::query()->create([
            'actor_id' => Auth::id(),
            'accion' => $accion,
            'entidad' => $entidad->getTable(),
            'entidad_id' => $entidad->getKey(),
            'detalle' => $detalle === [] ? null : $detalle,
        ]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'detalle' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected static function booted(): void
    {
        $rechazar = fn () => throw new LogicException('La auditoría no se puede modificar ni borrar.');

        static::updating($rechazar);
        static::deleting($rechazar);
    }
}
