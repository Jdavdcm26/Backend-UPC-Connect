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
    private const array PESOS = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nit = is_string($value) ? Empresa::normalizarNit($value) : '';

        if (! preg_match('/^(\d{8,15})-(\d)$/', $nit, $partes) || self::digitoVerificacion($partes[1]) !== (int) $partes[2]) {
            $fail('El NIT no es válido: revisa los números y el dígito de verificación (por ejemplo, 900123456-8).');
        }
    }

    public static function digitoVerificacion(string $numero): int
    {
        $suma = 0;

        foreach (array_reverse(str_split($numero)) as $posicion => $digito) {
            $suma += (int) $digito * self::PESOS[$posicion];
        }

        $residuo = $suma % 11;

        return $residuo > 1 ? 11 - $residuo : $residuo;
    }
}
