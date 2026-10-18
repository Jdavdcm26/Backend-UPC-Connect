<?php

use App\Http\Controllers\Autenticacion\ReenvioVerificacionController;
use App\Http\Controllers\Autenticacion\RegistroController;
use App\Http\Controllers\Autenticacion\RestablecimientoContrasenaController;
use App\Http\Controllers\Autenticacion\SolicitudRestablecimientoController;
use App\Http\Controllers\Autenticacion\UsuarioActualController;
use App\Http\Controllers\Autenticacion\VerificacionCorreoController;
use App\Http\Controllers\Catalogos\CarreraController;
use App\Http\Controllers\Catalogos\HabilidadController;
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

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', UsuarioActualController::class)->name('me');

        Route::get('/carreras', [CarreraController::class, 'index'])->name('carreras.index');
        Route::get('/habilidades', [HabilidadController::class, 'index'])->name('habilidades.index');
    });
});
