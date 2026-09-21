<?php

use App\Models\Distribusi;
use App\Models\Pegawai;
use App\Models\Penerimaan;
use App\Models\Produksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function penerimaanAdmin(): User
{
    return User::factory()->admin()->create();
}

function penerimaanKepala(): User
{
    return User::factory()->kepala()->create();
}

/**
 * @return array{user: User, pegawai: Pegawai}
 */
function penerimaanPetugas(): array
{
    $user = User::factory()->petugas()->create();

    return [
        'user' => $user,
        'pegawai' => Pegawai::factory()->create([
            'user_id' => $user->id,
            'status' => Pegawai::STATUS_ACTIVE,
        ]),
    ];
}

function penerimaanDistribusiDikirim(int $porsi = 900, ?Pegawai $petugas = null): Distribusi
{
    $produksi = Produksi::factory()->create();

    return Distribusi::factory()->create([
        'produksi_id' => $produksi->id,
        'tanggal' => now()->toDateString(),
        'jumlah_porsi' => $porsi,
        'status' => Distribusi::STATUS_DIKIRIM,
        'petugas_id' => $petugas?->id,
        'jam_berangkat' => now(),
    ]);
}

/**
 * @return array<string, mixed>
 */
function penerimaanPayload(int $jumlah, ?UploadedFile $foto = null): array
{
    return [
        'jumlah_diterima' => $jumlah,
        'waktu_diterima' => now()->format('Y-m-d H:i'),
        'penerima_nama' => 'PIC Sekolah',
        'foto_bukti' => $foto,
        'catatan' => 'Jumlah diterima sesuai pengiriman.',
    ];
}

test('distribusi berstatus dikirim dapat menerima penerimaan', function () {
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim(900);

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), [
        'jumlah_diterima' => 900,
        'waktu_diterima' => now()->format('Y-m-d H:i'),
        'penerima_nama' => 'PIC Sekolah',
    ])->assertRedirect(route('admin.penerimaan.show', $distribusi))
        ->assertSessionHas('status');

    expect(Penerimaan::where('distribusi_id', $distribusi->id)->exists())->toBeTrue()
        ->and($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DITERIMA);
});

test('distribusi yang belum dikirim ditolak mencatat penerimaan', function () {
    $admin = penerimaanAdmin();
    $produksi = Produksi::factory()->create();
    $distribusi = Distribusi::factory()->create([
        'produksi_id' => $produksi->id,
        'status' => Distribusi::STATUS_DISIAPKAN,
    ]);

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), penerimaanPayload(900))
        ->assertSessionHasErrors('distribusi');

    expect(Penerimaan::count())->toBe(0);
});

test('satu distribusi hanya memiliki satu penerimaan', function () {
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim();
    Penerimaan::factory()->create([
        'distribusi_id' => $distribusi->id,
        'jumlah_diterima' => $distribusi->jumlah_porsi,
    ]);

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), penerimaanPayload($distribusi->jumlah_porsi))
        ->assertSessionHasErrors('distribusi');

    expect(Penerimaan::where('distribusi_id', $distribusi->id)->count())->toBe(1);
});

test('jumlah diterima wajib diisi dan berupa angka positif', function () {
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim();

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), [
        'waktu_diterima' => now()->format('Y-m-d H:i'),
        'penerima_nama' => 'PIC Sekolah',
    ])->assertSessionHasErrors('jumlah_diterima');

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), [
        'jumlah_diterima' => 'abc',
        'waktu_diterima' => now()->format('Y-m-d H:i'),
        'penerima_nama' => 'PIC Sekolah',
    ])->assertSessionHasErrors('jumlah_diterima');
});

test('jumlah diterima tidak boleh 0', function () {
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim();

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), [
        'jumlah_diterima' => 0,
        'waktu_diterima' => now()->format('Y-m-d H:i'),
        'penerima_nama' => 'PIC Sekolah',
    ])->assertSessionHasErrors('jumlah_diterima');
});

