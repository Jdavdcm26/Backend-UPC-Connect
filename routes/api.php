<?php

use App\Http\Controllers\Autenticacion\ReenvioVerificacionController;
use App\Http\Controllers\Autenticacion\RegistroController;
use App\Http\Controllers\Autenticacion\RestablecimientoContrasenaController;
use App\Http\Controllers\Autenticacion\SolicitudRestablecimientoController;
use App\Http\Controllers\Autenticacion\UsuarioActualController;
use App\Http\Controllers\Autenticacion\VerificacionCorreoController;
use App\Http\Controllers\Catalogos\CarreraController;
use App\Http\Controllers\Catalogos\HabilidadController;
use App\Http\Controllers\Perfiles\PerfilPropioController;
use App\Http\Controllers\Postulaciones\PostulacionController;
use App\Http\Controllers\Vacantes\EmpresaVacanteController;
use App\Http\Controllers\Vacantes\EstadoVacanteController;
use App\Http\Controllers\Vacantes\VacanteController;
use App\Http\Middleware\AsegurarCuentaActiva;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/register', RegistroController::class)
        ->middleware('throttle:registro')
        ->name('auth.register');

    Route::get('/auth/email/verify/{id}/{hash}', VerificacionCorreoController::class)
        ->whereUuid('id')
        ->middleware('throttle:6,1')
        ->name('auth.verification.verify');

    Route::post('/auth/email/verification-notification', ReenvioVerificacionController::class)
        ->middleware('throttle:reenvio-verificacion')
        ->name('auth.verification.send');

    Route::post('/auth/forgot-password', SolicitudRestablecimientoController::class)
        ->middleware('throttle:recuperacion')
        ->name('auth.password.email');

    Route::post('/auth/reset-password', RestablecimientoContrasenaController::class)
        ->middleware('throttle:recuperacion')
        ->name('auth.password.update');

    Route::middleware(['auth:sanctum', AsegurarCuentaActiva::class])->group(function () {
        Route::get('/me', UsuarioActualController::class)->name('me');

        Route::get('/carreras', [CarreraController::class, 'index'])->name('carreras.index');
        Route::get('/habilidades', [HabilidadController::class, 'index'])->name('habilidades.index');

        Route::middleware('can:es-candidato')->group(function () {
            Route::get('/me/perfil', [PerfilPropioController::class, 'show'])->name('me.perfil.show');
            Route::put('/me/perfil', [PerfilPropioController::class, 'update'])->name('me.perfil.update');
        });

        Route::get('/vacantes', [VacanteController::class, 'index'])->name('vacantes.index');
        Route::get('/vacantes/{vacante}', [VacanteController::class, 'show'])->whereUuid('vacante')->name('vacantes.show');
        Route::post('/vacantes/{vacante}/postulaciones', [PostulacionController::class, 'store'])->whereUuid('vacante')->name('vacantes.postulaciones.store');

        // Vacantes de la empresa con sesión: {vacantePropia} solo resuelve las suyas.
        Route::middleware('can:es-empresa')->prefix('empresa')->name('empresa.')->group(function () {
            Route::get('/vacantes', [EmpresaVacanteController::class, 'index'])->name('vacantes.index');
            Route::post('/vacantes', [EmpresaVacanteController::class, 'store'])->name('vacantes.store');
            Route::get('/vacantes/{vacantePropia}', [EmpresaVacanteController::class, 'show'])->name('vacantes.show');
            Route::put('/vacantes/{vacantePropia}', [EmpresaVacanteController::class, 'update'])->name('vacantes.update');
            Route::patch('/vacantes/{vacantePropia}/estado', EstadoVacanteController::class)->name('vacantes.estado');
        });
    });
});
