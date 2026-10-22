<?php

namespace App\Models\Catalogos;

use Database\Factories\Catalogos\CategoriaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Categoría de publicación (HU-18, HU-54).
 */
#[Table('categorias')]
#[Fillable(['nombre', 'activa'])]
class Categoria extends Model
{
    /** @use HasFactory<CategoriaFactory> */
    use HasFactory, HasUuids;

    public const string CLAVE_CACHE_ACTIVAS = 'catalogos:categorias';

    /**
     * Invalida la caché del catálogo cuando una categoría cambia.
     */
    protected static function booted(): void
    {
        $invalidar = fn () => Cache::forget(self::CLAVE_CACHE_ACTIVAS);

        static::saved($invalidar);
        static::deleted($invalidar);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
