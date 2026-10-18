<?php

use App\Actions\Catalogos\FusionarHabilidades;
use App\Enums\Perfiles\NivelHabilidad;
use App\Models\Catalogos\Habilidad;
use App\Models\Perfiles\Perfil;

beforeEach(function () {
    $this->origen = Habilidad::factory()->create(['nombre' => 'Javascript']);
    $this->destino = Habilidad::factory()->create(['nombre' => 'JavaScript']);
});

it('moves the profiles to the target skill and deactivates the source', function () {
    $perfil = Perfil::factory()->create();
    $perfil->habilidades()->attach($this->origen, ['nivel' => NivelHabilidad::Avanzado]);

    $afectados = app(FusionarHabilidades::class)->handle($this->origen, $this->destino);

    expect($afectados)->toBe(1)
        ->and($perfil->habilidades()->pluck('habilidades.id')->all())->toBe([$this->destino->id])
        ->and($perfil->habilidades()->first()->pivot->nivel)->toBe(NivelHabilidad::Avanzado)
        ->and($this->origen->fresh()->activa)->toBeFalse();
});

it('keeps a single target entry with its level for profiles that had both skills', function () {
    $perfil = Perfil::factory()->create();
    $perfil->habilidades()->attach($this->origen, ['nivel' => NivelHabilidad::Basico]);
    $perfil->habilidades()->attach($this->destino, ['nivel' => NivelHabilidad::Intermedio]);

    app(FusionarHabilidades::class)->handle($this->origen, $this->destino);

    $habilidades = $perfil->habilidades()->get();

    expect($habilidades)->toHaveCount(1)
        ->and($habilidades->first()->id)->toBe($this->destino->id)
        ->and($habilidades->first()->pivot->nivel)->toBe(NivelHabilidad::Intermedio);
});

it('refuses to merge a skill into itself', function () {
    app(FusionarHabilidades::class)->handle($this->origen, $this->origen);
})->throws(InvalidArgumentException::class);
