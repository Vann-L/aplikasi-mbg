<?php

use App\Models\BahanBaku;
use App\Models\ProduksiBahan;
use App\Models\StokMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bahanBakuData(array $overrides = []): array
{
    return array_merge([
        'nama' => 'Beras',
        'satuan' => 'kg',
        'stok_minimum' => 50,
        'status' => BahanBaku::STATUS_ACTIVE,
    ], $overrides);
}

test('admin can view bahan baku index', function () {
    $admin = User::factory()->admin()->create();
    BahanBaku::factory()->create(['nama' => 'Beras']);

    $this->actingAs($admin)->get(route('admin.bahan-baku.index'))
        ->assertOk()
        ->assertSee('Beras');
});

test('admin can create a bahan baku', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.bahan-baku.store'), bahanBakuData())
        ->assertRedirect(route('admin.bahan-baku.index'));

    $this->assertDatabaseHas('bahan_baku', [
        'nama' => 'Beras',
        'satuan' => 'kg',
        'status' => BahanBaku::STATUS_ACTIVE,
    ]);
});

test('bahan baku creation requires nama', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.bahan-baku.store'), bahanBakuData(['nama' => '']))
        ->assertSessionHasErrors('nama');
});

test('bahan baku creation requires satuan', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.bahan-baku.store'), bahanBakuData(['satuan' => '']))
        ->assertSessionHasErrors('satuan');
});

test('bahan baku stok_minimum must be numeric', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.bahan-baku.store'), bahanBakuData(['stok_minimum' => 'banyak']))
        ->assertSessionHasErrors('stok_minimum');
});

test('bahan baku stok_minimum cannot be negative', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.bahan-baku.store'), bahanBakuData(['stok_minimum' => -1]))
        ->assertSessionHasErrors('stok_minimum');
});

test('bahan baku status must be valid', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.bahan-baku.store'), bahanBakuData(['status' => 'aktif']))
        ->assertSessionHasErrors('status');
});

test('admin can view bahan baku detail', function () {
    $admin = User::factory()->admin()->create();
    $bahanBaku = BahanBaku::factory()->create(['nama' => 'Beras']);

    $this->actingAs($admin)->get(route('admin.bahan-baku.show', $bahanBaku))
        ->assertOk()
        ->assertSee('Beras');
});

test('admin can update a bahan baku', function () {
    $admin = User::factory()->admin()->create();
    $bahanBaku = BahanBaku::factory()->create(['nama' => 'Beras']);

    $this->actingAs($admin)->put(route('admin.bahan-baku.update', $bahanBaku), bahanBakuData([
        'nama' => 'Beras Premium',
        'stok_minimum' => 75,
        'status' => BahanBaku::STATUS_INACTIVE,
    ]))->assertRedirect(route('admin.bahan-baku.index'));

    $this->assertDatabaseHas('bahan_baku', [
        'id' => $bahanBaku->id,
        'nama' => 'Beras Premium',
        'status' => BahanBaku::STATUS_INACTIVE,
    ]);
});

test('admin can delete a bahan baku without operational data', function () {
    $admin = User::factory()->admin()->create();
    $bahanBaku = BahanBaku::factory()->create();

    $this->actingAs($admin)->delete(route('admin.bahan-baku.destroy', $bahanBaku))
        ->assertRedirect(route('admin.bahan-baku.index'));

    $this->assertDatabaseMissing('bahan_baku', ['id' => $bahanBaku->id]);
});

test('bahan baku used by produksi_bahan cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    $bahanBaku = BahanBaku::factory()->create();
    ProduksiBahan::factory()->create(['bahan_baku_id' => $bahanBaku->id]);

    $this->actingAs($admin)->delete(route('admin.bahan-baku.destroy', $bahanBaku))
        ->assertRedirect(route('admin.bahan-baku.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('bahan_baku', ['id' => $bahanBaku->id]);
});

test('bahan baku used by stok_mutations cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    $bahanBaku = BahanBaku::factory()->create();
    StokMutation::factory()->create(['bahan_baku_id' => $bahanBaku->id]);

    $this->actingAs($admin)->delete(route('admin.bahan-baku.destroy', $bahanBaku))
        ->assertRedirect(route('admin.bahan-baku.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('bahan_baku', ['id' => $bahanBaku->id]);
});

test('kepala can view bahan baku but cannot manage it', function () {
    $kepala = User::factory()->kepala()->create();
    $bahanBaku = BahanBaku::factory()->create();

    $this->actingAs($kepala)->get(route('admin.bahan-baku.index'))->assertOk();
    $this->actingAs($kepala)->get(route('admin.bahan-baku.show', $bahanBaku))->assertOk();
    $this->actingAs($kepala)->get(route('admin.bahan-baku.create'))->assertForbidden();
    $this->actingAs($kepala)->post(route('admin.bahan-baku.store'), bahanBakuData())->assertForbidden();
    $this->actingAs($kepala)->get(route('admin.bahan-baku.edit', $bahanBaku))->assertForbidden();
    $this->actingAs($kepala)->put(route('admin.bahan-baku.update', $bahanBaku), bahanBakuData())->assertForbidden();
    $this->actingAs($kepala)->delete(route('admin.bahan-baku.destroy', $bahanBaku))->assertForbidden();
});

test('petugas can view bahan baku but cannot manage it', function () {
    $petugas = User::factory()->petugas()->create();
    $bahanBaku = BahanBaku::factory()->create();

    $this->actingAs($petugas)->get(route('admin.bahan-baku.index'))->assertOk();
    $this->actingAs($petugas)->get(route('admin.bahan-baku.show', $bahanBaku))->assertOk();
    $this->actingAs($petugas)->get(route('admin.bahan-baku.create'))->assertForbidden();
    $this->actingAs($petugas)->post(route('admin.bahan-baku.store'), bahanBakuData())->assertForbidden();
    $this->actingAs($petugas)->put(route('admin.bahan-baku.update', $bahanBaku), bahanBakuData())->assertForbidden();
    $this->actingAs($petugas)->delete(route('admin.bahan-baku.destroy', $bahanBaku))->assertForbidden();
});

test('guest is redirected to login from bahan baku module', function () {
    $this->get(route('admin.bahan-baku.index'))->assertRedirect(route('login'));
    $this->post(route('admin.bahan-baku.store'), bahanBakuData())->assertRedirect(route('login'));
});

test('bahan baku index can be searched', function () {
    $admin = User::factory()->admin()->create();
    BahanBaku::factory()->create(['nama' => 'Beras']);
    BahanBaku::factory()->create(['nama' => 'Gula Pasir']);

    $this->actingAs($admin)->get(route('admin.bahan-baku.index', ['search' => 'Beras']))
        ->assertOk()
        ->assertSee('Beras')
        ->assertDontSee('Gula Pasir');
});

test('bahan baku index can be filtered by status', function () {
    $admin = User::factory()->admin()->create();
    BahanBaku::factory()->create(['nama' => 'Beras', 'status' => BahanBaku::STATUS_ACTIVE]);
    BahanBaku::factory()->create(['nama' => 'Gula Pasir', 'status' => BahanBaku::STATUS_INACTIVE]);

    $this->actingAs($admin)->get(route('admin.bahan-baku.index', ['status' => BahanBaku::STATUS_INACTIVE]))
        ->assertOk()
        ->assertSee('Gula Pasir')
        ->assertDontSee('Beras');
});
