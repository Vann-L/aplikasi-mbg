<?php

use App\Models\Absensi;
use App\Models\BahanBaku;
use App\Models\Distribusi;
use App\Models\JadwalPegawai;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Penerimaan;
use App\Models\Produksi;
use App\Models\Sekolah;
use App\Models\StokMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can open laporan produksi', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.laporan.produksi'))
        ->assertOk()
        ->assertSee('Laporan Produksi');
});

test('kepala can open laporan produksi', function () {
    $kepala = User::factory()->kepala()->create();

    $this->actingAs($kepala)->get(route('kepala.laporan.produksi'))
        ->assertOk()
        ->assertSee('Laporan Produksi');
});

test('petugas is forbidden from laporan modules', function () {
    $petugas = User::factory()->petugas()->create();

    $this->actingAs($petugas)
        ->get(route('admin.laporan.produksi'))
        ->assertForbidden();

    $this->actingAs($petugas)
        ->get(route('admin.laporan.stok'))
        ->assertForbidden();

    $this->actingAs($petugas)
        ->get(route('admin.laporan.absensi'))
        ->assertForbidden();
});

test('kepala cannot open admin laporan route', function () {
    $kepala = User::factory()->kepala()->create();

    $this->actingAs($kepala)->get(route('admin.laporan.produksi'))->assertForbidden();
});

test('admin cannot open kepala laporan route', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('kepala.laporan.produksi'))->assertForbidden();
});

test('laporan produksi shows target, result, and selisih', function () {
    $admin = User::factory()->admin()->create();
    $menu = Menu::factory()->create(['nama' => 'Nasi Ungu Spesial']);

    Produksi::factory()->create([
        'menu_id' => $menu->id,
        'tanggal' => now()->toDateString(),
        'target_porsi' => 100,
        'hasil_porsi' => 120,
        'status' => Produksi::STATUS_SELESAI,
    ]);

    $this->actingAs($admin)->get(route('admin.laporan.produksi'))
        ->assertOk()
        ->assertSee('Nasi Ungu Spesial')
        ->assertSee('100')
        ->assertSee('120')
        ->assertSee('+20')
        ->assertSee('kelebihan');
});

test('laporan produksi filter by tanggal works', function () {
    $admin = User::factory()->admin()->create();
    $menu = Menu::factory()->create(['nama' => 'Nasi Jadul']);

    Produksi::factory()->create([
        'menu_id' => $menu->id,
        'tanggal' => now()->subDays(3)->toDateString(),
        'target_porsi' => 50,
        'hasil_porsi' => 50,
        'status' => Produksi::STATUS_SELESAI,
    ]);

    $this->actingAs($admin)->get(route('admin.laporan.produksi', [
        'tanggal_awal' => now()->toDateString(),
        'tanggal_akhir' => now()->toDateString(),
    ]))
        ->assertOk()
        ->assertDontSee('Nasi Jadul');
});

test('laporan produksi filter by status works', function () {
    $admin = User::factory()->admin()->create();
    $menu = Menu::factory()->create(['nama' => 'Nasi Berjalan']);
    $menu2 = Menu::factory()->create(['nama' => 'Nasi Selesai']);

    Produksi::factory()->create([
        'menu_id' => $menu->id,
        'tanggal' => now()->toDateString(),
        'status' => Produksi::STATUS_PEMORSIAN,
    ]);

    Produksi::factory()->create([
        'menu_id' => $menu2->id,
        'tanggal' => now()->toDateString(),
        'status' => Produksi::STATUS_SELESAI,
    ]);

    $this->actingAs($admin)->get(route('admin.laporan.produksi', ['status' => Produksi::STATUS_SELESAI]))
        ->assertOk()
        ->assertSee('Nasi Selesai')
        ->assertDontSee('Nasi Berjalan');
});

test('laporan stok shows actual stock', function () {
    $admin = User::factory()->admin()->create();
    $bahan = BahanBaku::factory()->create(['nama' => 'Kacang Tanah', 'satuan' => 'kg', 'stok_minimum' => 30]);

    StokMutation::factory()->create([
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_MASUK,
        'jumlah' => 100,
    ]);

    $this->actingAs($admin)->get(route('admin.laporan.stok'))
        ->assertOk()
        ->assertSee('Kacang Tanah')
        ->assertSee('100')
        ->assertSee('kg')
        ->assertSee('Normal');
});

