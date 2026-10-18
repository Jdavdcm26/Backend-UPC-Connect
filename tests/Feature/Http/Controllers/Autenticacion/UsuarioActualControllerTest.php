<?php

use App\Models\User;

it('returns 401 when there is no session', function () {
    $this->getJson(route('api.v1.me'))
        ->assertUnauthorized();
});

it('returns the authenticated user without sensitive fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.me'))
        ->assertOk()
        ->assertExactJsonStructure(['data' => ['id', 'name', 'email', 'email_verified_at']])
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});
