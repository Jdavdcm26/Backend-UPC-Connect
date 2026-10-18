<?php

use App\Enums\Autenticacion\Rol;
use App\Models\Perfiles\Perfil;
use App\Models\Postulaciones\Postulacion;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use App\Models\Vacantes\Vacante;
use App\Rules\Vacantes\NitValido;
use Database\Seeders\DatosDemostracionSeeder;

it('loads an administrator, companies, candidates, vacancies and applications with history', function () {
    $this->seed(DatosDemostracionSeeder::class);

    $usuarios = User::query()->withoutGlobalScopes();

    expect((clone $usuarios)->where('rol', Rol::Admin)->pluck('email')->all())->toBe([DatosDemostracionSeeder::CORREO_ADMIN])
        ->and(Empresa::query()->count())->toBe(3)
        ->and((clone $usuarios)->whereIn('rol', [Rol::Estudiante, Rol::Egresado])->count())->toBe(15)
        ->and(Perfil::query()->count())->toBe(15)
        ->and(Vacante::query()->count())->toBe(12)
        ->and(Postulacion::query()->count())->toBe(30)
        ->and(Postulacion::query()->whereHas('historial', null, '>', 1)->exists())->toBeTrue();
});

it('creates vacancies the public search can find and valid company NITs', function () {
    $this->seed(DatosDemostracionSeeder::class);

    expect(Vacante::query()->whereNull('texto_busqueda')->exists())->toBeFalse()
        ->and(Vacante::query()->where('texto_busqueda', 'like', '%desarrollo web%')->exists())->toBeTrue();

    Empresa::query()->pluck('nit')->each(function (string $nit): void {
        $error = null;
        (new NitValido)->validate('nit', $nit, function (string $mensaje) use (&$error) {
            $error = $mensaje;
        });

        expect($error)->toBeNull();
    });
});

it('does not duplicate the data when run twice', function () {
    $this->seed(DatosDemostracionSeeder::class);
    $this->seed(DatosDemostracionSeeder::class);

    expect(Vacante::query()->count())->toBe(12)
        ->and(Postulacion::query()->count())->toBe(30);
});

it('refuses to run in production even when the seed command is forced', function () {
    $this->app['env'] = 'production';

    app(DatosDemostracionSeeder::class)->run();
})->throws(RuntimeException::class, 'Los datos de demostración no se cargan en producción.');
