<?php

namespace App\Models\Catalogos;

use App\Enums\Catalogos\NivelCarrera;
use App\Models\Autenticacion\Universidad;
use App\Models\Scopes\UniversidadScope;
use Database\Factories\Catalogos\CarreraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Programa académico oficial de una universidad.
 */
#[Table('carreras')]
#[ScopedBy(UniversidadScope::class)]
#[Fillable(['nombre', 'facultad', 'nivel', 'activa'])]
class Carrera extends Model
{
    /** @use HasFactory<CarreraFactory> */
    use HasFactory, HasUuids;

    /**
     * Clave de la caché con las carreras activas de una universidad.
     */
    public static function claveCacheActivas(string $universidadId): string
    {
        return "catalogos:carreras:{$universidadId}";
    }

    /**
     * Invalida la caché del catálogo cuando una carrera cambia.
     */
    protected static function booted(): void
    {
        $invalidar = fn (self $carrera) => Cache::forget(self::claveCacheActivas($carrera->universidad_id));

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
            'nivel' => NivelCarrera::class,
            'activa' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Universidad, $this>
     */
    public function universidad(): BelongsTo
    {
        return $this->belongsTo(Universidad::class);
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
