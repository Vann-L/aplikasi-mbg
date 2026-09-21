<?php

use App\Models\Absensi;
use App\Models\BahanBaku;
use App\Models\Distribusi;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Penerimaan;
use App\Models\Produksi;
use App\Models\ProduksiBahan;
use App\Models\Sekolah;
use App\Models\StokMutation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('models map to the agreed table names', function () {
    expect((new User)->getTable())->toBe('users');
    expect((new Pegawai)->getTable())->toBe('pegawai');
    expect((new Sekolah)->getTable())->toBe('sekolah');
    expect((new Menu)->getTable())->toBe('menus');
    expect((new BahanBaku)->getTable())->toBe('bahan_baku');
    expect((new StokMutation)->getTable())->toBe('stok_mutations');
    expect((new Produksi)->getTable())->toBe('produksi');
    expect((new ProduksiBahan)->getTable())->toBe('produksi_bahan');
    expect((new Distribusi)->getTable())->toBe('distribusi');
    expect((new Penerimaan)->getTable())->toBe('penerimaan');
    expect((new Absensi)->getTable())->toBe('absensi');
});

test('users and pegawai have a one-to-one relationship', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);

    expect($pegawai->user->id)->toBe($user->id);
    expect($user->pegawai->id)->toBe($pegawai->id);
});

test('pegawai has many absensi records', function () {
    $pegawai = Pegawai::factory()->create();
    $absensi = Absensi::factory()->create(['pegawai_id' => $pegawai->id]);

    expect($absensi->pegawai->id)->toBe($pegawai->id);
    expect($pegawai->absensi)->toHaveCount(1);
});

test('relationship chain menu to stok works end to end', function () {
    $user = User::factory()->create();
    $menu = Menu::factory()->create(['nama' => 'Nasi Ayam']);
    $bahanBaku = BahanBaku::factory()->create();
    $produksi = Produksi::factory()->create(['menu_id' => $menu->id]);
    $produksiBahan = ProduksiBahan::factory()->create([
        'produksi_id' => $produksi->id,
        'bahan_baku_id' => $bahanBaku->id,
    ]);
    $stokMutation = StokMutation::factory()->create([
        'bahan_baku_id' => $bahanBaku->id,
        'user_id' => $user->id,
    ]);

    expect($produksi->menu->id)->toBe($menu->id);
    expect($menu->produksis)->toHaveCount(1);
    expect($produksiBahan->produksi->id)->toBe($produksi->id);
    expect($produksiBahan->bahanBaku->id)->toBe($bahanBaku->id);
    expect($produksi->produksiBahans)->toHaveCount(1);
    expect($bahanBaku->produksiBahans)->toHaveCount(1);
    expect($stokMutation->bahanBaku->id)->toBe($bahanBaku->id);
    expect($stokMutation->user->id)->toBe($user->id);
    expect($bahanBaku->stokMutations)->toHaveCount(1);
});

test('relationship chain produksi to penerimaan works end to end', function () {
    $user = User::factory()->create();
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    $sekolah = Sekolah::factory()->create();
    $produksi = Produksi::factory()->create(['created_by' => $user->id]);
    $distribusi = Distribusi::factory()->create([
        'produksi_id' => $produksi->id,
        'sekolah_id' => $sekolah->id,
        'petugas_id' => $pegawai->id,
        'created_by' => $user->id,
    ]);
    $penerimaan = Penerimaan::factory()->create([
        'distribusi_id' => $distribusi->id,
        'created_by' => $user->id,
    ]);

    expect($distribusi->produksi->id)->toBe($produksi->id);
    expect($distribusi->sekolah->id)->toBe($sekolah->id);
    expect($distribusi->petugas->id)->toBe($pegawai->id);
    expect($distribusi->createdBy->id)->toBe($user->id);
    expect($produksi->distribusis)->toHaveCount(1);
    expect($sekolah->distribusis)->toHaveCount(1);
    expect($pegawai->distribusis)->toHaveCount(1);
    expect($penerimaan->distribusi->id)->toBe($distribusi->id);
    expect($penerimaan->createdBy->id)->toBe($user->id);
    expect($distribusi->penerimaan->id)->toBe($penerimaan->id);
});

test('target produksi can be derived from total porsi of related distribusi', function () {
    $produksi = Produksi::factory()->create();

    Distribusi::factory()->create(['produksi_id' => $produksi->id, 'jumlah_porsi' => 900]);
    Distribusi::factory()->create(['produksi_id' => $produksi->id, 'jumlah_porsi' => 700]);
    Distribusi::factory()->create(['produksi_id' => $produksi->id, 'jumlah_porsi' => 400]);

    expect($produksi->distribusis->sum('jumlah_porsi'))->toBe(2000);
});

test('unique constraints are enforced', function () {
    $pegawai = Pegawai::factory()->create();
    $sekolah = Sekolah::factory()->create();
    $distribusi = Distribusi::factory()->create();

    expect(fn () => Pegawai::factory()->create(['id_pegawai' => $pegawai->id_pegawai]))
        ->toThrow(QueryException::class);

    expect(fn () => Sekolah::factory()->create(['npsn' => $sekolah->npsn]))
        ->toThrow(QueryException::class);

    expect(fn () => Distribusi::factory()->create(['kode_distribusi' => $distribusi->kode_distribusi]))
        ->toThrow(QueryException::class);
});

test('one distribution has only one reception', function () {
    $penerimaan = Penerimaan::factory()->create();

    expect(fn () => Penerimaan::factory()->create(['distribusi_id' => $penerimaan->distribusi_id]))
        ->toThrow(QueryException::class);
});

test('foreign keys block orphan records', function () {
    expect(fn () => StokMutation::factory()->create(['bahan_baku_id' => 999999]))
        ->toThrow(QueryException::class);

    expect(fn () => Distribusi::factory()->create(['sekolah_id' => 999999]))
        ->toThrow(QueryException::class);
});
