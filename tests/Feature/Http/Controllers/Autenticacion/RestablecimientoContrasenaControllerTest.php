<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;

const NUEVA_CONTRASENA = 'nueva-clave-segura-2026';

beforeEach(fn () => Http::fake(['api.pwnedpasswords.com/*' => Http::response('')]));

function restablecer(User $usuario, string $token, array $cambios = []): TestResponse
{
    return test()->postJson(route('api.v1.auth.password.update'), [
        'token' => $token,
        'email' => $usuario->email,
        'password' => NUEVA_CONTRASENA,
        'password_confirmation' => NUEVA_CONTRASENA,
        ...$cambios,
    ]);
}

it('changes the password with a valid token and lets the user log in with it', function () {
    $usuario = User::factory()->create();

    restablecer($usuario, Password::createToken($usuario))
        ->assertOk()
        ->assertExactJson(['message' => 'Tu contraseña fue restablecida.']);

    $contrasena = $usuario->fresh()->password;

    expect(Hash::check(NUEVA_CONTRASENA, $contrasena))->toBeTrue()
        ->and(Hash::info($contrasena)['algoName'])->toBe('argon2id');

    $this->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => NUEVA_CONTRASENA])->assertOk();
});

it('rejects a token that was already used with 422', function () {
    $usuario = User::factory()->create();
    $token = Password::createToken($usuario);

    restablecer($usuario, $token)->assertOk();

    restablecer($usuario, $token, ['password' => 'otra-clave-segura-2026', 'password_confirmation' => 'otra-clave-segura-2026'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token' => 'El enlace para restablecer la contraseña no es válido o ya venció. Solicita uno nuevo.']);
});

it('rejects an expired token with 422 and keeps the password', function () {
    $usuario = User::factory()->create();
    $token = Password::createToken($usuario);

    $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

    restablecer($usuario, $token)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('token');

    expect(Hash::check('password', $usuario->fresh()->password))->toBeTrue();
});

it('rejects a token issued for another account with 422', function () {
    $usuario = User::factory()->create();
    $otro = User::factory()->create();

    restablecer($usuario, Password::createToken($otro))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('token');
});

it('applies the password policy to the new password', function () {
    $usuario = User::factory()->create();

    restablecer($usuario, Password::createToken($usuario), ['password' => 'corta123', 'password_confirmation' => 'corta123'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password' => 'El campo contraseña debe tener al menos 10 caracteres.']);
});

it('closes other open sessions after the password changes', function () {
    $usuario = User::factory()->create();
    $origen = ['Origin' => 'http://localhost:5173'];

    $cookieDeSesion = $this->withHeaders($origen)
        ->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => 'password'])
        ->assertOk()
        ->getCookie(config('session.cookie'))
        ->getValue();

    $consultarMe = function () use ($origen, $cookieDeSesion) {
        $this->app['auth']->forgetGuards();

        return $this->withHeaders($origen)
            ->withCookie(config('session.cookie'), $cookieDeSesion)
            ->getJson(route('api.v1.me'));
    };

    $consultarMe()->assertOk();

    restablecer($usuario, Password::createToken($usuario))->assertOk();

    $consultarMe()->assertUnauthorized();
});

it('accepts an invitation token for days after it was issued', function () {
    $usuario = User::factory()->create();
    $token = Password::broker('invitaciones')->createToken($usuario);

    $this->travel(2)->days();

    restablecer($usuario, $token)->assertOk();

    expect(Hash::check(NUEVA_CONTRASENA, $usuario->fresh()->password))->toBeTrue();
});

it('rejects an invitation token after seven days', function () {
    $usuario = User::factory()->create();
    $token = Password::broker('invitaciones')->createToken($usuario);

    $this->travel(7)->days();
    $this->travel(1)->minutes();

    restablecer($usuario, $token)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('token');
});

it('invalidates the pending invitation once the password is defined', function () {
    $usuario = User::factory()->create();
    $invitacion = Password::broker('invitaciones')->createToken($usuario);

    restablecer($usuario, Password::createToken($usuario))->assertOk();

    restablecer($usuario, $invitacion, ['password' => 'otra-clave-segura-2026', 'password_confirmation' => 'otra-clave-segura-2026'])
        ->assertUnprocessable();
});
