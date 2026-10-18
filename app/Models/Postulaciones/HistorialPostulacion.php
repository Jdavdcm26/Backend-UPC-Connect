<?php

namespace App\Models\Postulaciones;

use App\Enums\Postulaciones\EstadoPostulacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Un cambio de estado de una postulación. Es evidencia (RNF-19): no se
 * modifica ni se borra.
 */
#[Table('historial_postulaciones', timestamps: false)]
#[Fillable(['estado', 'cambiado_por', 'cambiado_en'])]
class HistorialPostulacion extends Model
{
    use HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoPostulacion::class,
            'cambiado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Postulacion, $this>
     */
    public function postulacion(): BelongsTo
    {
        return $this->belongsTo(Postulacion::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cambiado_por');
    }

    protected static function booted(): void
    {
        $rechazar = fn () => throw new LogicException('El historial de una postulación no se puede modificar ni borrar.');

        static::updating($rechazar);
        static::deleting($rechazar);
    }
}
