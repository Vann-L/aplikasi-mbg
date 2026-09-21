<?php

use App\Models\BahanBaku;
use App\Models\Distribusi;
use App\Models\Menu;
use App\Models\Produksi;
use App\Models\ProduksiBahan;
use App\Models\Sekolah;
use App\Models\StokMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function produksiAdmin(): User
{
    return User::factory()->admin()->create();
}

function produksiKepala(): User
{
    return User::factory()->kepala()->create();
}

function produksiPetugas(): User
{
    return User::factory()->petugas()->create();
}

function produksiDraft(mixed $user = null): Produksi
{
    return Produksi::factory()->create([
        'menu_id' => Menu::factory()->create()->id,
        'tanggal' => now()->toDateString(),
        'created_by' => ($user ?? produksiAdmin())->id,
    ]);
}

function tambahStokBahan(BahanBaku $bahan, float $jumlah): void
{
    StokMutation::factory()->create([
        'bahan_baku_id' => $bahan,
        'tipe' => StokMutation::TIPE_MASUK,
        'jumlah' => $jumlah,
        'tanggal' => now()->toDateString(),
    ]);
}

test('admin dapat membuat produksi', function () {
    $menu = Menu::factory()->create(['nama' => 'Nasi Ayam']);

    $admin = produksiAdmin();

    $this->actingAs($admin)->post(route('admin.produksi.store'), [
        'menu_id' => $menu->id,
        'tanggal' => now()->toDateString(),
    ])->assertRedirect(route('admin.produksi.show', Produksi::first()));

    $this->assertDatabaseHas('produksi', [
        'menu_id' => $menu->id,
        'status' => Produksi::STATUS_BELUM_DIMULAI,
        'target_porsi' => 0,
    ]);
    expect(Produksi::first()->created_by)->toBe($admin->id);
});

test('petugas dapat menjalankan produksi dari awal hingga hasil', function () {
    $user = produksiPetugas();
    $menu = Menu::factory()->create();
    $bahan = BahanBaku::factory()->create(['nama' => 'Beras', 'satuan' => 'kg', 'stok_minimum' => 1]);
    tambahStokBahan($bahan, 100);

    $this->actingAs($user)->post(route('petugas.produksi.store'), [
        'menu_id' => $menu->id,
        'tanggal' => now()->toDateString(),
    ])->assertRedirect(route('petugas.produksi.show', Produksi::first()));

    $produksi = Produksi::first();

    $this->actingAs($user)->post(route('petugas.produksi.ingredient.store', $produksi), [
        'bahan_baku_id' => $bahan->id,
        'jumlah' => 50,
    ])->assertSessionHas('status');

    $this->actingAs($user)->post(route('petugas.produksi.start', $produksi))->assertSessionHas('status');

    expect($produksi->fresh()->status)->toBe(Produksi::STATUS_PERSIAPAN);
    expect($produksi->fresh()->stock_deducted_at)->not->toBeNull();

    $this->actingAs($user)->put(route('petugas.produksi.status.update', $produksi), [
        'status' => Produksi::STATUS_PENGOLAHAN,
    ])->assertSessionHas('status');

    $this->actingAs($user)->put(route('petugas.produksi.result.update', $produksi), [
        'hasil_porsi' => 1980,
    ])->assertSessionHas('status');

    expect($produksi->fresh()->hasil_porsi)->toBe(1980);
});

test('kepala hanya dapat membaca produksi', function () {
    $kepala = produksiKepala();
    $produksi = produksiDraft($kepala);
    $menu = $produksi->menu;

    $this->actingAs($kepala)->get(route('kepala.produksi.index'))->assertOk();
    $this->actingAs($kepala)->get(route('kepala.produksi.show', $produksi))->assertOk();

    $this->actingAs($kepala)->post(route('admin.produksi.store'), [
        'menu_id' => $menu->id,
        'tanggal' => now()->toDateString(),
    ])->assertForbidden();

    $this->actingAs($kepala)->get(route('admin.produksi.create'))->assertForbidden();
    $this->actingAs($kepala)->post(route('admin.produksi.start', $produksi))->assertForbidden();
});

