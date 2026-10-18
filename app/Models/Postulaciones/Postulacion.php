<?php

namespace App\Models\Postulaciones;

use App\Enums\Postulaciones\EstadoPostulacion;
use App\Models\User;
use App\Models\Vacantes\Vacante;
use Database\Factories\Postulaciones\PostulacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Postulación de un estudiante o egresado a una vacante (HU-45 a HU-47).
 */
#[Table('postulaciones')]
#[Fillable(['mensaje'])]
class Postulacion extends Model
{
    /** @use HasFactory<PostulacionFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoPostulacion::class,
        ];
    }

    /**
     * @return BelongsTo<Vacante, $this>
     */
    public function vacante(): BelongsTo
    {
        return $this->belongsTo(Vacante::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * @return HasMany<HistorialPostulacion, $this>
     */
    public function historial(): HasMany
    {
        return $this->hasMany(HistorialPostulacion::class)->orderBy('cambiado_en')->orderBy('id');
    }
}
