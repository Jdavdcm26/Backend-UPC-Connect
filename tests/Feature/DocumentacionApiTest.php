<?php

it('serves the OpenAPI 3.1 document of the v1 API outside production', function () {
    $respuesta = $this->getJson('/docs/api.json')
        ->assertOk()
        ->assertJsonPath('servers.0.url', url('/api/v1'))
        ->assertJsonPath('components.securitySchemes.apiKey.in', 'cookie')
        ->assertJsonPath('paths./me.get.operationId', 'v1.me');

    expect($respuesta->json('openapi'))->toStartWith('3.1.');
});

it('returns 403 for the API documentation in production', function () {
    $this->app['env'] = 'production';

    $this->getJson('/docs/api.json')->assertForbidden();
    $this->get('/docs/api')->assertForbidden();
});
