<?php

use App\Http\Controllers\Autenticacion\UsuarioActualController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', UsuarioActualController::class)->name('me');
    });
});
