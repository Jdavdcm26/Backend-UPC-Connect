<?php

use App\Http\Controllers\Autenticacion\ReenvioVerificacionController;
use App\Http\Controllers\Autenticacion\RegistroController;
use App\Http\Controllers\Autenticacion\RestablecimientoContrasenaController;
use App\Http\Controllers\Autenticacion\SolicitudRestablecimientoController;
use App\Http\Controllers\Autenticacion\UsuarioActualController;
use App\Http\Controllers\Autenticacion\VerificacionCorreoController;
use App\Http\Controllers\Catalogos\CarreraController;
use App\Http\Controllers\Catalogos\CategoriaController;
use App\Http\Controllers\Catalogos\HabilidadController;
use App\Http\Controllers\Networking\BloqueoController;
use App\Http\Controllers\Networking\SeguimientoController;
use App\Http\Controllers\Perfiles\HabilidadPerfilController;
use App\Http\Controllers\Perfiles\PerfilController;
use App\Http\Controllers\Perfiles\PerfilPropioController;
use App\Http\Controllers\Postulaciones\EmpresaPostulacionController;
use App\Http\Controllers\Postulaciones\EstadoPostulacionController;
use App\Http\Controllers\Postulaciones\PostulacionController;
use App\Http\Controllers\Publicaciones\AperturaPublicacionController;
use App\Http\Controllers\Publicaciones\ComentarioController;
use App\Http\Controllers\Publicaciones\ImagenPublicacionController;
use App\Http\Controllers\Publicaciones\PublicacionController;
use App\Http\Controllers\Publicaciones\ReaccionController;
use App\Http\Controllers\Vacantes\EmpresaVacanteController;
use App\Http\Controllers\Vacantes\EstadoVacanteController;
use App\Http\Controllers\Vacantes\VacanteController;
use App\Http\Middleware\AsegurarCuentaActiva;
use App\Models\Publicaciones\Publicacion;
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
        Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');

        Route::middleware('can:es-candidato')->group(function () {
            Route::get('/me/perfil', [PerfilPropioController::class, 'show'])->name('me.perfil.show');
            Route::put('/me/perfil', [PerfilPropioController::class, 'update'])->name('me.perfil.update');
            Route::post('/me/perfil/habilidades', [HabilidadPerfilController::class, 'store'])->name('me.perfil.habilidades.store');
            Route::delete('/me/perfil/habilidades/{habilidad}', [HabilidadPerfilController::class, 'destroy'])->whereUuid('habilidad')->name('me.perfil.habilidades.destroy');
            Route::get('/me/postulaciones', [PostulacionController::class, 'index'])->name('me.postulaciones.index');
        });

        Route::get('/perfiles/{slug}', [PerfilController::class, 'show'])->name('perfiles.show');

        Route::get('/vacantes', [VacanteController::class, 'index'])->name('vacantes.index');
        Route::get('/vacantes/{vacante}', [VacanteController::class, 'show'])->whereUuid('vacante')->name('vacantes.show');
        Route::post('/vacantes/{vacante}/postulaciones', [PostulacionController::class, 'store'])->whereUuid('vacante')->name('vacantes.postulaciones.store');

        // Vacantes y postulaciones de la empresa con sesión: {vacantePropia} y
        // {postulacionPropia} solo resuelven las suyas.
        Route::middleware('can:es-empresa')->prefix('empresa')->name('empresa.')->group(function () {
            Route::get('/vacantes', [EmpresaVacanteController::class, 'index'])->name('vacantes.index');
            Route::post('/vacantes', [EmpresaVacanteController::class, 'store'])->name('vacantes.store');
            Route::get('/vacantes/{vacantePropia}', [EmpresaVacanteController::class, 'show'])->name('vacantes.show');
            Route::put('/vacantes/{vacantePropia}', [EmpresaVacanteController::class, 'update'])->name('vacantes.update');
            Route::patch('/vacantes/{vacantePropia}/estado', EstadoVacanteController::class)->name('vacantes.estado');

            Route::get('/vacantes/{vacantePropia}/postulaciones', [EmpresaPostulacionController::class, 'index'])->name('vacantes.postulaciones.index');
            Route::get('/postulaciones/{postulacionPropia}', [EmpresaPostulacionController::class, 'show'])->name('postulaciones.show');
            Route::patch('/postulaciones/{postulacionPropia}/estado', EstadoPostulacionController::class)->name('postulaciones.estado');
        });

        // Publicaciones de la comunidad: estudiantes y egresados interactúan y
        // las empresas solo leen (HU-18 a HU-26).
        Route::middleware('can:viewAny,'.Publicacion::class)->group(function () {
            Route::get('/publicaciones', [PublicacionController::class, 'index'])->name('publicaciones.index');
            Route::post('/publicaciones', [PublicacionController::class, 'store'])->middleware('throttle:20,1')->name('publicaciones.store');
            Route::get('/publicaciones/{publicacion}', [PublicacionController::class, 'show'])->whereUuid('publicacion')->name('publicaciones.show');
            Route::put('/publicaciones/{publicacion}', [PublicacionController::class, 'update'])->whereUuid('publicacion')->name('publicaciones.update');
            Route::delete('/publicaciones/{publicacion}', [PublicacionController::class, 'destroy'])->whereUuid('publicacion')->name('publicaciones.destroy');
            Route::patch('/publicaciones/{publicacion}/apertura', AperturaPublicacionController::class)->whereUuid('publicacion')->name('publicaciones.apertura');

            Route::post('/publicaciones/{publicacion}/imagenes', [ImagenPublicacionController::class, 'store'])->whereUuid('publicacion')->name('publicaciones.imagenes.store');
            Route::delete('/publicaciones/{publicacion}/imagenes/{imagen}', [ImagenPublicacionController::class, 'destroy'])->whereUuid(['publicacion', 'imagen'])->name('publicaciones.imagenes.destroy');

            Route::post('/publicaciones/{publicacion}/reaccion', [ReaccionController::class, 'store'])->whereUuid('publicacion')->name('publicaciones.reaccion.store');
            Route::delete('/publicaciones/{publicacion}/reaccion', [ReaccionController::class, 'destroy'])->whereUuid('publicacion')->name('publicaciones.reaccion.destroy');

            Route::get('/publicaciones/{publicacion}/comentarios', [ComentarioController::class, 'index'])->whereUuid('publicacion')->name('publicaciones.comentarios.index');
            Route::post('/publicaciones/{publicacion}/comentarios', [ComentarioController::class, 'store'])->whereUuid('publicacion')->middleware('throttle:20,1')->name('publicaciones.comentarios.store');
            Route::put('/comentarios/{comentario}', [ComentarioController::class, 'update'])->whereUuid('comentario')->name('comentarios.update');
            Route::delete('/comentarios/{comentario}', [ComentarioController::class, 'destroy'])->whereUuid('comentario')->name('comentarios.destroy');
        });

        // Seguir y bloquear a otras personas (HU-28, HU-31).
        Route::get('/me/bloqueados', [BloqueoController::class, 'index'])->name('me.bloqueados.index');
        Route::post('/usuarios/{usuario}/seguimiento', [SeguimientoController::class, 'store'])->whereUuid('usuario')->name('usuarios.seguimiento.store');
        Route::delete('/usuarios/{usuario}/seguimiento', [SeguimientoController::class, 'destroy'])->whereUuid('usuario')->name('usuarios.seguimiento.destroy');
        Route::post('/usuarios/{usuario}/bloqueo', [BloqueoController::class, 'store'])->whereUuid('usuario')->name('usuarios.bloqueo.store');
        Route::delete('/usuarios/{usuario}/bloqueo', [BloqueoController::class, 'destroy'])->whereUuid('usuario')->name('usuarios.bloqueo.destroy');
    });
});
