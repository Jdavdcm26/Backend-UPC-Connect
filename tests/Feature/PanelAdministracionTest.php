<?php

use App\Models\User;

it('renders the admin panel login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('UPC Connect');
});

it('redirects guests to the admin panel login', function () {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

it('returns 403 to authenticated users outside local until admin roles exist', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});
