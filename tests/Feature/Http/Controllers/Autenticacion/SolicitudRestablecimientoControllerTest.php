<?php

use App\Models\User;
use App\Notifications\Autenticacion\EnlaceRestablecerContrasena;
use App\Rules\Autenticacion\CaptchaValido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

const MENSAJE_SOLICITUD = 'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña.';

beforeEach(function () {
    $this->respuestaTurnstile = Http::response(['success' => true]);

    Notification::fake();
    Http::fake([CaptchaValido::URL_VERIFICACION => fn () => $this->respuestaTurnstile]);
});

function solicitarRestablecimiento(string $email): TestResponse
{
    return test()->postJson(route('api.v1.auth.password.email'), ['email' => $email, 'captcha' => 'token-de-turnstile']);
}

it('queues a reset link to the SPA on the mail queue for a registered account', function () {
    $usuario = User::factory()->create();

    solicitarRestablecimiento(strtoupper($usuario->email))
        ->assertOk()
        ->assertExactJson(['message' => MENSAJE_SOLICITUD]);

    Notification::assertSentTo($usuario, EnlaceRestablecerContrasena::class, function (EnlaceRestablecerContrasena $notificacion) use ($usuario): bool {
        $enlace = $notificacion->toMail($usuario)->actionUrl;

        return $notificacion->queue === 'mail'
            && str_starts_with($enlace, config('app.frontend_url').'/restablecer-contrasena?')
            && str_contains($enlace, 'token='.$notificacion->token)
            && str_contains($enlace, 'email='.urlencode($usuario->email));
    });
});

it('returns the same response for an unknown email without sending anything', function () {
    solicitarRestablecimiento('nadie@unicesar.edu.co')
        ->assertOk()
        ->assertExactJson(['message' => MENSAJE_SOLICITUD]);

    Notification::assertNothingSent();
});

it('returns the same response when a link was requested moments ago', function () {
    $usuario = User::factory()->create();

    solicitarRestablecimiento($usuario->email)->assertOk();

    solicitarRestablecimiento($usuario->email)
        ->assertOk()
        ->assertExactJson(['message' => MENSAJE_SOLICITUD]);

    Notification::assertSentToTimes($usuario, EnlaceRestablecerContrasena::class, 1);
});

it('rejects the request with 422 when Turnstile does not validate the captcha', function () {
    $this->respuestaTurnstile = Http::response(['success' => false]);
    $usuario = User::factory()->create();

    solicitarRestablecimiento($usuario->email)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('captcha');

    Notification::assertNothingSent();
});

it('returns 429 after five requests per minute from the same IP', function () {
    foreach (range(1, 5) as $intento) {
        solicitarRestablecimiento("nadie{$intento}@unicesar.edu.co")->assertOk();
    }

    solicitarRestablecimiento('nadie6@unicesar.edu.co')->assertTooManyRequests();
});
