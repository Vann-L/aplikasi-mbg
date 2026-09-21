<?php

use App\Models\BahanBaku;
use App\Models\StokMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function stokAdmin(): User
{
    return User::factory()->admin()->create();
}

function stokKepala(): User
{
    return User::factory()->kepala()->create();
}

function stokPetugas(): User
{
    return User::factory()->petugas()->create();
}

function bahanBakuBeras(float $stokMinimum = 10): BahanBaku
{
    return BahanBaku::factory()->create(['nama' => 'Beras', 'satuan' => 'kg', 'stok_minimum' => $stokMinimum]);
}

test('admin dapat melihat halaman stok', function () {
    $user = stokAdmin();
    $bahan = bahanBakuBeras();

    $this->actingAs($user)->get(route('admin.stok.index'))
        ->assertOk()
        ->assertSee('Beras');
});

test('kepala dapat melihat halaman stok', function () {
    $user = stokKepala();
    $bahan = bahanBakuBeras();

    $this->actingAs($user)->get(route('kepala.stok.index'))
        ->assertOk()
        ->assertSee('Beras');
});

test('petugas dapat melihat halaman stok', function () {
    $user = stokPetugas();
    $bahan = bahanBakuBeras();

    $this->actingAs($user)->get(route('petugas.stok.index'))
        ->assertOk()
        ->assertSee('Beras');
});

test('admin dapat melihat riwayat mutasi stok', function () {
    $user = stokAdmin();
    StokMutation::factory()->create(['bahan_baku_id' => bahanBakuBeras(), 'tipe' => StokMutation::TIPE_MASUK, 'keterangan' => 'kiriman mingguan']);

    $this->actingAs($user)->get(route('admin.stok.history'))
        ->assertOk()
        ->assertSee('kiriman mingguan');
});

test('kepala dapat melihat riwayat mutasi stok', function () {
    $user = stokKepala();
    StokMutation::factory()->create(['bahan_baku_id' => bahanBakuBeras(), 'tipe' => StokMutation::TIPE_MASUK]);

    $this->actingAs($user)->get(route('kepala.stok.history'))->assertOk();
});

test('petugas dapat melihat riwayat mutasi stok', function () {
    $user = stokPetugas();
    StokMutation::factory()->create(['bahan_baku_id' => bahanBakuBeras(), 'tipe' => StokMutation::TIPE_MASUK]);

    $this->actingAs($user)->get(route('petugas.stok.history'))->assertOk();
});

test('admin dapat mencatat stok masuk', function () {
    $user = stokAdmin();
    $bahan = bahanBakuBeras();

    $this->actingAs($user)->post(route('admin.stok.store'), [
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_MASUK,
        'jumlah' => 10,
        'tanggal' => now()->toDateString(),
        'keterangan' => 'kiriman awal',
    ])->assertRedirect(route('admin.stok.index'));

    $this->assertDatabaseHas('stok_mutations', [
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_MASUK,
        'jumlah' => 10.0,
        'user_id' => $user->id,
    ]);

    expect($bahan->fresh()->stokTersedia())->toBe(10.0);
});

test('petugas dapat mencatat stok keluar dan stok berkurang', function () {
    $user = stokPetugas();
    $bahan = bahanBakuBeras();
    StokMutation::factory()->create(['bahan_baku_id' => $bahan, 'tipe' => StokMutation::TIPE_MASUK, 'jumlah' => 10]);

    $this->actingAs($user)->post(route('petugas.stok.store'), [
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_KELUAR,
        'jumlah' => 4,
        'tanggal' => now()->toDateString(),
    ])->assertRedirect(route('petugas.stok.index'));

    expect($bahan->fresh()->stokTersedia())->toBe(6.0);
});

test('admin dapat mencatat penyesuaian stok', function () {
    $user = stokAdmin();
    $bahan = bahanBakuBeras();
    StokMutation::factory()->create(['bahan_baku_id' => $bahan, 'tipe' => StokMutation::TIPE_MASUK, 'jumlah' => 10]);

    $this->actingAs($user)->post(route('admin.stok.store'), [
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_PENYESUAIAN,
        'jumlah' => -3,
        'tanggal' => now()->toDateString(),
    ])->assertRedirect(route('admin.stok.index'));

    expect($bahan->fresh()->stokTersedia())->toBe(7.0);
});

test('stok keluar tidak boleh melebihi stok tersedia', function () {
    $user = stokAdmin();
    $bahan = bahanBakuBeras();
    StokMutation::factory()->create(['bahan_baku_id' => $bahan, 'tipe' => StokMutation::TIPE_MASUK, 'jumlah' => 5]);

    $this->actingAs($user)->post(route('admin.stok.store'), [
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_KELUAR,
        'jumlah' => 10,
        'tanggal' => now()->toDateString(),
    ])->assertInvalid(['jumlah'])
        ->assertSessionHasErrors('jumlah');

    expect(StokMutation::query()->where('tipe', StokMutation::TIPE_KELUAR)->count())->toBe(0);
});