test('jumlah diterima tidak boleh lebih besar dari jumlah dikirim', function () {
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim(900);

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), penerimaanPayload(901))
        ->assertSessionHasErrors('jumlah_diterima');

    expect(Penerimaan::count())->toBe(0);
});

test('jumlah diterima lebih kecil dari jumlah dikirim tetap diperbolehkan', function () {
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim(900);

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), penerimaanPayload(890))
        ->assertRedirect(route('admin.penerimaan.show', $distribusi));

    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DITERIMA)
        ->and(Penerimaan::where('distribusi_id', $distribusi->id)->first()->jumlah_diterima)->toBe(890);
});

test('selisih dihitung benar dari jumlah dikirim dan diterima', function () {
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim(900);

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), penerimaanPayload(890));

    expect($distribusi->fresh()->selisihTerkirimDiterima())->toBe(10);
});

test('penerimaan mengubah status distribusi menjadi diterima', function () {
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim();

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), penerimaanPayload($distribusi->jumlah_porsi));

    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_DITERIMA);
});

test('distribusi diterima dapat difinalisasi menjadi selesai', function () {
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim();
    Penerimaan::factory()->create([
        'distribusi_id' => $distribusi->id,
        'jumlah_diterima' => $distribusi->jumlah_porsi,
    ]);
    $distribusi->update(['status' => Distribusi::STATUS_DITERIMA]);

    $this->actingAs($admin)->post(route('admin.penerimaan.selesai', $distribusi))
        ->assertSessionHas('status');

    expect($distribusi->fresh()->status)->toBe(Distribusi::STATUS_SELESAI);
});

test('distribusi berstatus selesai tidak dapat menerima lagi', function () {
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim();
    Penerimaan::factory()->create([
        'distribusi_id' => $distribusi->id,
        'jumlah_diterima' => $distribusi->jumlah_porsi,
    ]);

    $this->actingAs($admin)->post(route('admin.penerimaan.selesai', $distribusi));

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), penerimaanPayload($distribusi->jumlah_porsi))
        ->assertSessionHasErrors('distribusi');

    expect(Penerimaan::where('distribusi_id', $distribusi->id)->count())->toBe(1);
});

test('foto bukti optional dapat diupload dan tersimpan di storage', function () {
    Storage::fake('public');
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim();

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), penerimaanPayload(
        $distribusi->jumlah_porsi,
        UploadedFile::fake()->image('bukti.jpg'),
    ))->assertRedirect(route('admin.penerimaan.show', $distribusi));

    $penerimaan = Penerimaan::where('distribusi_id', $distribusi->id)->first();
    expect($penerimaan->foto_bukti)->not->toBeNull();

    Storage::disk('public')->assertExists($penerimaan->foto_bukti);
});

test('tipe file foto bukti divalidasi', function () {
    Storage::fake('public');
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim();

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), penerimaanPayload(
        $distribusi->jumlah_porsi,
        UploadedFile::fake()->create('dokumen.txt', 100),
    ))->assertSessionHasErrors('foto_bukti');

    expect(Penerimaan::count())->toBe(0);
});

test('penerimaan tidak memiliki alur update atau delete pada MVP', function () {
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim();
    Penerimaan::factory()->create([
        'distribusi_id' => $distribusi->id,
        'jumlah_diterima' => $distribusi->jumlah_porsi,
    ]);

    $this->actingAs($admin)->put(route('admin.penerimaan.store', $distribusi), penerimaanPayload($distribusi->jumlah_porsi))
        ->assertStatus(405);
    $this->actingAs($admin)->delete(route('admin.penerimaan.store', $distribusi))
        ->assertStatus(405);

    expect(Penerimaan::where('distribusi_id', $distribusi->id)->count())->toBe(1);
});

