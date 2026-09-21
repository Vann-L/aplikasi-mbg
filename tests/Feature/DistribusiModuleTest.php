<?php

use App\Models\Distribusi;
use App\Models\Pegawai;
use App\Models\Produksi;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function distribusiAdmin(): User
{
    return User::factory()->admin()->create();
}

function distribusiKepala(): User
{
    return User::factory()->kepala()->create();
}

function distribusiPetugas(): User
{
    return User::factory()->petugas()->create();
}

function pegawaiPetugas(): Pegawai
{
    return Pegawai::factory()->create([
        'user_id' => User::factory()->petugas(),
        'status' => Pegawai::STATUS_ACTIVE,
    ]);
}

function distribusiDenganStatus(string $status, ?Pegawai $petugas = null, ?int $hasilPorsi = null): Distribusi
{
    $produksi = Produksi::factory()->create();
    $distribusi = Distribusi::factory()->create([
        'produksi_id' => $produksi->id,
        'tanggal' => now()->toDateString(),
        'status' => $status,
        'petugas_id' => $petugas?->id,
    ]);

    if ($hasilPorsi !== null) {
        $produksi->forceFill(['hasil_porsi' => $hasilPorsi])->save();
    }

    return $distribusi;
}

function distribusiDenganPorsi(int $porsi, string $status, ?Pegawai $petugas = null, int $hasilPorsi = 0): Distribusi
{
    $produksi = Produksi::factory()->create();
    $distribusi = Distribusi::factory()->create([
        'produksi_id' => $produksi->id,
        'tanggal' => now()->toDateString(),
        'jumlah_porsi' => $porsi,
        'status' => $status,
        'petugas_id' => $petugas?->id,
    ]);
    $produksi->forceFill(['hasil_porsi' => $hasilPorsi])->save();

    return $distribusi;
}

test('admin dapat melihat daftar distribusi', function () {
    $distribusi = distribusiDenganStatus(Distribusi::STATUS_DIJADWALKAN);

    $this->actingAs(distribusiAdmin())
        ->get(route('admin.distribusi.index'))
        ->assertOk()
        ->assertSee($distribusi->kode_distribusi)
        ->assertSee($distribusi->sekolah->nama);
});

test('admin dapat mengelola penjadwalan distribusi', function () {
    $admin = distribusiAdmin();
    $petugas = pegawaiPetugas();
    $distribusi = distribusiDenganStatus(Distribusi::STATUS_DIJADWALKAN);

    $this->actingAs($admin)->put(route('admin.distribusi.update', $distribusi), [
        'petugas_id' => $petugas->id,
        'kendaraan' => 'Mobil Box 01',
        'tanggal' => now()->toDateString(),
        'jam_berangkat' => '07:15',
    ])->assertRedirect(route('admin.distribusi.show', $distribusi));

    $updated = $distribusi->fresh();
    expect($updated->petugas_id)->toBe($petugas->id);
    expect($updated->kendaraan)->toBe('Mobil Box 01');
    expect($updated->jam_berangkat?->format('H:i'))->toBe('07:15');
});

test('petugas hanya dapat dipilih dari pegawai berakun role operasional', function () {
    $admin = distribusiAdmin();
    $pegawaiKepala = Pegawai::factory()->create([
        'user_id' => distribusiKepala()->id,
        'status' => Pegawai::STATUS_ACTIVE,
    ]);
    $distribusi = distribusiDenganStatus(Distribusi::STATUS_DIJADWALKAN);

    $this->actingAs($admin)->put(route('admin.distribusi.update', $distribusi), [
        'petugas_id' => $pegawaiKepala->id,
        'tanggal' => now()->toDateString(),
    ])->assertSessionHasErrors('petugas_id');
});

test('distribusi yang sudah dikirim tidak dapat dijadwalkan ulang', function () {
    $admin = distribusiAdmin();
    $distribusi = distribusiDenganStatus(Distribusi::STATUS_DIKIRIM);

    $this->actingAs($admin)->put(route('admin.distribusi.update', $distribusi), [
        'tanggal' => now()->toDateString(),
    ])->assertSessionHasErrors('status');
});