test('target produksi dihitung dari total distribusi 900+700+400 = 2000', function () {
    $admin = produksiAdmin();
    $produksi = produksiDraft($admin);
    $sekolahs = Sekolah::factory()->count(3)->create(['status' => 'active']);

    foreach ([900, 700, 400] as $index => $porsi) {
        $this->actingAs($admin)->post(route('admin.produksi.distribution.store', $produksi), [
            'sekolah_id' => $sekolahs[$index]->id,
            'jumlah_porsi' => $porsi,
        ])->assertSessionHas('status');
    }

    expect($produksi->fresh()->kebutuhanPorsi())->toBe(2000);
    expect($produksi->fresh()->target_porsi)->toBe(2000);
});

test('perubahan jumlah distribusi mengubah target', function () {
    $admin = produksiAdmin();
    $produksi = produksiDraft($admin);
    $sekolah = Sekolah::factory()->create(['status' => 'active']);

    $this->actingAs($admin)->post(route('admin.produksi.distribution.store', $produksi), [
        'sekolah_id' => $sekolah->id,
        'jumlah_porsi' => 900,
    ]);

    expect($produksi->fresh()->target_porsi)->toBe(900);

    $distribusi = $produksi->distribusis()->first();

    $this->actingAs($admin)->put(route('admin.produksi.distribution.update', [$produksi, $distribusi]), [
        'sekolah_id' => $sekolah->id,
        'jumlah_porsi' => 1200,
    ])->assertSessionHas('status');

    expect($produksi->fresh()->target_porsi)->toBe(1200);

    $this->actingAs($admin)->delete(route('admin.produksi.distribution.destroy', [$produksi, $distribusi]))
        ->assertSessionHas('status');

    expect($produksi->fresh()->target_porsi)->toBe(0);
});

test('bahan produksi dapat ditambahkan dengan satuan konsisten', function () {
    $admin = produksiAdmin();
    $produksi = produksiDraft($admin);
    $bahan = BahanBaku::factory()->create(['nama' => 'Wortel', 'satuan' => 'kg']);

    $this->actingAs($admin)->post(route('admin.produksi.ingredient.store', $produksi), [
        'bahan_baku_id' => $bahan->id,
        'jumlah' => 20,
    ])->assertSessionHas('status');

    $this->assertDatabaseHas('produksi_bahan', [
        'produksi_id' => $produksi->id,
        'bahan_baku_id' => $bahan->id,
        'jumlah' => 20.0,
        'satuan' => 'kg',
    ]);

    $this->actingAs($admin)->post(route('admin.produksi.ingredient.store', $produksi), [
        'bahan_baku_id' => $bahan->id,
        'jumlah' => 0,
    ])->assertSessionHasErrors('jumlah');
});

test('stok cukup maka produksi dapat dimulai', function () {
    $admin = produksiAdmin();
    $produksi = produksiDraft($admin);
    $bahan = BahanBaku::factory()->create(['nama' => 'Ayam', 'satuan' => 'kg']);
    tambahStokBahan($bahan, 100);
    ProduksiBahan::factory()->create(['produksi_id' => $produksi, 'bahan_baku_id' => $bahan, 'jumlah' => 50, 'satuan' => 'kg']);

    $this->actingAs($admin)->post(route('admin.produksi.start', $produksi))->assertSessionHas('status');

    expect($produksi->fresh()->status)->toBe(Produksi::STATUS_PERSIAPAN);
    expect($produksi->fresh()->stock_deducted_at)->not->toBeNull();
    expect($produksi->fresh()->started_at)->not->toBeNull();
});

