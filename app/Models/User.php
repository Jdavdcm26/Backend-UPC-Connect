<?php

namespace App\Models;

use App\Enums\Autenticacion\EstadoUsuario;
use App\Enums\Autenticacion\Rol;
use App\Models\Autenticacion\Universidad;
use App\Models\Scopes\UniversidadScope;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Cuenta de acceso de cualquier persona o empresa. La tabla conserva los
 * nombres de Laravel en inglés (users, email, password) para que Fortify,
 * Sanctum y Filament funcionen sin ajustes; las columnas propias del dominio
 * van en español.
 */
#[ScopedBy(UniversidadScope::class)]
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
            'estado' => EstadoUsuario::class,
            'ultimo_acceso_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Universidad, $this>
     */
    public function universidad(): BelongsTo
    {
        return $this->belongsTo(Universidad::class);
    }
}
