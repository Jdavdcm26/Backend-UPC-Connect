<?php

namespace App\Models\Autenticacion;

use App\Models\Catalogos\Carrera;
use App\Models\User;
use Database\Factories\Autenticacion\UniversidadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('universidades')]
#[Fillable(['nombre', 'dominio_correo', 'activa'])]
class Universidad extends Model
{
    /** @use HasFactory<UniversidadFactory> */
    use HasFactory, HasUuids;

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
     * @return HasMany<User, $this>
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Carrera, $this>
     */
    public function carreras(): HasMany
    {
        return $this->hasMany(Carrera::class);
    }
}