test('stok tidak cukup maka produksi ditolak', function () {
    $admin = produksiAdmin();
    $produksi = produksiDraft($admin);
    $bahan = BahanBaku::factory()->create(['nama' => 'Ayam', 'satuan' => 'kg']);
    tambahStokBahan($bahan, 10);
    ProduksiBahan::factory()->create(['produksi_id' => $produksi, 'bahan_baku_id' => $bahan, 'jumlah' => 50, 'satuan' => 'kg']);

    $this->actingAs($admin)->post(route('admin.produksi.start', $produksi))->assertSessionHas('error');

    expect($produksi->fresh()->status)->toBe(Produksi::STATUS_BELUM_DIMULAI);
    expect($produksi->fresh()->stock_deducted_at)->toBeNull();
    expect(StokMutation::where('tipe', StokMutation::TIPE_KELUAR)->count())->toBe(0);
    expect($bahan->fresh()->stokTersedia())->toBe(10.0);
});

test('stok keluar dibuat saat produksi dimulai', function () {
    $admin = produksiAdmin();
    $produksi = produksiDraft($admin);
    $bahan = BahanBaku::factory()->create(['nama' => 'Beras', 'satuan' => 'kg']);
    tambahStokBahan($bahan, 100);
    ProduksiBahan::factory()->create(['produksi_id' => $produksi, 'bahan_baku_id' => $bahan, 'jumlah' => 40, 'satuan' => 'kg']);

    $this->actingAs($admin)->post(route('admin.produksi.start', $produksi));

    $this->assertDatabaseHas('stok_mutations', [
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_KELUAR,
        'jumlah' => 40.0,
        'tanggal' => $produksi->tanggal->format('Y-m-d H:i:s'),
        'user_id' => $admin->id,
    ]);

    expect($bahan->fresh()->stokTersedia())->toBe(60.0);
});

test('stok hanya dipotong satu kali saat produksi dimulai', function () {
    $admin = produksiAdmin();
    $produksi = produksiDraft($admin);
    $bahan = BahanBaku::factory()->create(['nama' => 'Beras', 'satuan' => 'kg']);
    tambahStokBahan($bahan, 100);
    ProduksiBahan::factory()->create(['produksi_id' => $produksi, 'bahan_baku_id' => $bahan, 'jumlah' => 40, 'satuan' => 'kg']);

    $this->actingAs($admin)->post(route('admin.produksi.start', $produksi));
    $this->actingAs($admin)->post(route('admin.produksi.start', $produksi))->assertSessionHas('error');

    expect(StokMutation::where('tipe', StokMutation::TIPE_KELUAR)->count())->toBe(1);
    expect($bahan->fresh()->stokTersedia())->toBe(60.0);
});

test('mulai produksi dibatalkan total jika salah satu bahan tidak cukup', function () {
    $admin = produksiAdmin();
    $produksi = produksiDraft($admin);
    $cukup = BahanBaku::factory()->create(['nama' => 'Ayam', 'satuan' => 'kg']);
    $kurang = BahanBaku::factory()->create(['nama' => 'Beras', 'satuan' => 'kg']);
    tambahStokBahan($cukup, 100);
    tambahStokBahan($kurang, 5);
    ProduksiBahan::factory()->create(['produksi_id' => $produksi, 'bahan_baku_id' => $cukup, 'jumlah' => 50, 'satuan' => 'kg']);
    ProduksiBahan::factory()->create(['produksi_id' => $produksi, 'bahan_baku_id' => $kurang, 'jumlah' => 20, 'satuan' => 'kg']);

    $this->actingAs($admin)->post(route('admin.produksi.start', $produksi))->assertSessionHas('error');

    expect(StokMutation::where('tipe', StokMutation::TIPE_KELUAR)->count())->toBe(0);
    expect($produksi->fresh()->status)->toBe(Produksi::STATUS_BELUM_DIMULAI);
    expect($cukup->fresh()->stokTersedia())->toBe(100.0);
    expect($kurang->fresh()->stokTersedia())->toBe(5.0);
});

