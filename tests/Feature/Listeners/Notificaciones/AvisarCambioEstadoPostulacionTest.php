<?php

use App\Enums\Postulaciones\EstadoPostulacion;
use App\Events\Postulaciones\PostulacionEstadoCambiado;
use App\Listeners\Notificaciones\AvisarCambioEstadoPostulacion;
use App\Models\Postulaciones\Postulacion;
use App\Notifications\Notificaciones\EstadoPostulacionCambiado;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->postulacion = Postulacion::factory()->create();
    $this->empresa = $this->postulacion->vacante->empresa;
});

function cambioDeEstado(Postulacion $postulacion, EstadoPostulacion $estado): PostulacionEstadoCambiado
{
    $postulacion->forceFill(['estado' => $estado])->save();
    $cambio = $postulacion->historial()->create([
        'estado' => $estado,
        'cambiado_por' => $postulacion->vacante->empresa->usuario_id,
        'cambiado_en' => now(),
    ]);

    return new PostulacionEstadoCambiado($postulacion, $cambio);
}

it('queues the email on the mail queue when the company changes the state', function () {
    Queue::fake();

    $this->actingAs($this->empresa->usuario)
        ->patchJson(route('api.v1.empresa.postulaciones.estado', $this->postulacion), ['estado' => 'ENTREVISTA'])
        ->assertOk();

    Queue::assertPushedOn('mail', CallQueuedListener::class, fn (CallQueuedListener $trabajo): bool => $trabajo->class === AvisarCambioEstadoPostulacion::class);
});

it('does not queue any email when the change is rejected', function () {
    Queue::fake();

    $this->actingAs($this->empresa->usuario)
        ->patchJson(route('api.v1.empresa.postulaciones.estado', $this->postulacion), ['estado' => 'RECIBIDA'])
        ->assertUnprocessable();

    Queue::assertNothingPushed();
});

it('emails the applicant the new state in Spanish with a link to their applications', function () {
    Notification::fake();
    $evento = cambioDeEstado($this->postulacion, EstadoPostulacion::NoSeleccionado);

    app(AvisarCambioEstadoPostulacion::class)->handle($evento);

    Notification::assertSentTo($this->postulacion->usuario, EstadoPostulacionCambiado::class, function (EstadoPostulacionCambiado $aviso): bool {
        $correo = $aviso->toMail($this->postulacion->usuario);
        $titulo = $this->postulacion->vacante->titulo;

        return $correo->subject === "Tu postulación a «{$titulo}» cambió de estado"
            && in_array("{$this->empresa->razon_social} actualizó tu postulación a la vacante «{$titulo}».", $correo->introLines, true)
            && in_array('Nuevo estado: **No seleccionado**.', $correo->introLines, true)
            && $correo->actionUrl === config('app.frontend_url').'/mis-postulaciones';
    });
});

it('sends a single email when the same change is processed twice', function () {
    Notification::fake();
    $evento = cambioDeEstado($this->postulacion, EstadoPostulacion::Entrevista);

    app(AvisarCambioEstadoPostulacion::class)->handle($evento);
    app(AvisarCambioEstadoPostulacion::class)->handle($evento);

    Notification::assertSentToTimes($this->postulacion->usuario, EstadoPostulacionCambiado::class, 1);
});

it('sends one email per change when the state changes again', function () {
    Notification::fake();

    app(AvisarCambioEstadoPostulacion::class)->handle(cambioDeEstado($this->postulacion, EstadoPostulacion::Entrevista));
    app(AvisarCambioEstadoPostulacion::class)->handle(cambioDeEstado($this->postulacion, EstadoPostulacion::Seleccionado));

    Notification::assertSentToTimes($this->postulacion->usuario, EstadoPostulacionCambiado::class, 2);
});
