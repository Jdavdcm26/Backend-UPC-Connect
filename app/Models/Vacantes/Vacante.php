<?php

namespace App\Models\Vacantes;

use App\Enums\Vacantes\EstadoVacante;
use App\Enums\Vacantes\ModalidadVacante;
use App\Enums\Vacantes\TipoVacante;
use App\Models\Catalogos\Carrera;
use Database\Factories\Vacantes\VacanteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vacante, práctica o pasantía de una empresa (HU-41 a HU-43).
 */
#[Table('vacantes')]
#[Fillable(['tipo', 'titulo', 'descripcion', 'requisitos', 'ciudad', 'modalidad', 'fecha_cierre'])]
class Vacante extends Model
{
    /** @use HasFactory<VacanteFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoVacante::class,
            'modalidad' => ModalidadVacante::class,
            'estado' => EstadoVacante::class,
            'fecha_cierre' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * @return BelongsToMany<Carrera, $this>
     */
    public function carreras(): BelongsToMany
    {
        return $this->belongsToMany(Carrera::class, 'vacante_carrera');
    }

    /**
     * Abierta a postulaciones: estado ACTIVA y fecha de cierre no vencida.
     * Excluye las vencidas aunque el cierre automático aún no haya corrido.
     */
    public function estaAbierta(): bool
    {
        return $this->estado === EstadoVacante::Activa && ! $this->fecha_cierre->isBefore(today());
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function abiertas(Builder $query): void
    {
        $query->where('estado', EstadoVacante::Activa)->whereDate('fecha_cierre', '>=', today());
    }
}
