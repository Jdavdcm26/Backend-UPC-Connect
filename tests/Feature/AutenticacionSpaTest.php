<?php

use App\Models\User;

const ORIGEN_SPA = 'http://localhost:5173';

it('issues the XSRF-TOKEN cookie from the csrf-cookie endpoint', function () {
    $this->get('/api/sanctum/csrf-cookie', ['Origin' => ORIGEN_SPA])
        ->assertNoContent()
        ->assertCookie('XSRF-TOKEN');
});

it('authenticates the SPA by session cookie after logging in', function () {
    $user = User::factory()->create();

    $this->withHeader('Origin', ORIGEN_SPA)
        ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk();

    $this->withHeader('Origin', ORIGEN_SPA)
        ->getJson(route('api.v1.me'))
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('rejects a login with a wrong password with 422', function () {
    $user = User::factory()->create();

    $this->withHeader('Origin', ORIGEN_SPA)
        ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'incorrecta'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->assertGuest();
});

it('ends the session on logout', function () {
    $user = User::factory()->create();

    $this->withHeader('Origin', ORIGEN_SPA)
        ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk();

    $this->withHeader('Origin', ORIGEN_SPA)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    $this->assertGuest();
});

it('allows credentialed CORS requests only from the SPA origin', function (string $origen, bool $permitido) {
    $respuesta = $this->call('OPTIONS', route('api.v1.me'), server: [
        'HTTP_ORIGIN' => $origen,
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ]);

    if ($permitido) {
        $respuesta->assertHeader('Access-Control-Allow-Origin', $origen)
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    } else {
        expect($respuesta->headers->get('Access-Control-Allow-Origin'))->not->toBe($origen);
    }
})->with([
    'origen de la SPA' => [ORIGEN_SPA, true],
    'origen desconocido' => ['https://sitio-ajeno.example', false],
]);
