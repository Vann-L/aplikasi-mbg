<?php

use App\Models\Absensi;
use App\Models\BahanBaku;
use App\Models\Distribusi;
use App\Models\JadwalPegawai;
use App\Models\KalenderOperasional;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\PolaJadwal;
use App\Models\Produksi;
use App\Models\Sekolah;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\PolaJadwalFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    CarbonImmutable::setTestNow(null);
});

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

test('petugas dashboard shows today effective schedule and attendance', function () {
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
        ->assertViewHas('jadwalEfektifHariIni', fn ($jadwal) => $jadwal !== null && $jadwal->sumber === 'override' && $jadwal->jamMasuk?->format('H:i') === '07:00')
        ->assertViewHas('absensiHariIni', fn ($absensi) => $absensi !== null && $absensi->pegawai_id === $pegawai->id);
});

test('petugas dashboard renders without a linked pegawai record', function () {
    $user = User::factory()->petugas()->create();

    $this->actingAs($user)->get(route('petugas.dashboard'))
        ->assertOk()
        ->assertViewHas('jadwalEfektifHariIni', null)
        ->assertViewHas('absensiHariIni', null);
});

test('petugas dashboard menampilkan jadwal efektif dari pola saat tanpa override', function () {
    CarbonImmutable::setTestNow('2026-01-08 07:00:00', config('app.timezone'));
    $user = User::factory()->petugas()->create();
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id, 'status' => Pegawai::STATUS_ACTIVE]);
    PolaJadwal::factory()->create([
        'pegawai_id' => $pegawai->id,
        'berlaku_mulai' => '2026-01-05',
        'hari' => PolaJadwalFactory::polaHari(['07:00', '16:00']),
    ]);

    $this->actingAs($user)->get(route('petugas.dashboard'))
        ->assertOk()
        ->assertViewHas('jadwalEfektifHariIni', fn ($jadwal) => $jadwal !== null && $jadwal->sumber === 'pola' && $jadwal->jamMasuk?->format('H:i') === '07:00')
        ->assertSee('07:00')
        ->assertDontSee('Belum ada jadwal');
});

test('petugas dashboard tidak cukup membaca override saja (kalender libur menang)', function () {
    $user = User::factory()->petugas()->create();
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id, 'status' => Pegawai::STATUS_ACTIVE]);
    KalenderOperasional::factory()->create(['tanggal' => today(), 'status' => KalenderOperasional::STATUS_LIBUR]);
    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => today(),
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
    ]);

    $this->actingAs($user)->get(route('petugas.dashboard'))
        ->assertOk()
        ->assertViewHas('jadwalEfektifHariIni', fn ($jadwal) => $jadwal !== null && $jadwal->sumber === 'kalender' && $jadwal->status === JadwalPegawai::STATUS_LIBUR)
        ->assertSee('Libur')
        ->assertDontSee('07:00');
});