test('hasil produksi dapat dicatat', function () {
    $admin = produksiAdmin();
    $produksi = produksiDraft($admin);
    $bahan = BahanBaku::factory()->create(['nama' => 'Ayam', 'satuan' => 'kg']);
    tambahStokBahan($bahan, 100);
    ProduksiBahan::factory()->create(['produksi_id' => $produksi, 'bahan_baku_id' => $bahan, 'jumlah' => 50, 'satuan' => 'kg']);

    $this->actingAs($admin)->post(route('admin.produksi.start', $produksi));

    $this->actingAs($admin)->put(route('admin.produksi.result.update', $produksi), [
        'hasil_porsi' => 1980,
    ])->assertSessionHas('status');

    expect($produksi->fresh()->hasil_porsi)->toBe(1980);

    $this->actingAs($admin)->put(route('admin.produksi.result.update', $produksi), [
        'hasil_porsi' => -5,
    ])->assertSessionHasErrors('hasil_porsi');
});

test('kekurangan produksi dihitung benar', function () {
    $produksi = produksiDraft();
    $sekolahs = Sekolah::factory()->count(3)->create(['status' => 'active']);
    foreach ([900, 700, 400] as $index => $porsi) {
        Distribusi::factory()->create([
            'produksi_id' => $produksi,
            'sekolah_id' => $sekolahs[$index]->id,
            'jumlah_porsi' => $porsi,
        ]);
    }
    $produksi->forceFill(['hasil_porsi' => 1980, 'target_porsi' => 2000])->save();

    expect($produksi->kebutuhanPorsi())->toBe(2000);
    expect($produksi->selisihPorsi())->toBe(-20);
});

test('kelebihan produksi dihitung benar', function () {
    $produksi = produksiDraft();
    $sekolah = Sekolah::factory()->create(['status' => 'active']);
    Distribusi::factory()->create([
        'produksi_id' => $produksi,
        'sekolah_id' => $sekolah->id,
        'jumlah_porsi' => 2000,
    ]);
    $produksi->forceFill(['hasil_porsi' => 2010, 'target_porsi' => 2000])->save();

    expect($produksi->selisihPorsi())->toBe(10);
});

test('produksi dengan histori tidak dapat dihapus', function () {
    $admin = produksiAdmin();

    $denganDistribusi = produksiDraft();
    $sekolah = Sekolah::factory()->create(['status' => 'active']);
    Distribusi::factory()->create(['produksi_id' => $denganDistribusi, 'sekolah_id' => $sekolah->id, 'jumlah_porsi' => 500]);

    $denganBahan = produksiDraft();
    ProduksiBahan::factory()->create(['produksi_id' => $denganBahan]);

    $sudahDimulai = produksiDraft();
    $bahan = BahanBaku::factory()->create(['nama' => 'Ayam', 'satuan' => 'kg']);
    tambahStokBahan($bahan, 100);
    ProduksiBahan::factory()->create(['produksi_id' => $sudahDimulai, 'bahan_baku_id' => $bahan, 'jumlah' => 10, 'satuan' => 'kg']);
    $this->actingAs($admin)->post(route('admin.produksi.start', $sudahDimulai));

    foreach ([$denganDistribusi, $denganBahan, $sudahDimulai] as $produksi) {
        $this->actingAs($admin)->delete(route('admin.produksi.destroy', $produksi))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('produksi', ['id' => $produksi->id]);
    }

    $draft = produksiDraft();
    $this->actingAs($admin)->delete(route('admin.produksi.destroy', $draft))
        ->assertSessionHas('status');
    $this->assertDatabaseMissing('produksi', ['id' => $draft->id]);
});

test('authorization role produksi bekerja', function () {
    $petugas = produksiPetugas();
    $kepala = produksiKepala();
    $produksi = produksiDraft();

    $this->actingAs($petugas)->post(route('admin.produksi.store'), [
        'menu_id' => $produksi->menu_id,
        'tanggal' => now()->toDateString(),
    ])->assertForbidden();

    $this->actingAs($petugas)->delete(route('admin.produksi.destroy', $produksi))->assertForbidden();

    $this->actingAs($kepala)->post(route('admin.produksi.start', $produksi))->assertForbidden();
});

test('guest dialihkan ke login saat mengakses produksi', function () {
    $this->get(route('admin.produksi.index'))->assertRedirect(route('login'));
    $this->get(route('petugas.produksi.show', produksiDraft()))->assertRedirect(route('login'));
});
