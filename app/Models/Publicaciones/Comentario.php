<?php

namespace App\Models\Publicaciones;

use App\Enums\Publicaciones\EstadoModeracion;
use App\Models\Networking\Bloqueo;
use App\Models\User;
use Database\Factories\Publicaciones\ComentarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Comentario de una publicación (HU-26).
 */
#[Table('comentarios')]
#[Fillable(['contenido'])]
class Comentario extends Model
{
    /** @use HasFactory<ComentarioFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoModeracion::class,
            'editado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Publicacion, $this>
     */
    public function publicacion(): BelongsTo
    {
        return $this->belongsTo(Publicacion::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function esDe(User $usuario): bool
    {
        return $this->autor_id === $usuario->id;
    }

    /**
     * Visibles por moderación y de nadie con quien el usuario tenga un bloqueo.
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