test('laporan stok status is consistent with phase 5 logic', function () {
    $admin = User::factory()->admin()->create();

    $habis = BahanBaku::factory()->create(['nama' => 'Kunyit', 'stok_minimum' => 30]);
    $rendah = BahanBaku::factory()->create(['nama' => 'Jahe', 'stok_minimum' => 30]);
    $normal = BahanBaku::factory()->create(['nama' => 'Bawang', 'stok_minimum' => 30]);

    StokMutation::factory()->create(['bahan_baku_id' => $habis->id, 'tipe' => StokMutation::TIPE_MASUK, 'jumlah' => 0]);
    StokMutation::factory()->create(['bahan_baku_id' => $rendah->id, 'tipe' => StokMutation::TIPE_MASUK, 'jumlah' => 10]);
    StokMutation::factory()->create(['bahan_baku_id' => $normal->id, 'tipe' => StokMutation::TIPE_MASUK, 'jumlah' => 50]);

    $stokTersedia = BahanBaku::stokTersediaPerBahan();

    expect($habis->statusStok($stokTersedia->get($habis->id) ?? 0))->toBe(BahanBaku::STOK_STATUS_HABIS);
    expect($rendah->statusStok($stokTersedia->get($rendah->id) ?? 0))->toBe(BahanBaku::STOK_STATUS_RENDAH);
    expect($normal->statusStok($stokTersedia->get($normal->id) ?? 0))->toBe(BahanBaku::STOK_STATUS_NORMAL);

    $this->actingAs($admin)->get(route('admin.laporan.stok'))
        ->assertOk()
        ->assertSee('Kunyit');
});

test('laporan stok filter by status works', function () {
    $admin = User::factory()->admin()->create();

    $habis = BahanBaku::factory()->create(['nama' => 'Kunyit', 'stok_minimum' => 30]);
    $normal = BahanBaku::factory()->create(['nama' => 'Bawang', 'stok_minimum' => 750]);

    StokMutation::factory()->create(['bahan_baku_id' => $habis->id, 'tipe' => StokMutation::TIPE_MASUK, 'jumlah' => 0]);
    StokMutation::factory()->create(['bahan_baku_id' => $normal->id, 'tipe' => StokMutation::TIPE_MASUK, 'jumlah' => 750]);

    $this->actingAs($admin)->get(route('admin.laporan.stok', ['status' => BahanBaku::STOK_STATUS_HABIS]))
        ->assertOk()
        ->assertSee('Kunyit')
        ->assertDontSee('750');
});

test('laporan distribusi shows sekolah, porsi, petugas, and status', function () {
    $admin = User::factory()->admin()->create();
    $sekolah = Sekolah::factory()->create(['nama' => 'SDN Contoh 1']);
    $petugas = Pegawai::factory()->create(['nama' => 'Siti Ani']);
    $menu = Menu::factory()->create(['nama' => 'Nasi Ayam Jangkrik']);

    $produksi = Produksi::factory()->create([
        'menu_id' => $menu->id,
        'tanggal' => now()->toDateString(),
        'status' => Produksi::STATUS_SELESAI,
    ]);

    Distribusi::factory()->create([
        'kode_distribusi' => 'DST-LAP-001',
        'produksi_id' => $produksi->id,
        'sekolah_id' => $sekolah->id,
        'tanggal' => now()->toDateString(),
        'jumlah_porsi' => 350,
        'petugas_id' => $petugas->id,
        'kendaraan' => 'Mobil Box',
        'status' => Distribusi::STATUS_DIKIRIM,
    ]);

    $this->actingAs($admin)->get(route('admin.laporan.distribusi'))
        ->assertOk()
        ->assertSee('DST-LAP-001')
        ->assertSee('SDN Contoh 1')
        ->assertSee('Nasi Ayam Jangkrik')
        ->assertSee('Siti Ani')
        ->assertSee('350')
        ->assertSee('Mobil Box')
        ->assertSee('Dikirim');
});

test('laporan distribusi filter by sekolah works', function () {
    $admin = User::factory()->admin()->create();
    $sekolahA = Sekolah::factory()->create(['nama' => 'SDN Filter A']);
    $sekolahB = Sekolah::factory()->create(['nama' => 'SDN Filter B']);
    $menu = Menu::factory()->create();

    $produksi = Produksi::factory()->create([
        'menu_id' => $menu->id,
        'tanggal' => now()->toDateString(),
        'status' => Produksi::STATUS_SELESAI,
    ]);

    Distribusi::factory()->create([
        'kode_distribusi' => 'DST-FILTA',
        'produksi_id' => $produksi->id,
        'sekolah_id' => $sekolahA->id,
        'tanggal' => now()->toDateString(),
        'jumlah_porsi' => 100,
        'status' => Distribusi::STATUS_DIJADWALKAN,
    ]);

    Distribusi::factory()->create([
        'kode_distribusi' => 'DST-FILTB',
        'produksi_id' => $produksi->id,
        'sekolah_id' => $sekolahB->id,
        'tanggal' => now()->toDateString(),
        'jumlah_porsi' => 100,
        'status' => Distribusi::STATUS_DIJADWALKAN,
    ]);

    $this->actingAs($admin)->get(route('admin.laporan.distribusi', ['sekolah' => $sekolahA->id]))
        ->assertOk()
        ->assertSee('DST-FILTA')
        ->assertDontSee('DST-FILTB');
});

