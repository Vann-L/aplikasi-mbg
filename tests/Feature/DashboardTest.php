<?php

use App\Models\Absensi;
use App\Models\BahanBaku;
use App\Models\Distribusi;
use App\Models\JadwalPegawai;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Produksi;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin dashboard shows master data totals', function () {
    Pegawai::factory()->count(2)->create();
    Sekolah::factory()->count(3)->create();
    Menu::factory()->count(4)->create();
    BahanBaku::factory()->count(5)->create();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('totalPegawai', 2)
        ->assertViewHas('totalSekolah', 3)
        ->assertViewHas('totalMenu', 4)
        ->assertViewHas('totalBahanBaku', 5);
});

test('admin dashboard counts only todays distributions', function () {
    Distribusi::factory()->create(['tanggal' => today()]);
    Distribusi::factory()->create(['tanggal' => today()->subDay()]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('distribusiHariIni', 1);
});

test('kepala dashboard summarises todays operations', function () {
    Produksi::factory()->create([
        'tanggal' => today(),
        'target_porsi' => 1000,
        'hasil_porsi' => 250,
    ]);

    $kepala = User::factory()->kepala()->create();

    $this->actingAs($kepala)->get(route('kepala.dashboard'))
        ->assertOk()
        ->assertViewHas('produksiHariIniCount', 1)
        ->assertViewHas('targetHariIni', 1000)
        ->assertViewHas('hasilHariIni', 250)
        ->assertViewHas('progressProduksi', 25);
});

test('kepala dashboard lists ingredients below minimum stock', function () {
    $bahan = BahanBaku::factory()->create(['stok_minimum' => 10]);
    $bahan->stokMutations()->create([
        'tipe' => 'masuk',
        'jumlah' => 4,
        'tanggal' => today(),
    ]);

    $kepala = User::factory()->kepala()->create();

    $this->actingAs($kepala)->get(route('kepala.dashboard'))
        ->assertOk()
        ->assertViewHas('bahanBakuDiBawahMinimum', fn ($items) => $items->count() === 1);
});

test('petugas dashboard shows today schedule and attendance', function () {
    $user = User::factory()->petugas()->create();
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);

    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => today(),
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    Absensi::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => today(),
    ]);

    $this->actingAs($user)->get(route('petugas.dashboard'))
        ->assertOk()
        ->assertViewHas('jadwalHariIni', fn ($jadwal) => $jadwal !== null && $jadwal->pegawai_id === $pegawai->id)
        ->assertViewHas('absensiHariIni', fn ($absensi) => $absensi !== null && $absensi->pegawai_id === $pegawai->id);
});

test('petugas dashboard renders without a linked pegawai record', function () {
    $user = User::factory()->petugas()->create();

    $this->actingAs($user)->get(route('petugas.dashboard'))
        ->assertOk()
        ->assertViewHas('jadwalHariIni', null)
        ->assertViewHas('absensiHariIni', null);
});