test('penyesuaian tidak boleh membuat stok negatif', function () {
    $user = stokAdmin();
    $bahan = bahanBakuBeras();
    StokMutation::factory()->create(['bahan_baku_id' => $bahan, 'tipe' => StokMutation::TIPE_MASUK, 'jumlah' => 5]);

    $this->actingAs($user)->post(route('admin.stok.store'), [
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_PENYESUAIAN,
        'jumlah' => -10,
        'tanggal' => now()->toDateString(),
    ])->assertSessionHasErrors('jumlah');

    expect(StokMutation::count())->toBe(1);
});

test('jumlah harus lebih dari 0 untuk masuk dan keluar', function () {
    $user = stokAdmin();
    $bahan = bahanBakuBeras();

    $this->actingAs($user)->post(route('admin.stok.store'), [
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_MASUK,
        'jumlah' => 0,
        'tanggal' => now()->toDateString(),
    ])->assertSessionHasErrors('jumlah');

    $this->actingAs($user)->post(route('admin.stok.store'), [
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_KELUAR,
        'jumlah' => -5,
        'tanggal' => now()->toDateString(),
    ])->assertSessionHasErrors('jumlah');

    expect(StokMutation::count())->toBe(0);
});

test('penyesuaian tidak boleh bernilai 0', function () {
    $user = stokAdmin();
    $bahan = bahanBakuBeras();

    $this->actingAs($user)->post(route('admin.stok.store'), [
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_PENYESUAIAN,
        'jumlah' => 0,
        'tanggal' => now()->toDateString(),
    ])->assertSessionHasErrors('jumlah');

    expect(StokMutation::count())->toBe(0);
});

test('tipe mutasi harus valid', function () {
    $user = stokAdmin();
    $bahan = bahanBakuBeras();

    $this->actingAs($user)->post(route('admin.stok.store'), [
        'bahan_baku_id' => $bahan->id,
        'tipe' => 'xyz',
        'jumlah' => 5,
        'tanggal' => now()->toDateString(),
    ])->assertSessionHasErrors('tipe');

    expect(StokMutation::count())->toBe(0);
});

test('petugas tidak dapat mencatat penyesuaian', function () {
    $user = stokPetugas();
    $bahan = bahanBakuBeras();

    $this->actingAs($user)->post(route('petugas.stok.store'), [
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_PENYESUAIAN,
        'jumlah' => 5,
        'tanggal' => now()->toDateString(),
    ])->assertSessionHasErrors('tipe');

    expect(StokMutation::count())->toBe(0);
});

test('kepala tidak dapat mencatat mutasi stok', function () {
    $user = stokKepala();
    $bahan = bahanBakuBeras();

    $this->actingAs($user)->post(route('admin.stok.store'), [
        'bahan_baku_id' => $bahan->id,
        'tipe' => StokMutation::TIPE_MASUK,
        'jumlah' => 5,
        'tanggal' => now()->toDateString(),
    ])->assertForbidden();

    expect(StokMutation::count())->toBe(0);
});

test('halaman stok menampilkan indikator habis, rendah, dan normal', function () {
    $user = stokAdmin();

    $bahanHabis = BahanBaku::factory()->create(['nama' => 'Tepung', 'stok_minimum' => 10]);
    $bahanRendah = BahanBaku::factory()->create(['nama' => 'Garam', 'stok_minimum' => 10]);
    StokMutation::factory()->create(['bahan_baku_id' => $bahanRendah, 'tipe' => StokMutation::TIPE_MASUK, 'jumlah' => 3]);
    $bahanNormal = BahanBaku::factory()->create(['nama' => 'Minyak', 'stok_minimum' => 10]);
    StokMutation::factory()->create(['bahan_baku_id' => $bahanNormal, 'tipe' => StokMutation::TIPE_MASUK, 'jumlah' => 20]);

    $this->actingAs($user)->get(route('admin.stok.index'))
        ->assertOk()
        ->assertSee('Habis')
        ->assertSee('Rendah')
        ->assertSee('Normal');
});

test('form tambah mutasi hanya dapat diakses admin dan petugas', function () {
    $this->actingAs(stokAdmin())->get(route('admin.stok.create'))->assertOk()->assertSee('Penyesuaian');
    $this->actingAs(stokKepala())->get(route('admin.stok.create'))->assertForbidden();
    $this->actingAs(stokPetugas())->get(route('petugas.stok.create'))->assertOk()->assertDontSee('Penyesuaian');
});

test('perhitungan stok tersedia memperhitungkan penyesuaian signed', function () {
    $bahan = bahanBakuBeras();
    StokMutation::factory()->create(['bahan_baku_id' => $bahan, 'tipe' => StokMutation::TIPE_MASUK, 'jumlah' => 10]);
    StokMutation::factory()->create(['bahan_baku_id' => $bahan, 'tipe' => StokMutation::TIPE_KELUAR, 'jumlah' => 4]);
    StokMutation::factory()->create(['bahan_baku_id' => $bahan, 'tipe' => StokMutation::TIPE_PENYESUAIAN, 'jumlah' => -2]);

    expect($bahan->fresh()->stokTersedia())->toBe(4.0);
    expect(BahanBaku::stokTersediaPerBahan()->get($bahan->id))->toBe(4.0);
});
