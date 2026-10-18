<?php

use App\Models\User;
use App\Notifications\Autenticacion\VerificarCorreo;
use Illuminate\Support\Facades\Notification;

const MENSAJE_REENVIO = 'Si tu cuenta está pendiente de activación, te enviamos un nuevo enlace a tu correo.';

beforeEach(fn () => Notification::fake());

it('sends a new verification link to a pending account', function () {
    $usuario = User::factory()->unverified()->create();

    $this->postJson(route('api.v1.auth.verification.send'), ['email' => strtoupper($usuario->email)])
        ->assertAccepted()
        ->assertExactJson(['message' => MENSAJE_REENVIO]);

    Notification::assertSentTo($usuario, VerificarCorreo::class);
});

it('returns the same response without sending anything for unknown or verified accounts', function (string $correo) {
    $this->postJson(route('api.v1.auth.verification.send'), ['email' => $correo])
        ->assertAccepted()
        ->assertExactJson(['message' => MENSAJE_REENVIO]);

    Notification::assertNothingSent();
})->with([
    'correo no registrado' => fn (): string => 'nadie@unicesar.edu.co',
    'cuenta ya verificada' => fn (): string => User::factory()->create()->email,
]);

it('returns 422 when the email is missing', function () {
    $this->postJson(route('api.v1.auth.verification.send'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'El campo correo electrónico es obligatorio.']);
});

it('returns 422 instead of failing when the email is not a string', function () {
    $this->postJson(route('api.v1.auth.verification.send'), ['email' => ['a@unicesar.edu.co']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('returns 429 after three resend requests per minute for the same email', function () {
    $usuario = User::factory()->unverified()->create();

    foreach (range(1, 3) as $intento) {
        $this->postJson(route('api.v1.auth.verification.send'), ['email' => $usuario->email])->assertAccepted();
    }

    $this->postJson(route('api.v1.auth.verification.send'), ['email' => $usuario->email])
        ->assertTooManyRequests();
});