test('laporan distribusi filter by tanggal works', function () {
    $admin = User::factory()->admin()->create();
    $sekolah = Sekolah::factory()->create();
    $menu = Menu::factory()->create();

    $produksi = Produksi::factory()->create([
        'menu_id' => $menu->id,
        'tanggal' => now()->toDateString(),
        'status' => Produksi::STATUS_SELESAI,
    ]);

    Distribusi::factory()->create([
        'kode_distribusi' => 'DST-LAMA',
        'produksi_id' => $produksi->id,
        'sekolah_id' => $sekolah->id,
        'tanggal' => now()->subDays(3)->toDateString(),
        'jumlah_porsi' => 100,
        'status' => Distribusi::STATUS_DIKIRIM,
    ]);

    $this->actingAs($admin)->get(route('admin.laporan.distribusi', [
        'tanggal_awal' => now()->toDateString(),
        'tanggal_akhir' => now()->toDateString(),
    ]))
        ->assertOk()
        ->assertDontSee('DST-LAMA');
});

test('laporan penerimaan shows dikirim, diterima, and selisih', function () {
    $admin = User::factory()->admin()->create();
    $sekolah = Sekolah::factory()->create(['nama' => 'SMPN Uji Coba']);
    $menu = Menu::factory()->create();

    $produksi = Produksi::factory()->create([
        'menu_id' => $menu->id,
        'tanggal' => now()->toDateString(),
        'status' => Produksi::STATUS_SELESAI,
    ]);

    $distribusi = Distribusi::factory()->create([
        'kode_distribusi' => 'DST-PRN-001',
        'produksi_id' => $produksi->id,
        'sekolah_id' => $sekolah->id,
        'tanggal' => now()->toDateString(),
        'jumlah_porsi' => 100,
        'status' => Distribusi::STATUS_DITERIMA,
    ]);

    Penerimaan::factory()->create([
        'distribusi_id' => $distribusi->id,
        'jumlah_diterima' => 90,
        'penerima_nama' => 'Pak Guru Penerima',
        'waktu_diterima' => now(),
    ]);

    $this->actingAs($admin)->get(route('admin.laporan.penerimaan'))
        ->assertOk()
        ->assertSee('DST-PRN-001')
        ->assertSee('SMPN Uji Coba')
        ->assertSee('100')
        ->assertSee('90')
        ->assertSee('+10') // selisih 100 - 90
        ->assertSee('Pak Guru Penerima')
        ->assertSee('Diterima');
});

test('laporan penerimaan only lists distributions with a reception record', function () {
    $admin = User::factory()->admin()->create();
    $sekolah = Sekolah::factory()->create();
    $menu = Menu::factory()->create();

    $produksi = Produksi::factory()->create([
        'menu_id' => $menu->id,
        'tanggal' => now()->toDateString(),
        'status' => Produksi::STATUS_SELESAI,
    ]);

    $denganPenerimaan = Distribusi::factory()->create([
        'kode_distribusi' => 'DST-PRN-YES',
        'produksi_id' => $produksi->id,
        'sekolah_id' => $sekolah->id,
        'tanggal' => now()->toDateString(),
        'jumlah_porsi' => 100,
        'status' => Distribusi::STATUS_DITERIMA,
    ]);

    $tanpaPenerimaan = Distribusi::factory()->create([
        'kode_distribusi' => 'DST-PRN-NO',
        'produksi_id' => $produksi->id,
        'sekolah_id' => $sekolah->id,
        'tanggal' => now()->toDateString(),
        'jumlah_porsi' => 100,
        'status' => Distribusi::STATUS_DIKIRIM,
    ]);

    Penerimaan::factory()->create(['distribusi_id' => $denganPenerimaan->id, 'jumlah_diterima' => 100]);

    $this->actingAs($admin)->get(route('admin.laporan.penerimaan'))
        ->assertOk()
        ->assertSee('DST-PRN-YES')
        ->assertDontSee('DST-PRN-NO');

    expect(Penerimaan::where('distribusi_id', $tanpaPenerimaan->id)->count())->toBe(0);
});

