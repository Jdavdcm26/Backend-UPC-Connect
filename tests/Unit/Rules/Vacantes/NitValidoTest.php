<?php

use App\Rules\Vacantes\NitValido;

function validarNit(mixed $nit): ?string
{
    $error = null;
    (new NitValido)->validate('nit', $nit, function (string $mensaje) use (&$error) {
        $error = $mensaje;
    });

    return $error;
}

it('accepts real NITs with their check digit in any common format', function (string $nit) {
    expect(validarNit($nit))->toBeNull();
})->with([
    'Bancolombia con guion' => '890903938-8',
    'Ecopetrol con puntos' => '899.999.068-1',
    'DIAN sin guion' => '8001972684',
]);

it('rejects malformed NITs or a wrong check digit', function (mixed $nit) {
    expect(validarNit($nit))->toBe('El NIT no es válido: revisa los números y el dígito de verificación (por ejemplo, 900123456-8).');
})->with([
    'dígito equivocado' => '890903938-7',
    'muy corto' => '1234-5',
    'vacío' => '',
    'no es texto' => [['890903938-8']],
]);
