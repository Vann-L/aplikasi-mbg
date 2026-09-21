<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('user can change own password', function () {
    $user = User::factory()->petugas()->create(['password' => 'password']);

    $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('user cannot change password with wrong current password', function () {
    $user = User::factory()->petugas()->create(['password' => 'password']);

    $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('admin can reset another user password', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->petugas()->create();

    $this->actingAs($admin)->post(route('admin.users.reset-password', $target), [
        'password' => 'reset-password',
        'password_confirmation' => 'reset-password',
    ])->assertRedirect(route('admin.dashboard'));

    expect(Hash::check('reset-password', $target->refresh()->password))->toBeTrue();
});

test('non admin cannot reset another user password', function () {
    $actor = User::factory()->kepala()->create();
    $target = User::factory()->petugas()->create();

    $this->actingAs($actor)->post(route('admin.users.reset-password', $target), [
        'password' => 'reset-password',
        'password_confirmation' => 'reset-password',
    ])->assertForbidden();
});
