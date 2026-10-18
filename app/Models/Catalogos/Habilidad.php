<?php

namespace App\Models\Catalogos;

use App\Enums\Catalogos\TipoHabilidad;
use App\Models\Perfiles\Perfil;
use App\Models\Perfiles\PerfilHabilidad;
use Database\Factories\Catalogos\HabilidadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

/**
 * Competencia del catálogo (técnica, blanda o idioma).
 */
#[Table('habilidades')]
#[Fillable(['nombre', 'tipo', 'activa'])]
class Habilidad extends Model
{
    /** @use HasFactory<HabilidadFactory> */
    use HasFactory, HasUuids;

    public const string CLAVE_CACHE_ACTIVAS = 'catalogos:habilidades';

    /**
     * Invalida la caché del catálogo cuando una habilidad cambia.
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

    /**
     * @return BelongsToMany<Perfil, $this>
     */
    public function perfiles(): BelongsToMany
    {
        return $this->belongsToMany(Perfil::class, 'perfil_habilidad')
            ->using(PerfilHabilidad::class)
            ->withPivot('nivel')
            ->withTimestamps();
    }
}
