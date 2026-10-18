<?php

namespace App\Models\Catalogos;

use App\Enums\Catalogos\TipoHabilidad;
use Database\Factories\Catalogos\HabilidadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Competencia del catálogo (técnica, blanda o idioma).
 */
#[Table('habilidades')]
#[Fillable(['nombre', 'tipo', 'activa'])]
class Habilidad extends Model
{
    /** @use HasFactory<HabilidadFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoHabilidad::class,
            'activa' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function activas(Builder $query): void
    {
        $query->where('activa', true);
    }
}