test('admin dapat mencatat penerimaan lengkap dengan foto', function () {
    Storage::fake('public');
    $admin = penerimaanAdmin();
    $distribusi = penerimaanDistribusiDikirim(900);

    $this->actingAs($admin)->post(route('admin.penerimaan.store', $distribusi), penerimaanPayload(
        890,
        UploadedFile::fake()->image('bukti_penerimaan.jpg'),
    ))->assertRedirect(route('admin.penerimaan.show', $distribusi));

    $penerimaan = Penerimaan::where('distribusi_id', $distribusi->id)->first();
    expect($penerimaan->jumlah_diterima)->toBe(890)
        ->and($penerimaan->penerima_nama)->toBe('PIC Sekolah')
        ->and($penerimaan->created_by)->toBe($admin->id)
        ->and($penerimaan->foto_bukti)->not->toBeNull();
});

test('petugas hanya dapat mencatat penerimaan untuk distribusi yang ditugaskan', function () {
    $petugas = penerimaanPetugas();
    $lain = penerimaanPetugas();
    $milik = penerimaanDistribusiDikirim(900, $petugas['pegawai']);
    $bukan = penerimaanDistribusiDikirim(900, $lain['pegawai']);

    $this->actingAs($petugas['user'])->post(route('petugas.penerimaan.store', $milik), penerimaanPayload(900))
        ->assertRedirect(route('petugas.penerimaan.show', $milik));

    expect($milik->fresh()->status)->toBe(Distribusi::STATUS_DITERIMA);

    $this->actingAs($petugas['user'])->post(route('petugas.penerimaan.store', $bukan), penerimaanPayload(900))
        ->assertForbidden();
    $this->actingAs($petugas['user'])->get(route('petugas.penerimaan.show', $bukan))
        ->assertForbidden();
});

test('petugas hanya melihat distribusi yang ditugaskan pada daftar penerimaan', function () {
    $petugas = penerimaanPetugas();
    $lain = penerimaanPetugas();
    $milik = penerimaanDistribusiDikirim(900, $petugas['pegawai']);
    $bukan = penerimaanDistribusiDikirim(900, $lain['pegawai']);

    $this->actingAs($petugas['user'])->get(route('petugas.penerimaan.index'))
        ->assertOk()
        ->assertSee($milik->kode_distribusi)
        ->assertDontSee($bukan->kode_distribusi);
});

test('kepala hanya dapat melihat penerimaan secara read-only', function () {
    $kepala = penerimaanKepala();
    $distribusi = penerimaanDistribusiDikirim();

    $this->actingAs($kepala)->get(route('kepala.penerimaan.index'))
        ->assertOk()
        ->assertSee($distribusi->kode_distribusi);
    $this->actingAs($kepala)->get(route('kepala.penerimaan.show', $distribusi))
        ->assertOk()
        ->assertSee($distribusi->kode_distribusi);

    $this->actingAs($kepala)->post(route('admin.penerimaan.store', $distribusi), penerimaanPayload(900))
        ->assertForbidden();
    $this->actingAs($kepala)->get(route('admin.penerimaan.create', $distribusi))
        ->assertForbidden();
    $this->actingAs($kepala)->post(route('admin.penerimaan.selesai', $distribusi))
        ->assertForbidden();
});

test('guest dialihkan ke login saat mengakses penerimaan', function () {
    $distribusi = penerimaanDistribusiDikirim();

    $this->get(route('admin.penerimaan.index'))->assertRedirect(route('login'));
    $this->post(route('admin.penerimaan.store', $distribusi), penerimaanPayload(900))
        ->assertRedirect(route('login'));
});

test('user dengan role lain ditolak mengakses penerimaan yang bukan haknya', function () {
    $petugas = penerimaanPetugas();
    $distribusi = penerimaanDistribusiDikirim();

    $this->actingAs($petugas['user'])->get(route('admin.penerimaan.index'))->assertForbidden();
    $this->actingAs($petugas['user'])->get(route('admin.penerimaan.create', $distribusi))->assertForbidden();

    $admin = penerimaanAdmin();
    $this->actingAs($admin)->get(route('petugas.penerimaan.index'))->assertForbidden();
});
