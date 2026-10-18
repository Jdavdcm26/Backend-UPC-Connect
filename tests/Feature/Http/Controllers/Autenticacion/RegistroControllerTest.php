<?php

use App\Enums\Autenticacion\EstadoUsuario;
use App\Enums\Autenticacion\Rol;
use App\Models\Autenticacion\Universidad;
use App\Models\User;
use App\Notifications\Autenticacion\VerificarCorreo;
use App\Rules\Autenticacion\CaptchaValido;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

const MENSAJE_REGISTRO = 'Revisa tu correo institucional: te enviamos un enlace para activar tu cuenta.';
const MENSAJE_CAPTCHA = 'No pudimos comprobar que eres una persona. Completa de nuevo la verificación e inténtalo otra vez.';

beforeEach(function () {
    $this->upc = Universidad::factory()->create(['dominio_correo' => 'unicesar.edu.co']);

    $this->respuestaTurnstile = Http::response(['success' => true]);
    $this->respuestaContrasenasFiltradas = Http::response('');

    Notification::fake();
    Http::fake([
        CaptchaValido::URL_VERIFICACION => fn () => $this->respuestaTurnstile,
        'api.pwnedpasswords.com/*' => fn () => $this->respuestaContrasenasFiltradas,
    ]);
});

function datosDeRegistro(array $cambios = []): array
{
    return [
        'email' => 'nuevo@unicesar.edu.co',
        'password' => 'clave-segura-2026',
        'password_confirmation' => 'clave-segura-2026',
        'captcha' => 'token-de-turnstile',
        ...$cambios,
    ];
}

it('creates a pending student account in the university of the email domain', function () {
    $this->postJson(route('api.v1.auth.register'), datosDeRegistro())
        ->assertAccepted()
        ->assertExactJson(['message' => MENSAJE_REGISTRO]);

    $usuario = User::query()->where('email', 'nuevo@unicesar.edu.co')->sole();

    expect($usuario)
        ->universidad_id->toBe($this->upc->id)
        ->rol->toBe(Rol::Estudiante)
        ->estado->toBe(EstadoUsuario::Pendiente)
        ->email_verified_at->toBeNull()
        ->and(Hash::info($usuario->password)['algoName'])->toBe('argon2id');

    $this->assertGuest();
});

it('queues the verification email on the mail queue', function () {
    $this->postJson(route('api.v1.auth.register'), datosDeRegistro())->assertAccepted();

    Notification::assertSentTo(
        User::query()->where('email', 'nuevo@unicesar.edu.co')->sole(),
        VerificarCorreo::class,
        fn (VerificarCorreo $notificacion): bool => $notificacion->queue === 'mail',
    );
});

it('normalizes the email to lowercase before matching the institutional domain', function () {
    $this->postJson(route('api.v1.auth.register'), datosDeRegistro(['email' => '  Nuevo@UNICESAR.edu.co ']))
        ->assertAccepted();

    expect(User::query()->where('email', 'nuevo@unicesar.edu.co')->exists())->toBeTrue();
});

it('returns the same response without creating or notifying when the email is already registered', function () {
    $existente = User::factory()->for($this->upc)->create(['email' => 'nuevo@unicesar.edu.co']);

    $this->postJson(route('api.v1.auth.register'), datosDeRegistro())
        ->assertAccepted()
        ->assertExactJson(['message' => MENSAJE_REGISTRO]);

    expect(User::query()->count())->toBe(1);
    Notification::assertNothingSentTo($existente);
    $this->assertGuest();
});

it('rejects an email outside an active institutional domain with 422', function (string $email) {
    Universidad::factory()->inactiva()->create(['dominio_correo' => 'inactiva.edu.co']);

    $this->postJson(route('api.v1.auth.register'), datosDeRegistro(['email' => $email]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email' => 'Debes registrarte con el correo institucional de tu universidad, por ejemplo nombre@unicesar.edu.co.',
        ]);

    expect(User::query()->exists())->toBeFalse();
})->with([
    'correo personal' => 'nuevo@gmail.com',
    'universidad inactiva' => 'nuevo@inactiva.edu.co',
]);

it('rejects a password shorter than 10 characters with 422', function () {
    $this->postJson(route('api.v1.auth.register'), datosDeRegistro([
        'password' => 'corta123',
        'password_confirmation' => 'corta123',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password' => 'El campo contraseña debe tener al menos 10 caracteres.']);
});

it('rejects a password that does not match its confirmation with 422', function () {
    $this->postJson(route('api.v1.auth.register'), datosDeRegistro(['password_confirmation' => 'otra-clave-2026']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password' => 'La confirmación de contraseña no coincide.']);
});

it('rejects a password found in data leaks with 422', function () {
    $hash = strtoupper(sha1('clave-segura-2026'));
    $this->respuestaContrasenasFiltradas = Http::response(substr($hash, 5).':42');

    $this->postJson(route('api.v1.auth.register'), datosDeRegistro())
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'password' => 'Esta contraseña apareció en una filtración de datos. Elige una diferente.',
        ]);
});

it('rejects the registration with 422 when Turnstile does not validate the captcha', function () {
    $this->respuestaTurnstile = Http::response(['success' => false, 'error-codes' => ['invalid-input-response']]);

    $this->postJson(route('api.v1.auth.register'), datosDeRegistro())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['captcha' => MENSAJE_CAPTCHA]);

    expect(User::query()->exists())->toBeFalse();
});

it('rejects the registration with 422 when Turnstile cannot be reached', function () {
    $this->respuestaTurnstile = Http::failedConnection();

    $this->postJson(route('api.v1.auth.register'), datosDeRegistro())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['captcha' => MENSAJE_CAPTCHA]);
});

it('sends the captcha token and the secret key to Turnstile', function () {
    config(['services.turnstile.secret_key' => 'clave-secreta']);

    $this->postJson(route('api.v1.auth.register'), datosDeRegistro())->assertAccepted();

    Http::assertSent(fn (Request $peticion): bool => $peticion->url() === CaptchaValido::URL_VERIFICACION
        && $peticion['secret'] === 'clave-secreta'
        && $peticion['response'] === 'token-de-turnstile');
});

it('returns the required-field errors when the payload is empty', function () {
    $this->postJson(route('api.v1.auth.register'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email' => 'El campo correo electrónico es obligatorio.',
            'password' => 'El campo contraseña es obligatorio.',
            'captcha' => 'El campo verificación de seguridad es obligatorio.',
        ]);
});

it('returns 429 after five registration attempts per minute from the same IP', function () {
    foreach (range(1, 5) as $intento) {
        $this->postJson(route('api.v1.auth.register'), datosDeRegistro(['email' => "nuevo{$intento}@unicesar.edu.co"]))
            ->assertAccepted();
    }

    $this->postJson(route('api.v1.auth.register'), datosDeRegistro(['email' => 'nuevo6@unicesar.edu.co']))
        ->assertTooManyRequests();
});
