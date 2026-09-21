<?php

use App\Models\Distribusi;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function sekolahData(array $overrides = []): array
{
    return array_merge([
        'npsn' => '12345678',
        'nama' => 'SDN 1 Sukamaju',
        'alamat' => 'Jl. Pendidikan No. 1',
        'kontak' => '081234567890',
        'jumlah_penerima' => 250,
        'status' => Sekolah::STATUS_ACTIVE,
    ], $overrides);
}

test('admin can view sekolah index', function () {
    $admin = User::factory()->admin()->create();
    Sekolah::factory()->create(['nama' => 'SDN 1 Sukamaju']);

    $this->actingAs($admin)->get(route('admin.sekolah.index'))
        ->assertOk()
        ->assertSee('SDN 1 Sukamaju');
});

test('sekolah index can be searched and filtered', function () {
    $admin = User::factory()->admin()->create();
    Sekolah::factory()->create(['nama' => 'SDN 1 Sukamaju', 'npsn' => '11111111']);
    Sekolah::factory()->create(['nama' => 'SDN 2 Mekarsari', 'npsn' => '22222222', 'status' => Sekolah::STATUS_INACTIVE]);

    $this->actingAs($admin)->get(route('admin.sekolah.index', ['search' => 'Mekarsari']))
        ->assertOk()
        ->assertSee('SDN 2 Mekarsari')
        ->assertDontSee('SDN 1 Sukamaju');

    $this->actingAs($admin)->get(route('admin.sekolah.index', ['search' => '11111111']))
        ->assertOk()
        ->assertSee('SDN 1 Sukamaju')
        ->assertDontSee('SDN 2 Mekarsari');

    $this->actingAs($admin)->get(route('admin.sekolah.index', ['status' => Sekolah::STATUS_INACTIVE]))
        ->assertOk()
        ->assertSee('SDN 2 Mekarsari')
        ->assertDontSee('SDN 1 Sukamaju');
});

test('admin can create a sekolah', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.sekolah.store'), sekolahData())
        ->assertRedirect(route('admin.sekolah.index'));

    $this->assertDatabaseHas('sekolah', [
        'npsn' => '12345678',
        'nama' => 'SDN 1 Sukamaju',
        'jumlah_penerima' => 250,
        'status' => Sekolah::STATUS_ACTIVE,
    ]);
});

test('sekolah creation requires valid data', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.sekolah.store'), [])
        ->assertSessionHasErrors(['npsn', 'nama', 'jumlah_penerima', 'status']);
});

test('sekolah creation rejects negative jumlah_penerima', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.sekolah.store'), sekolahData(['jumlah_penerima' => -1]))
        ->assertSessionHasErrors('jumlah_penerima');
});

test('sekolah creation rejects duplicate npsn', function () {
    $admin = User::factory()->admin()->create();
    Sekolah::factory()->create(['npsn' => '12345678']);

    $this->actingAs($admin)->post(route('admin.sekolah.store'), sekolahData())
        ->assertSessionHasErrors('npsn');
});

test('admin can view sekolah detail', function () {
    $admin = User::factory()->admin()->create();
    $sekolah = Sekolah::factory()->create(['nama' => 'SDN 1 Sukamaju']);

    $this->actingAs($admin)->get(route('admin.sekolah.show', $sekolah))
        ->assertOk()
        ->assertSee('SDN 1 Sukamaju');
});

test('admin can update a sekolah', function () {
    $admin = User::factory()->admin()->create();
    $sekolah = Sekolah::factory()->create(['npsn' => '12345678']);

    $this->actingAs($admin)->put(route('admin.sekolah.update', $sekolah), sekolahData([
        'nama' => 'SDN 1 Sukamaju Baru',
        'jumlah_penerima' => 300,
        'status' => Sekolah::STATUS_INACTIVE,
    ]))->assertRedirect(route('admin.sekolah.index'));

    $this->assertDatabaseHas('sekolah', [
        'id' => $sekolah->id,
        'nama' => 'SDN 1 Sukamaju Baru',
        'jumlah_penerima' => 300,
        'status' => Sekolah::STATUS_INACTIVE,
    ]);
});

test('sekolah update keeps its own npsn valid', function () {
    $admin = User::factory()->admin()->create();
    $sekolah = Sekolah::factory()->create(['npsn' => '12345678']);

    $this->actingAs($admin)->put(route('admin.sekolah.update', $sekolah), sekolahData())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.sekolah.index'));
});

test('admin can delete a sekolah without distribution', function () {
    $admin = User::factory()->admin()->create();
    $sekolah = Sekolah::factory()->create();

    $this->actingAs($admin)->delete(route('admin.sekolah.destroy', $sekolah))
        ->assertRedirect(route('admin.sekolah.index'));

    $this->assertDatabaseMissing('sekolah', ['id' => $sekolah->id]);
});

test('sekolah used by distribution cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    $sekolah = Sekolah::factory()->create();
    Distribusi::factory()->create(['sekolah_id' => $sekolah->id]);

    $this->actingAs($admin)->delete(route('admin.sekolah.destroy', $sekolah))
        ->assertRedirect(route('admin.sekolah.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('sekolah', ['id' => $sekolah->id]);
});

test('kepala can view sekolah but cannot manage it', function () {
    $kepala = User::factory()->kepala()->create();
    $sekolah = Sekolah::factory()->create();

    $this->actingAs($kepala)->get(route('admin.sekolah.index'))->assertOk();
    $this->actingAs($kepala)->get(route('admin.sekolah.show', $sekolah))->assertOk();
    $this->actingAs($kepala)->get(route('admin.sekolah.create'))->assertForbidden();
    $this->actingAs($kepala)->post(route('admin.sekolah.store'), sekolahData())->assertForbidden();
    $this->actingAs($kepala)->get(route('admin.sekolah.edit', $sekolah))->assertForbidden();
    $this->actingAs($kepala)->put(route('admin.sekolah.update', $sekolah), sekolahData())->assertForbidden();
    $this->actingAs($kepala)->delete(route('admin.sekolah.destroy', $sekolah))->assertForbidden();
});

test('petugas can read sekolah but cannot manage it', function () {
    $petugas = User::factory()->petugas()->create();
    $sekolah = Sekolah::factory()->create();

    $this->actingAs($petugas)->get(route('admin.sekolah.index'))->assertOk();
    $this->actingAs($petugas)->get(route('admin.sekolah.show', $sekolah))->assertOk();
    $this->actingAs($petugas)->get(route('admin.sekolah.create'))->assertForbidden();
    $this->actingAs($petugas)->post(route('admin.sekolah.store'), sekolahData())->assertForbidden();
    $this->actingAs($petugas)->put(route('admin.sekolah.update', $sekolah), sekolahData())->assertForbidden();
    $this->actingAs($petugas)->delete(route('admin.sekolah.destroy', $sekolah))->assertForbidden();
});

test('guest is redirected to login from sekolah module', function () {
    $this->get(route('admin.sekolah.index'))->assertRedirect(route('login'));
    $this->post(route('admin.sekolah.store'), sekolahData())->assertRedirect(route('login'));
});
