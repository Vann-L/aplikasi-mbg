<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin is redirected to admin area after login', function () {
    $user = User::factory()->admin()->create(['password' => 'password']);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));
});

test('kepala is redirected to kepala area after login', function () {
    $user = User::factory()->kepala()->create(['password' => 'password']);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('kepala.dashboard'));
});

test('petugas is redirected to petugas area after login', function () {
    $user = User::factory()->petugas()->create(['password' => 'password']);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('petugas.dashboard'));
});

test('admin can access admin area', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
});

test('kepala cannot access admin area', function () {
    $user = User::factory()->kepala()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
});

test('petugas cannot access admin area', function () {
    $user = User::factory()->petugas()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
});

test('guest is redirected to login for protected area', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('admin cannot access kepala or petugas dashboard', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->get(route('kepala.dashboard'))->assertForbidden();
    $this->actingAs($user)->get(route('petugas.dashboard'))->assertForbidden();
});

test('petugas cannot access kepala dashboard', function () {
    $user = User::factory()->petugas()->create();

    $this->actingAs($user)->get(route('kepala.dashboard'))->assertForbidden();
});

test('role cannot access another role module route', function () {
    $user = User::factory()->petugas()->create();

    $this->actingAs($user)->get(route('admin.pegawai.index'))->assertForbidden();
    $this->actingAs($user)->get(route('kepala.laporan.index'))->assertForbidden();
});

test('admin can open a placeholder module page', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->get(route('admin.user.index'))
        ->assertOk()
        ->assertSee('Modul ini akan tersedia');
});