test('kepala hanya dapat membaca distribusi', function () {
    $kepala = distribusiKepala();
    $distribusi = distribusiDenganStatus(Distribusi::STATUS_DIJADWALKAN);

    $this->actingAs($kepala)->get(route('kepala.distribusi.index'))->assertOk()
        ->assertSee($distribusi->kode_distribusi);
    $this->actingAs($kepala)->get(route('kepala.distribusi.show', $distribusi))->assertOk();

    $this->actingAs($kepala)->post(route('admin.distribusi.siapkan', $distribusi))->assertForbidden();
    $this->actingAs($kepala)->get(route('admin.distribusi.edit', $distribusi))->assertForbidden();
});

test('petugas hanya dapat melihat distribusi yang ditugaskan', function () {
    $petugasUser = distribusiPetugas();
    $pegawai = Pegawai::factory()->create([
        'user_id' => $petugasUser->id,
        'status' => Pegawai::STATUS_ACTIVE,
    ]);

    $mine = distribusiDenganStatus(Distribusi::STATUS_DIJADWALKAN, $pegawai);
    $other = distribusiDenganStatus(Distribusi::STATUS_DIJADWALKAN, pegawaiPetugas());

    $this->actingAs($petugasUser)->get(route('petugas.distribusi.index'))
        ->assertOk()
        ->assertSee($mine->kode_distribusi)
        ->assertDontSee($other->kode_distribusi);

    $this->actingAs($petugasUser)->get(route('petugas.distribusi.show', $mine))->assertOk();
    $this->actingAs($petugasUser)->get(route('petugas.distribusi.show', $other))->assertForbidden();
});

test('distribusi wajib memiliki sekolah dan jumlah porsi positif', function () {
    $admin = distribusiAdmin();
    $produksi = Produksi::factory()->create();

    $this->actingAs($admin)->post(route('admin.produksi.distribution.store', $produksi), [
        'jumlah_porsi' => 500,
    ])->assertSessionHasErrors('sekolah_id');

    $sekolah = Sekolah::factory()->create(['status' => Sekolah::STATUS_ACTIVE]);

    $this->actingAs($admin)->post(route('admin.produksi.distribution.store', $produksi), [
        'sekolah_id' => $sekolah->id,
        'jumlah_porsi' => 0,
    ])->assertSessionHasErrors('jumlah_porsi');

    expect(Distribusi::count())->toBe(0);
});

test('distribusi harus memiliki petugas sebelum dikirim', function () {
    $admin = distribusiAdmin();
    $distribusi = distribusiDenganStatus(Distribusi::STATUS_DISIAPKAN);

    $this->actingAs($admin)->post(route('admin.distribusi.kirim', $distribusi))
        ->assertSessionHas('error');

    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DISIAPKAN);
});

test('produksi belum memenuhi kebutuhan maka pengiriman ditolak', function () {
    $admin = distribusiAdmin();
    $petugas = pegawaiPetugas();
    $distribusi = distribusiDenganPorsi(2000, Distribusi::STATUS_DISIAPKAN, $petugas, 1980);

    $this->actingAs($admin)->post(route('admin.distribusi.kirim', $distribusi))
        ->assertSessionHas('error');

    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DISIAPKAN);
    expect($distribusi->fresh()->jam_berangkat)->toBeNull();
});

test('produksi mencukupi maka pengiriman boleh', function () {
    $admin = distribusiAdmin();
    $petugas = pegawaiPetugas();
    $distribusi = distribusiDenganPorsi(2000, Distribusi::STATUS_DISIAPKAN, $petugas, 2000);

    $this->actingAs($admin)->post(route('admin.distribusi.kirim', $distribusi))
        ->assertSessionHas('status');

    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DIKIRIM);
    expect($distribusi->fresh()->jam_berangkat)->not->toBeNull();
});

