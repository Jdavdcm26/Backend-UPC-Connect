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
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * Deja el NIT como "número-dígito", sin puntos ni espacios, para que dos
     * formas de escribir el mismo NIT no pasen como empresas distintas.
     */
    public static function normalizarNit(string $nit): string
    {
        $digitos = preg_replace('/\D/', '', $nit);

        return strlen($digitos) < 2 ? $digitos : substr($digitos, 0, -1).'-'.substr($digitos, -1);
    }

    /**
     * Dígito de verificación de un NIT según el algoritmo de la DIAN.
     */
    public static function digitoVerificacionNit(string $numero): int
    {
        $pesos = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        $suma = 0;

        foreach (array_reverse(str_split($numero)) as $posicion => $digito) {
            $suma += (int) $digito * $pesos[$posicion];
        }

        $residuo = $suma % 11;

        return $residuo > 1 ? 11 - $residuo : $residuo;
    }

    public function estaAprobada(): bool
    {
        return $this->estado_aprobacion === EstadoAprobacionEmpresa::Aprobada;
    }

    /**
     * @return HasMany<Vacante, $this>
     */
    public function vacantes(): HasMany
    {
        return $this->hasMany(Vacante::class);
    }
}
