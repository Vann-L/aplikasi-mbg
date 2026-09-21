<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin sidebar shows admin modules only', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('admin.pegawai.index'))
        ->assertSee(route('admin.user.index'))
        ->assertSee(route('admin.laporan.index'))
        ->assertDontSee(route('petugas.jadwal.index'));
});

test('kepala sidebar shows monitoring modules only', function () {
    $kepala = User::factory()->kepala()->create();

    $this->actingAs($kepala)->get(route('kepala.dashboard'))
        ->assertOk()
        ->assertSee(route('kepala.laporan.index'))
        ->assertDontSee(route('admin.user.index'))
        ->assertDontSee(route('admin.pegawai.index'))
        ->assertDontSee(route('petugas.jadwal.index'));
});

test('petugas sidebar shows operational modules only', function () {
    $petugas = User::factory()->petugas()->create();

    $this->actingAs($petugas)->get(route('petugas.dashboard'))
        ->assertOk()
        ->assertSee(route('petugas.jadwal.index'))
        ->assertDontSee(route('admin.user.index'))
        ->assertDontSee(route('admin.pegawai.index'))
        ->assertDontSee(route('kepala.laporan.index'));
});
