<?php

namespace App\Rules\Vacantes;

use App\Models\Vacantes\Empresa;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * NIT colombiano con su dígito de verificación (algoritmo de la DIAN). Acepta
 * puntos, espacios y el guion opcional: "900.123.456-8" o "9001234568".
 */
class NitValido implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nit = is_string($value) ? Empresa::normalizarNit($value) : '';

        if (! preg_match('/^(\d{8,15})-(\d)$/', $nit, $partes) || Empresa::digitoVerificacionNit($partes[1]) !== (int) $partes[2]) {
            $fail('El NIT no es válido: revisa los números y el dígito de verificación (por ejemplo, 900123456-8).');
        }
    }
}
