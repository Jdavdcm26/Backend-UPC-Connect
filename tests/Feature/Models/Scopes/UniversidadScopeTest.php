<?php

use App\Models\Autenticacion\Universidad;
use App\Models\User;

it('limits user queries to the university of the authenticated user', function () {
    $upc = Universidad::factory()->create();
    $propio = User::factory()->for($upc)->create();
    $companero = User::factory()->for($upc)->create();
    $ajeno = User::factory()->create();

    $this->actingAs($propio);

    expect(User::query()->pluck('id')->all())
        ->toEqualCanonicalizing([$propio->id, $companero->id])
        ->and(User::query()->find($ajeno->id))->toBeNull();
});

it('does not filter user queries without an authenticated user', function () {
    $usuarios = User::factory()->count(2)->create();

    expect(User::query()->pluck('id')->all())
        ->toEqualCanonicalizing($usuarios->pluck('id')->all());
});
