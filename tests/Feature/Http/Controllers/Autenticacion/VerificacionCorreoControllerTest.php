<?php

use App\Enums\Autenticacion\EstadoUsuario;
use App\Models\User;
use App\Notifications\Autenticacion\VerificarCorreo;
use Illuminate\Support\Facades\URL;

/**
 * Convierte el enlace del correo (página de la SPA) en la llamada que la SPA
 * hace a la API con los mismos parámetros firmados.
 */
function llamadaDeLaSpa(string $enlaceDelCorreo): string
{
    parse_str((string) parse_url($enlaceDelCorreo, PHP_URL_QUERY), $parametros);

    return "/api/v1/auth/email/verify/{$parametros['id']}/{$parametros['hash']}?".http_build_query([
        'expires' => $parametros['expires'],
        'signature' => $parametros['signature'],
    ]);
}

function enlaceDelCorreo(User $usuario): string
{
    return (new VerificarCorreo)->toMail($usuario)->actionUrl;
}

it('sends a link that opens the verification page of the SPA', function () {
    $enlace = enlaceDelCorreo(User::factory()->unverified()->create());

    expect($enlace)->toStartWith(config('app.frontend_url').'/verificar-correo?');
});

it('activates a pending account from the email link without a session', function () {
    $usuario = User::factory()->unverified()->create();

    $this->getJson(llamadaDeLaSpa(enlaceDelCorreo($usuario)))
        ->assertOk()
        ->assertExactJson(['message' => 'Tu cuenta está activa. Ya puedes iniciar sesión.']);

    expect($usuario->fresh())
        ->hasVerifiedEmail()->toBeTrue()
        ->estado->toBe(EstadoUsuario::Activo);

    $this->assertGuest();
});

it('returns 409 when the link was already used', function () {
    $usuario = User::factory()->unverified()->create();
    $llamada = llamadaDeLaSpa(enlaceDelCorreo($usuario));

    $this->getJson($llamada)->assertOk();

    $this->getJson($llamada)
        ->assertConflict()
        ->assertJsonPath('codigo', 'cuenta_ya_activa');
});

it('returns 410 and keeps the account pending when the link expired', function () {
    $usuario = User::factory()->unverified()->create();
    $llamada = llamadaDeLaSpa(enlaceDelCorreo($usuario));

    $this->travel(61)->minutes();

    $this->getJson($llamada)
        ->assertGone()
        ->assertJsonPath('codigo', 'enlace_vencido');

    expect($usuario->fresh()->estado)->toBe(EstadoUsuario::Pendiente);
});

it('returns 403 when the signature was tampered with', function () {
    $usuario = User::factory()->unverified()->create();

    $this->getJson(llamadaDeLaSpa(enlaceDelCorreo($usuario)).'0')
        ->assertForbidden()
        ->assertJsonPath('codigo', 'enlace_invalido');

    expect($usuario->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('returns 403 when the signed hash does not match the account email', function () {
    $usuario = User::factory()->unverified()->create();

    $rutaFirmada = URL::temporarySignedRoute(
        'api.v1.auth.verification.verify',
        now()->addHour(),
        ['id' => $usuario->id, 'hash' => hash('sha256', 'otro@unicesar.edu.co')],
        absolute: false,
    );

    $this->getJson($rutaFirmada)
        ->assertForbidden()
        ->assertJsonPath('codigo', 'enlace_invalido');
});

it('verifies the email but keeps a suspended account suspended', function () {
    $usuario = User::factory()->unverified()->suspendido()->create();

    $this->getJson(llamadaDeLaSpa(enlaceDelCorreo($usuario)))->assertOk();

    expect($usuario->fresh())
        ->hasVerifiedEmail()->toBeTrue()
        ->estado->toBe(EstadoUsuario::Suspendido);
});

it('returns 404 when the id is not a UUID', function () {
    $this->getJson('/api/v1/auth/email/verify/123/abc')->assertNotFound();
});
