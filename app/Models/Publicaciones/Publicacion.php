<?php

namespace App\Models\Publicaciones;

use App\Enums\Publicaciones\EstadoModeracion;
use App\Enums\Publicaciones\TipoPublicacion;
use App\Models\Catalogos\Categoria;
use App\Models\Networking\Bloqueo;
use App\Models\Scopes\UniversidadScope;
use App\Models\User;
use Database\Factories\Publicaciones\PublicacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Publicación del feed de la comunidad de una universidad (HU-18 a HU-24).
 */
#[ScopedBy(UniversidadScope::class)]
#[Table('publicaciones')]
#[Fillable(['categoria_id', 'contenido', 'detalles'])]
class Publicacion extends Model
{
    /** @use HasFactory<PublicacionFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public const int MAXIMO_IMAGENES = 4;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoPublicacion::class,
            'estado' => EstadoModeracion::class,
            'detalles' => 'array',
            'abierta' => 'boolean',
            'editada_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * @return HasMany<PublicacionImagen, $this>
     */
    public function imagenes(): HasMany
    {
        return $this->hasMany(PublicacionImagen::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return HasMany<Comentario, $this>
     */
    public function comentarios(): HasMany
    {
        return $this->hasMany(Comentario::class);
    }

    /**
     * @return HasMany<Reaccion, $this>
     */
    public function reacciones(): HasMany
    {
        return $this->hasMany(Reaccion::class);
    }

    public function esDe(User $usuario): bool
    {
        return $this->autor_id === $usuario->id;
    }

    /**
     * Carga lo que muestra cada publicación sin consultas extra por fila:
     * autor con su perfil, categoría, imágenes, contadores y si el usuario ya
     * reaccionó. Los comentarios contados son los que el usuario puede ver.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function conDetallesPara(Builder $query, User $usuario): void
    {
        $query->with(['autor.perfil.carrera', 'categoria', 'imagenes'])
            ->withCount([
                'reacciones',
                'comentarios' => fn ($comentarios) => $comentarios->visiblesPara($usuario),
            ])
            ->withExists(['reacciones as reaccione' => fn ($reacciones) => $reacciones->where('usuario_id', $usuario->id)]);
    }

    /**
     * Lo que el usuario puede ver: visible por moderación y de nadie con quien
     * tenga un bloqueo en cualquier dirección (HU-24, HU-31).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visiblesPara(Builder $query, User $usuario): void
    {
        $query->where('estado', EstadoModeracion::Visible)
            ->whereNotIn('autor_id', Bloqueo::vinculadosCon($usuario));
    }
}
