<?php

use App\Http\Controllers\Autenticacion\ReenvioVerificacionController;
use App\Http\Controllers\Autenticacion\RegistroController;
use App\Http\Controllers\Autenticacion\UsuarioActualController;
use App\Http\Controllers\Autenticacion\VerificacionCorreoController;
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

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', UsuarioActualController::class)->name('me');
    });
});
