<?php

use App\Models\User;
use Illuminate\Testing\TestResponse;

function iniciarSesion(string $email, string $password = 'password', string $ip = '10.0.0.1'): TestResponse
{
    return test()->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson('/api/v1/auth/login', ['email' => $email, 'password' => $password]);
}

it('rejects a pending account with a clear message when the password is correct', function () {
    $usuario = User::factory()->unverified()->create();

    iniciarSesion($usuario->email)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email' => 'Tu cuenta aún no está activa. Abre el enlace que te enviamos a tu correo institucional para activarla.',
        ]);

    $this->assertGuest();
});

it('rejects a suspended account with a clear message when the password is correct', function () {
    $usuario = User::factory()->suspendido()->create();

    iniciarSesion($usuario->email)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email' => 'Tu cuenta está suspendida. Si crees que es un error, comunícate con la administración de la plataforma.',
        ]);

    $this->assertGuest();
});

it('does not reveal the account status when the password is wrong', function () {
    $usuario = User::factory()->suspendido()->create();

    iniciarSesion($usuario->email, 'incorrecta')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email' => 'El correo o la contraseña no son correctos. Revísalos e inténtalo de nuevo.',
        ]);
});

it('accepts the email regardless of letter case', function () {
    $usuario = User::factory()->create(['email' => 'activo@unicesar.edu.co']);

    iniciarSesion('Activo@UNICESAR.edu.co')->assertOk();

    $this->assertAuthenticatedAs($usuario);
});

it('records the last access when the user logs in', function () {
    $this->freezeSecond();
    $usuario = User::factory()->create(['updated_at' => now()->subDay()]);

    iniciarSesion($usuario->email)->assertOk();

    expect($usuario->fresh())
        ->ultimo_acceso_en->toEqual(now())
        ->updated_at->toEqual(now()->subDay());
});

it('locks the login with 429 after five consecutive failures and tells the waiting time', function () {
    $this->freezeSecond();
    $usuario = User::factory()->create();

    foreach (range(1, 5) as $intento) {
        iniciarSesion($usuario->email, 'incorrecta')->assertUnprocessable();
    }

    iniciarSesion($usuario->email)
        ->assertTooManyRequests()
        ->assertJsonValidationErrors(['email' => 'Demasiados intentos de inicio de sesión. Inténtalo de nuevo en 60 segundos.']);

    $this->assertGuest();
});

it('resets the failure count after a successful login', function () {
    $usuario = User::factory()->create();

    foreach (range(1, 4) as $intento) {
        iniciarSesion($usuario->email, 'incorrecta')->assertUnprocessable();
    }

    iniciarSesion($usuario->email)->assertOk();
    $this->post('/api/v1/auth/logout');

    foreach (range(1, 4) as $intento) {
        iniciarSesion($usuario->email, 'incorrecta')->assertUnprocessable();
    }
});

it('limits an account to 20 attempts per minute across different IPs', function () {
    $usuario = User::factory()->create();

    foreach (range(1, 20) as $intento) {
        iniciarSesion($usuario->email, 'incorrecta', "10.0.1.{$intento}")->assertUnprocessable();
    }

    iniciarSesion($usuario->email, 'password', '10.0.2.1')
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertJsonPath('message', fn (string $mensaje): bool => str_starts_with($mensaje, 'Demasiados intentos de inicio de sesión.'));
});
