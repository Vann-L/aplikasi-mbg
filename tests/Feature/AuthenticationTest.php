<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login screen can be rendered', function () {
    $this->get(route('login'))->assertOk();
});

test('active user can authenticate', function () {
    $user = User::factory()->petugas()->create(['password' => 'password']);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('petugas.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('user cannot authenticate with wrong password', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('inactive user cannot authenticate', function () {
    $user = User::factory()->inactive()->create(['password' => 'password']);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('authenticated user can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('successful login records last login timestamp', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    expect($user->refresh()->last_login_at)->not->toBeNull();
});
