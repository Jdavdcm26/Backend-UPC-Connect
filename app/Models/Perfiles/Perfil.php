<?php

namespace App\Models\Perfiles;

use App\Enums\Perfiles\NivelHabilidad;
use App\Models\Catalogos\Carrera;
use App\Models\Catalogos\Habilidad;
use App\Models\User;
use Database\Factories\Perfiles\PerfilFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Perfil profesional de un estudiante o egresado.
 */
#[Table('perfiles')]
#[Fillable(['nombres', 'apellidos', 'titular', 'descripcion', 'carrera_id', 'semestre', 'anio_grado', 'ciudad', 'intereses'])]
class Perfil extends Model
{
    /** @use HasFactory<PerfilFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semestre' => 'integer',
            'anio_grado' => 'integer',
            'intereses' => 'array',
            'visibilidad' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * @return BelongsTo<Carrera, $this>
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    /**
     * @return BelongsToMany<Habilidad, $this>
     */
    public function habilidades(): BelongsToMany
    {
        return $this->belongsToMany(Habilidad::class, 'perfil_habilidad')
            ->withPivot('nivel')
            ->withCasts(['nivel' => NivelHabilidad::class])
            ->withTimestamps();
    }
}
