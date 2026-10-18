<?php

namespace App\Models\Vacantes;

use App\Enums\Vacantes\EstadoAprobacionEmpresa;
use App\Models\User;
use Database\Factories\Vacantes\EmpresaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Organización que publica vacantes, prácticas y pasantías. En el Release 1
 * la crea el administrador y queda aprobada (hallazgo 5).
 */
#[Table('empresas')]
#[Fillable(['razon_social', 'nit', 'sector', 'descripcion', 'sitio_web', 'ciudad'])]
class Empresa extends Model
{
    /** @use HasFactory<EmpresaFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado_aprobacion' => EstadoAprobacionEmpresa::class,
            'aprobada_en' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function aprobadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobada_por');
    }

    public function estaAprobada(): bool
    {
        return $this->estado_aprobacion === EstadoAprobacionEmpresa::Aprobada;
    }
}
