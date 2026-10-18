<?php

/*
|--------------------------------------------------------------------------
| Monolito modular
|--------------------------------------------------------------------------
|
| Cada módulo del Release 1 es un subnamespace dentro de las carpetas estándar
| de Laravel, por ejemplo App\Models\Vacantes o App\Http\Controllers\Vacantes.
|
| Un módulo puede usar de otro solo su parte pública: modelos, enumerados,
| eventos, contratos y recursos de API. Controladores, Form Requests, acciones,
| listeners, jobs, policies, notificaciones y correos son internos: solo los
| usa su propio módulo (y los service providers que los registran).
|
*/

$modulos = [
    'Autenticacion',
    'Perfiles',
    'Catalogos',
    'Notificaciones',
    'Vacantes',
    'Postulaciones',
    'Administracion',
    'Analitica',
];

$capasPublicas = [
    'Models',
    'Enums',
    'Events',
    'Contracts',
    'Http\Resources',
];

$capasInternas = [
    'Http\Controllers',
    'Http\Requests',
    'Actions',
    'Listeners',
    'Jobs',
    'Policies',
    'Notifications',
    'Mail',
];

$namespacesDelModulo = fn (string $modulo, array $capas): array => array_map(
    fn (string $capa): string => "App\\{$capa}\\{$modulo}",
    $capas,
);

arch()->preset()->php();

arch()->preset()->security();

arch()->preset()->laravel();

foreach ($modulos as $modulo) {
    arch("las capas internas de {$modulo} solo se usan dentro del módulo")
        ->expect($namespacesDelModulo($modulo, $capasInternas))
        ->toOnlyBeUsedIn([
            ...$namespacesDelModulo($modulo, [...$capasPublicas, ...$capasInternas]),
            'App\Providers',
        ]);
}