test('produksi berlebih maka pengiriman boleh', function () {
    $admin = distribusiAdmin();
    $petugas = pegawaiPetugas();
    $distribusi = distribusiDenganPorsi(2000, Distribusi::STATUS_DISIAPKAN, $petugas, 2010);

    $this->actingAs($admin)->post(route('admin.distribusi.kirim', $distribusi))
        ->assertSessionHas('status');

    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DIKIRIM);
});

test('transisi status distribusi bekerja berurutan', function () {
    $admin = distribusiAdmin();
    $petugas = pegawaiPetugas();
    $distribusi = distribusiDenganPorsi(2000, Distribusi::STATUS_DIJADWALKAN, $petugas, 2000);

    $this->actingAs($admin)->post(route('admin.distribusi.kirim', $distribusi))
        ->assertSessionHas('error');
    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DIJADWALKAN);

    $this->actingAs($admin)->post(route('admin.distribusi.siapkan', $distribusi))
        ->assertSessionHas('status');
    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DISIAPKAN);

    $this->actingAs($admin)->post(route('admin.distribusi.siapkan', $distribusi))
        ->assertSessionHas('error');
    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DISIAPKAN);

    $this->actingAs($admin)->post(route('admin.distribusi.kirim', $distribusi))
        ->assertSessionHas('status');
    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DIKIRIM);
});

test('petugas yang ditugaskan dapat menjalankan pengiriman', function () {
    $petugasUser = distribusiPetugas();
    $pegawai = Pegawai::factory()->create([
        'user_id' => $petugasUser->id,
        'status' => Pegawai::STATUS_ACTIVE,
    ]);
    $distribusi = distribusiDenganPorsi(800, Distribusi::STATUS_DIJADWALKAN, $pegawai, 800);

    $this->actingAs($petugasUser)->post(route('petugas.distribusi.siapkan', $distribusi))
        ->assertSessionHas('status');
    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DISIAPKAN);

    $this->actingAs($petugasUser)->post(route('petugas.distribusi.kirim', $distribusi))
        ->assertSessionHas('status');
    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DIKIRIM);
});

test('distribusi yang sudah dikirim tidak dapat dihapus', function () {
    $admin = distribusiAdmin();
    $sent = distribusiDenganStatus(Distribusi::STATUS_DIKIRIM);

    $this->actingAs($admin)->delete(route('admin.distribusi.destroy', $sent))
        ->assertSessionHas('error');
    $this->assertDatabaseHas('distribusi', ['id' => $sent->id]);

    $planned = distribusiDenganStatus(Distribusi::STATUS_DIJADWALKAN);
    $produksiId = $planned->produksi_id;

    $this->actingAs($admin)->delete(route('admin.distribusi.destroy', $planned))
        ->assertSessionHas('status');
    $this->assertDatabaseMissing('distribusi', ['id' => $planned->id]);
    expect(Produksi::find($produksiId)->fresh()->target_porsi)->toBe(0);

    $prepared = distribusiDenganStatus(Distribusi::STATUS_DISIAPKAN);
    $this->actingAs($admin)->delete(route('admin.distribusi.destroy', $prepared))
        ->assertSessionHas('status');
    $this->assertDatabaseMissing('distribusi', ['id' => $prepared->id]);
});

test('unauthorized access ditolak', function () {
    $distribusi = distribusiDenganStatus(Distribusi::STATUS_DIJADWALKAN);

    $this->actingAs(distribusiKepala())->post(route('admin.distribusi.siapkan', $distribusi))
        ->assertForbidden();

    $this->actingAs(distribusiPetugas())->get(route('admin.distribusi.index'))
        ->assertForbidden();

    $this->actingAs(distribusiPetugas())->get(route('admin.distribusi.edit', $distribusi))
        ->assertForbidden();
});

test('guest dialihkan ke login saat mengakses distribusi', function () {
    $this->get(route('admin.distribusi.index'))->assertRedirect(route('login'));
});