test('laporan absensi shows jadwal and absensi', function () {
    $admin = User::factory()->admin()->create();
    $pegawai = Pegawai::factory()->create(['nama' => 'Budi Perkasa']);

    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => now()->toDateString(),
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
    ]);

    Absensi::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => now()->toDateString(),
        'jam_masuk' => now()->startOfDay()->setHour(6)->setMinute(50),
        'jam_pulang' => now()->startOfDay()->setHour(16)->setMinute(5),
        'status' => Absensi::STATUS_HADIR,
    ]);

    $this->actingAs($admin)->get(route('admin.laporan.absensi'))
        ->assertOk()
        ->assertSee('Budi Perkasa')
        ->assertSee('07:00')
        ->assertSee('16:00')
        ->assertSee('06:50')
        ->assertSee('16:05')
        ->assertSee('Hadir');
});

test('laporan absensi status hadir, terlambat, alpa, izin is consistent', function () {
    $admin = User::factory()->admin()->create();

    $hadirP = Pegawai::factory()->create(['nama' => 'Hadir Orang']);
    $telatP = Pegawai::factory()->create(['nama' => 'Telat Orang']);
    $alpaP = Pegawai::factory()->create(['nama' => 'Alpa Orang']);
    $izinP = Pegawai::factory()->create(['nama' => 'Izin Orang']);

    foreach ([$hadirP, $telatP, $alpaP] as $pegawai) {
        JadwalPegawai::factory()->create([
            'pegawai_id' => $pegawai->id,
            'tanggal' => now()->toDateString(),
            'status' => JadwalPegawai::STATUS_TERJADWAL,
        ]);
    }

    Absensi::factory()->create([
        'pegawai_id' => $hadirP->id,
        'tanggal' => now()->toDateString(),
        'status' => Absensi::STATUS_HADIR,
    ]);

    Absensi::factory()->create([
        'pegawai_id' => $telatP->id,
        'tanggal' => now()->toDateString(),
        'status' => Absensi::STATUS_TERLAMBAT,
    ]);

    JadwalPegawai::factory()->create([
        'pegawai_id' => $izinP->id,
        'tanggal' => now()->toDateString(),
        'status' => JadwalPegawai::STATUS_IZIN,
    ]);

    $jadwal = JadwalPegawai::query()->orderBy('pegawai_id')->orderBy('id')->get();

    expect($jadwal->where('pegawai_id', $hadirP->id)->first()->statusRekap())->toBe(Absensi::STATUS_HADIR);
    expect($jadwal->where('pegawai_id', $telatP->id)->first()->statusRekap())->toBe(Absensi::STATUS_TERLAMBAT);
    expect($jadwal->where('pegawai_id', $alpaP->id)->first()->statusRekap())->toBe(Absensi::STATUS_ALPA);
    expect($jadwal->where('pegawai_id', $izinP->id)->first()->statusRekap())->toBe(Absensi::STATUS_IZIN);

    $this->actingAs($admin)->get(route('admin.laporan.absensi'))
        ->assertOk()
        ->assertSee('Hadir')
        ->assertSee('Terlambat')
        ->assertSee('Alpa')
        ->assertSee('Izin');
});

test('laporan absensi filter by pegawai works', function () {
    $admin = User::factory()->admin()->create();
    $p1 = Pegawai::factory()->create(['nama' => 'Awal Waktu']);
    $p2 = Pegawai::factory()->create(['nama' => 'Beres Waktu']);

    foreach ([$p1, $p2] as $pegawai) {
        JadwalPegawai::factory()->create([
            'pegawai_id' => $pegawai->id,
            'tanggal' => now()->toDateString(),
            'jam_masuk' => $pegawai->id === $p1->id ? '06:00' : '09:00',
            'status' => JadwalPegawai::STATUS_TERJADWAL,
        ]);
    }

    $this->actingAs($admin)->get(route('admin.laporan.absensi', ['pegawai' => $p1->id]))
        ->assertOk()
        ->assertSee('06:00')
        ->assertDontSee('09:00');
});

test('laporan absensi filter by tanggal works', function () {
    $admin = User::factory()->admin()->create();
    $pLama = Pegawai::factory()->create(['nama' => 'Kuno Keling']);
    $pBaru = Pegawai::factory()->create(['nama' => 'Fres Cih']);

    JadwalPegawai::factory()->create([
        'pegawai_id' => $pLama->id,
        'tanggal' => now()->subDays(3)->toDateString(),
        'jam_masuk' => '05:00',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
    ]);

    JadwalPegawai::factory()->create([
        'pegawai_id' => $pBaru->id,
        'tanggal' => now()->toDateString(),
        'jam_masuk' => '06:30',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
    ]);

    $this->actingAs($admin)->get(route('admin.laporan.absensi', [
        'tanggal_awal' => now()->toDateString(),
        'tanggal_akhir' => now()->toDateString(),
    ]))
        ->assertOk()
        ->assertSee('06:30')
        ->assertDontSee('05:00');
});
