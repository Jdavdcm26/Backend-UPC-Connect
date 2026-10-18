<?php

namespace App\Models\Autenticacion;

use App\Models\User;
use Database\Factories\Autenticacion\ConsentimientoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aceptación de una versión de la política de tratamiento de datos. Es
 * evidencia legal, así que no se edita: se agrega un registro por versión.
 */
#[Table('consentimientos', timestamps: false)]
#[Fillable(['version_documento', 'aceptado_en'])]
class Consentimiento extends Model
{
    /** @use HasFactory<ConsentimientoFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aceptado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
